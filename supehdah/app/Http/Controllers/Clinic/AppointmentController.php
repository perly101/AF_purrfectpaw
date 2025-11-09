<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\ClinicInfo;
use App\Models\Doctor;
use App\Services\Notification\NotificationService;
use App\Services\SmsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AppointmentController extends Controller
{
    /**
     * Display a listing of appointments.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $clinic = ClinicInfo::where('user_id', Auth::id())->firstOrFail();
        $appointments = Appointment::where('clinic_id', $clinic->id)
            ->where(function($query) {
                // Include all non-completed appointments
                $query->whereNotIn('status', ['completed', 'cancelled'])
                      // OR include completed appointments that are unpaid
                      ->orWhere(function($subQuery) {
                          $subQuery->where('status', 'completed')
                                   ->where(function($paymentQuery) {
                                       $paymentQuery->whereNull('payment_status')
                                                   ->orWhere('payment_status', 'unpaid');
                                   });
                      });
            })
            ->with('doctor') // Eager load doctor relationship
            ->orderByRaw("CASE 
                WHEN status = 'pending' THEN 1
                WHEN status = 'assigned' THEN 2
                WHEN status = 'confirmed' THEN 3
                WHEN status = 'completed' AND (payment_status IS NULL OR payment_status = 'unpaid') THEN 4
                WHEN status = 'closed' THEN 5
                ELSE 6 END")
            ->orderBy('appointment_date', 'desc')
            ->orderBy('appointment_time', 'desc')
            ->paginate(15);
            
        return view('clinic.appointments.index', compact('appointments', 'clinic'));
    }
    
    /**
     * Display a listing of completed and cancelled appointments.
     *
     * @return \Illuminate\View\View
     */
    public function archivedAppointments()
    {
        try {
            $clinic = ClinicInfo::where('user_id', Auth::id())->firstOrFail();
            
            // Get all distinct patient names that have paid completed appointments or cancelled appointments
            $patients = DB::table('appointments')
                ->select('owner_name', 'owner_phone', DB::raw('COUNT(*) as appointments_count'))
                ->where('clinic_id', $clinic->id)
                ->where(function($query) {
                    // Include cancelled appointments
                    $query->where('status', 'cancelled')
                          // OR include completed appointments that are paid
                          ->orWhere(function($subQuery) {
                              $subQuery->where('status', 'completed')
                                       ->where('payment_status', 'paid');
                          });
                })
                ->groupBy('owner_name', 'owner_phone')
                ->orderBy('owner_name')
                ->get();
                
            // Convert to a collection for pagination
            $patients = collect($patients);
            
            // Paginate the patients list
            $perPage = 15;
            $currentPage = request()->get('page', 1);
            $pagedPatients = new \Illuminate\Pagination\LengthAwarePaginator(
                $patients->forPage($currentPage, $perPage),
                $patients->count(),
                $perPage,
                $currentPage,
                ['path' => request()->url(), 'query' => request()->query()]
            );
                
            return view('clinic.appointments.archived', [
                'patients' => $pagedPatients,
                'clinic' => $clinic
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in archivedAppointments: ' . $e->getMessage());
            return view('clinic.appointments.archived', [
                'patients' => collect([]),
                'clinic' => $clinic ?? null,
                'error' => 'There was an error loading the archived appointments. Please try again.'
            ]);
        }
    }
    
    /**
     * Show appointment details
     * 
     * @param int $id
     * @return \Illuminate\View\View
     */
    
    /**
     * Delete an appointment.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function delete($id)
    {
        $clinic = ClinicInfo::where('user_id', Auth::id())->firstOrFail();
        $appointment = Appointment::where('clinic_id', $clinic->id)->findOrFail($id);
        
        // Log the appointment info before deletion for debugging
        \Illuminate\Support\Facades\Log::info('Deleting appointment', [
            'id' => $appointment->id,
            'date' => $appointment->appointment_date,
            'time' => $appointment->appointment_time,
            'status' => $appointment->status,
        ]);
        
        // Delete the appointment
        $appointment->delete();
        
        return redirect()->route('clinic.appointments.index')
            ->with('success', 'Appointment has been deleted successfully and the slot is now available for booking.');
    }
    
    /**
     * Show the form for viewing an appointment.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $clinic = ClinicInfo::where('user_id', Auth::id())->firstOrFail();
        $appointment = Appointment::where('clinic_id', $clinic->id)
            ->with(['customValues.field', 'doctor'])
            ->findOrFail($id);
            
        // Check if it's an AJAX request
        if (request()->ajax() || request()->has('ajax')) {
            return view('clinic.appointments.partials.appointment_details', compact('appointment', 'clinic'));
        }
            
        return view('clinic.appointments.show', compact('appointment', 'clinic'));
    }
    
    /**
     * Update the specified appointment status.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateStatus(Request $request, $id)
    {
        $clinic = ClinicInfo::where('user_id', Auth::id())->firstOrFail();
        $appointment = Appointment::where('clinic_id', $clinic->id)->findOrFail($id);
        
        $request->validate([
            'status' => 'required|in:pending,assigned,confirmed,completed,closed,cancelled',
        ]);
        
        // If changing to completed status, validate that a doctor is assigned
        if ($request->status === 'completed' && !$appointment->doctor_id) {
            return back()->withErrors(['doctor' => 'An appointment must be assigned to a doctor before marking as completed.']);
        }
        
        $oldStatus = $appointment->status;
        $appointment->status = $request->status;
        $appointment->save();
        
        // Send SMS notification when appointment is confirmed
        if ($request->status === 'confirmed' && $oldStatus !== 'confirmed' && $appointment->owner_phone) {
            try {
                $smsService = app(SmsService::class);
                
                // Eager load doctor relationship for proper name
                $appointment->load('doctor');
                
                // Prepare comprehensive appointment data for SMS
                $appointmentData = [
                    'clinic_name' => $clinic->clinic_name ?? 'PurrfectPaw Veterinary Clinic',
                    'appointment_date' => $appointment->appointment_date ? 
                        \Carbon\Carbon::parse($appointment->appointment_date)->format('F j, Y') : 'TBD',
                    'appointment_time' => $appointment->formatted_time ?: 
                        ($appointment->appointment_time ? \Carbon\Carbon::parse($appointment->appointment_time)->format('g:i A') : 'TBD'),
                    'doctor_name' => $appointment->doctor ? $appointment->doctor->name : 'Available Doctor',
                    'pet_name' => $appointment->owner_name ?: 'your pet'
                ];
                
                $result = $smsService->sendAppointmentConfirmation($appointment->owner_phone, $appointmentData);
                
                if ($result['success']) {
                    Log::info('Clinic appointment confirmation SMS sent successfully', [
                        'appointment_id' => $appointment->id,
                        'clinic_name' => $appointmentData['clinic_name'],
                        'doctor_name' => $appointmentData['doctor_name'],
                        'phone' => $appointment->owner_phone,
                        'message_id' => $result['data']['message_id'] ?? null
                    ]);
                } else {
                    Log::error('Failed to send clinic appointment confirmation SMS', [
                        'appointment_id' => $appointment->id,
                        'phone' => $appointment->owner_phone,
                        'error' => $result['error'] ?? 'Unknown error'
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('SMS service error during clinic appointment confirmation: ' . $e->getMessage(), [
                    'appointment_id' => $appointment->id,
                    'phone' => $appointment->owner_phone
                ]);
            }
        }
        
        // Send SMS notification when appointment is cancelled
        if ($request->status === 'cancelled' && $oldStatus !== 'cancelled' && $appointment->owner_phone) {
            try {
                $smsService = app(SmsService::class);
                
                $appointmentData = [
                    'clinic_name' => $clinic->clinic_name ?? 'AutoRepair Clinic',
                    'appointment_date' => $appointment->appointment_date ? 
                        \Carbon\Carbon::parse($appointment->appointment_date)->format('F j, Y') : '',
                    'appointment_time' => $appointment->formatted_time ?? ''
                ];
                
                $smsService->sendAppointmentCancellation($appointment->owner_phone, $appointmentData);
            } catch (\Exception $e) {
                Log::error('SMS service error during appointment cancellation: ' . $e->getMessage());
            }
        }
        
        // If the appointment is marked as completed, send a notification
        if ($request->status === 'completed' && $oldStatus !== 'completed') {
            try {
                $notificationService = app(NotificationService::class);
                $notificationService->notifyClinicAppointmentCompleted($clinic, $appointment);
            } catch (\Exception $e) {
                // Log the error but don't prevent the status update
                Log::error('Failed to send appointment completion notification: ' . $e->getMessage());
            }
        }
        
        return redirect()->route('clinic.appointments.show', $id)
            ->with('success', 'Appointment status updated successfully');
    }
    
    /**
     * Assign a doctor to an appointment
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function assignDoctor(Request $request, $id)
    {
        $clinic = ClinicInfo::where('user_id', Auth::id())->firstOrFail();
        $appointment = Appointment::where('clinic_id', $clinic->id)->findOrFail($id);
        
        $request->validate([
            'doctor_id' => 'required|exists:doctors,id'
        ]);
        
        $appointment->doctor_id = $request->doctor_id;
        
        // If the appointment is in pending status, update it to assigned
        if ($appointment->status === 'pending') {
            $appointment->status = 'assigned';
        }
        
        $appointment->save();
        
        // Send notification to the doctor about the new appointment assignment
        $doctor = Doctor::find($request->doctor_id);
        if ($doctor) {
            try {
                $notificationService = app(NotificationService::class);
                $appointment->load(['clinic']); // Ensure clinic relationship is loaded
                $notificationService->notifyDoctorAppointmentAssigned($doctor, $appointment);
                
                Log::info('Doctor appointment assignment notification sent', [
                    'doctor_id' => $doctor->id,
                    'appointment_id' => $appointment->id,
                    'clinic_id' => $clinic->id
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send doctor appointment assignment notification', [
                    'error' => $e->getMessage(),
                    'doctor_id' => $doctor->id,
                    'appointment_id' => $appointment->id
                ]);
            }
        }
        
        return redirect()->route('clinic.appointments.show', $id)
            ->with('success', 'Doctor assigned to appointment successfully');
    }
    
    /**
     * Add consultation notes to a completed appointment
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function addNotes(Request $request, $id)
    {
        $clinic = ClinicInfo::where('user_id', Auth::id())->firstOrFail();
        $appointment = Appointment::where('clinic_id', $clinic->id)->findOrFail($id);
        
        $request->validate([
            'notes' => 'required|string'
        ]);
        
        $appointment->notes = $request->notes;
        
        // If the appointment is in completed status, update it to closed
        if ($appointment->status === 'completed') {
            $appointment->status = 'closed';
        }
        
        $appointment->save();
        
        return redirect()->route('clinic.appointments.show', $id)
            ->with('success', 'Consultation notes added successfully');
    }
    
    /**
     * Display appointment history for a specific patient
     *
     * @param string $name Patient name
     * @param string $phone Patient phone
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function patientHistory($name, $phone)
    {
        $clinic = ClinicInfo::where('user_id', Auth::id())->firstOrFail();
        
        // Get patient info
        $patientInfo = Appointment::where('clinic_id', $clinic->id)
            ->where('owner_name', $name)
            ->where('owner_phone', $phone)
            ->first();
            
        if (!$patientInfo) {
            return redirect()->route('clinic.appointments.archived')
                ->with('error', 'Patient not found');
        }
        
        // Get all appointments for this patient
        $appointments = Appointment::where('clinic_id', $clinic->id)
            ->where('owner_name', $name)
            ->where('owner_phone', $phone)
            ->with(['doctor'])
            ->orderBy('updated_at', 'desc')
            ->get();
            
        return view('clinic.appointments.patient-history', compact('patientInfo', 'appointments', 'clinic'));
    }
}
