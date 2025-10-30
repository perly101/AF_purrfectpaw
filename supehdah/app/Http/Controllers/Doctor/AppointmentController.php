<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\ClinicInfo;
use App\Services\Notification\NotificationService;
use App\Services\SmsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AppointmentController extends Controller
{
    /**
     * Display a listing of all appointments for the doctor.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Get the current doctor
        $doctor = Doctor::where('user_id', Auth::id())->firstOrFail();
        
        // Initialize query for doctor's appointments
        $query = Appointment::where('doctor_id', $doctor->id);
        
        // Handle status filtering
        if (request()->has('status') && request()->get('status')) {
            if (request()->get('status') == 'show_all') {
                // Show all statuses
            } else {
                // Filter by specific status
                $query->where('status', request()->get('status'));
            }
        } else {
            // By default, exclude cancelled and closed appointments
            $query->whereNotIn('status', ['cancelled', 'closed']);
        }
        
        // Apply date filter if provided
        if (request()->has('date') && request()->get('date')) {
            $query->whereDate('appointment_date', request()->get('date'));
        }
            
        // Get the appointments
        $appointments = $query->orderByRaw("CASE 
                WHEN status = 'assigned' THEN 1
                WHEN status = 'confirmed' THEN 2
                WHEN status = 'in_progress' THEN 3
                WHEN status = 'completed' THEN 4
                ELSE 5 END")
            ->orderBy('appointment_date', 'desc')
            ->orderBy('appointment_time', 'desc')
            ->paginate(15);
            
        return view('doctor.appointments.index', compact('appointments', 'doctor'));
    }
    
    /**
     * Display a specific appointment details.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        // Get the current doctor
        $doctor = Doctor::where('user_id', Auth::id())->firstOrFail();
        
        // Get the appointment, ensuring it belongs to this doctor
        $appointment = Appointment::where('doctor_id', $doctor->id)
            ->with('customValues.field')
            ->findOrFail($id);
            
        // Get patient history (previous appointments for this patient)
        $patientHistory = Appointment::where('doctor_id', $doctor->id)
            ->where('id', '!=', $appointment->id)
            ->where('owner_phone', $appointment->owner_phone)
            ->orderBy('appointment_date', 'desc')
            ->get();
            
        return view('doctor.appointments.show', compact('appointment', 'doctor', 'patientHistory'));
    }
    
    /**
     * Update the status of an appointment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateStatus(Request $request, $id)
    {
        // Get the current doctor
        $doctor = Doctor::where('user_id', Auth::id())->firstOrFail();
        
        // Get the appointment, ensuring it belongs to this doctor
        $appointment = Appointment::where('doctor_id', $doctor->id)
            ->findOrFail($id);
            
        $request->validate([
            'status' => 'required|in:assigned,confirmed,in_progress,completed,closed,cancelled',
        ]);
        
        $oldStatus = $appointment->status;
        $appointment->status = $request->status;
        $appointment->save();
        
        // Enhanced debugging and SMS notification when doctor confirms appointment
        Log::info('🔍 DOCTOR STATUS UPDATE DEBUG', [
            'appointment_id' => $appointment->id,
            'old_status' => $oldStatus,
            'new_status' => $request->status,
            'has_phone' => !empty($appointment->owner_phone),
            'phone' => $appointment->owner_phone,
            'will_send_sms' => ($request->status === 'confirmed' && $oldStatus !== 'confirmed' && $appointment->owner_phone)
        ]);
        
        if ($request->status === 'confirmed' && $oldStatus !== 'confirmed' && $appointment->owner_phone) {
            Log::info('🚀 DOCTOR CONFIRMING APPOINTMENT - SENDING SMS IMMEDIATELY', [
                'appointment_id' => $appointment->id,
                'patient_phone' => $appointment->owner_phone,
                'time' => now()->format('H:i:s')
            ]);
            
            try {
                $smsService = app(SmsService::class);
                
                // Get clinic information with eager loading
                $appointment->load(['clinic', 'doctor']);
                $clinic = $appointment->clinic;
                
                // Prepare comprehensive appointment data for SMS with complete information
                $doctorName = $appointment->doctor ? $appointment->doctor->name : ($doctor->name ?? 'Available Doctor');
                $clinicName = $clinic ? $clinic->clinic_name : 'PurrfectPaw Veterinary Clinic';
                
                $appointmentData = [
                    'clinic_name' => $clinicName,
                    'appointment_date' => $appointment->appointment_date ? 
                        \Carbon\Carbon::parse($appointment->appointment_date)->format('F j, Y') : 'TBD',
                    'appointment_time' => $appointment->formatted_time ?: 
                        ($appointment->appointment_time ? \Carbon\Carbon::parse($appointment->appointment_time)->format('g:i A') : 'TBD'),
                    'doctor_name' => $doctorName,
                    'pet_name' => $appointment->owner_name ?: 'your pet'
                ];
                
                $result = $smsService->sendAppointmentConfirmation($appointment->owner_phone, $appointmentData);
                
                if ($result['success']) {
                    Log::info('Doctor appointment confirmation SMS sent successfully', [
                        'appointment_id' => $appointment->id,
                        'doctor_id' => $doctor->id,
                        'doctor_name' => $appointmentData['doctor_name'],
                        'clinic_name' => $appointmentData['clinic_name'],
                        'phone' => $appointment->owner_phone,
                        'message_id' => $result['data']['message_id'] ?? null
                    ]);
                } else {
                    Log::error('Failed to send doctor appointment confirmation SMS', [
                        'appointment_id' => $appointment->id,
                        'doctor_id' => $doctor->id,
                        'phone' => $appointment->owner_phone,
                        'error' => $result['error'] ?? 'Unknown error'
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('SMS service error during doctor appointment confirmation: ' . $e->getMessage(), [
                    'appointment_id' => $appointment->id,
                    'doctor_id' => $doctor->id,
                    'phone' => $appointment->owner_phone
                ]);
            }
        }
        
        // Send SMS notification when doctor cancels appointment
        if ($request->status === 'cancelled' && $oldStatus !== 'cancelled' && $appointment->owner_phone) {
            try {
                $smsService = app(SmsService::class);
                $clinic = $appointment->clinic;
                
                $appointmentData = [
                    'clinic_name' => $clinic ? $clinic->clinic_name : 'AutoRepair Clinic',
                    'appointment_date' => $appointment->appointment_date ? 
                        \Carbon\Carbon::parse($appointment->appointment_date)->format('F j, Y') : '',
                    'appointment_time' => $appointment->formatted_time ?? ''
                ];
                
                $smsService->sendAppointmentCancellation($appointment->owner_phone, $appointmentData);
            } catch (\Exception $e) {
                Log::error('SMS service error during doctor appointment cancellation: ' . $e->getMessage());
            }
        }
        
        return redirect()->route('doctor.appointments.show', $id)
            ->with('success', 'Appointment status updated successfully');
    }
    
    /**
     * Accept or decline an appointment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function acceptDecline(Request $request, $id)
    {
        // Get the current doctor
        $doctor = Doctor::where('user_id', Auth::id())->firstOrFail();
        
        // Get the appointment, ensuring it belongs to this doctor
        $appointment = Appointment::where('doctor_id', $doctor->id)
            ->findOrFail($id);
            
        $request->validate([
            'action' => 'required|in:accept,decline',
        ]);
        
        if ($request->action === 'accept') {
            $appointment->status = 'confirmed';
            $message = 'Appointment accepted successfully';
            
            // Enhanced debugging for accept action
            Log::info('🔍 DOCTOR ACCEPT/DECLINE DEBUG', [
                'appointment_id' => $appointment->id,
                'action' => $request->action,
                'has_phone' => !empty($appointment->owner_phone),
                'phone' => $appointment->owner_phone,
                'will_send_sms' => !empty($appointment->owner_phone)
            ]);
            
            // Send SMS notification when doctor accepts appointment
            if ($appointment->owner_phone) {
                Log::info('🚀 DOCTOR ACCEPTING APPOINTMENT - SENDING SMS IMMEDIATELY', [
                    'appointment_id' => $appointment->id,
                    'patient_phone' => $appointment->owner_phone,
                    'time' => now()->format('H:i:s')
                ]);
                
                try {
                    $smsService = app(SmsService::class);
                    $appointment->load(['clinic']);
                    $clinic = $appointment->clinic;
                    
                    // Enhanced appointment data with complete information
                    $doctorName = $doctor->name ?? 'Available Doctor';
                    $clinicName = $clinic ? $clinic->clinic_name : 'PurrfectPaw Veterinary Clinic';
                    
                    $appointmentData = [
                        'clinic_name' => $clinicName,
                        'appointment_date' => $appointment->appointment_date ? 
                            \Carbon\Carbon::parse($appointment->appointment_date)->format('F j, Y') : 'TBD',
                        'appointment_time' => $appointment->formatted_time ?: 
                            ($appointment->appointment_time ? \Carbon\Carbon::parse($appointment->appointment_time)->format('g:i A') : 'TBD'),
                        'doctor_name' => $doctorName,
                        'pet_name' => $appointment->owner_name ?: 'your pet'
                    ];
                    
                    $result = $smsService->sendAppointmentConfirmation($appointment->owner_phone, $appointmentData);
                    
                    if ($result['success']) {
                        Log::info('Doctor acceptance SMS sent successfully', [
                            'appointment_id' => $appointment->id,
                            'doctor_id' => $doctor->id,
                            'phone' => $appointment->owner_phone
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('SMS error during doctor acceptance: ' . $e->getMessage());
                }
            }
        } else {
            $appointment->status = 'cancelled';
            // Store cancellation reason in the notes field as JSON
            $cancellationData = ['cancellation_reason' => 'Declined by doctor', 'declined_at' => now()->format('Y-m-d H:i:s')];
            $appointment->notes = json_encode($cancellationData);
            $message = 'Appointment declined successfully';
            
            // Send SMS notification when doctor declines appointment
            if ($appointment->owner_phone) {
                try {
                    $smsService = app(SmsService::class);
                    $appointment->load(['clinic']);
                    $clinic = $appointment->clinic;
                    
                    // Enhanced cancellation data with doctor information
                    $doctorName = $doctor->name ?? 'Available Doctor';
                    $clinicName = $clinic ? $clinic->clinic_name : 'PurrfectPaw Veterinary Clinic';
                    
                    $appointmentData = [
                        'clinic_name' => $clinicName,
                        'appointment_date' => $appointment->appointment_date ? 
                            \Carbon\Carbon::parse($appointment->appointment_date)->format('F j, Y') : 'TBD',
                        'appointment_time' => $appointment->formatted_time ?: 
                            ($appointment->appointment_time ? \Carbon\Carbon::parse($appointment->appointment_time)->format('g:i A') : 'TBD'),
                        'doctor_name' => $doctorName,
                        'pet_name' => $appointment->owner_name ?: 'your pet'
                    ];
                    
                    $smsService->sendAppointmentCancellation($appointment->owner_phone, $appointmentData);
                } catch (\Exception $e) {
                    Log::error('SMS error during doctor decline: ' . $e->getMessage());
                }
            }
        }
        
        $appointment->save();
        
        return redirect()->route('doctor.appointments.index')
            ->with('success', $message);
    }
    
    /**
     * Start a consultation - update status to in_progress.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function startConsultation($id)
    {
        // Get the current doctor
        $doctor = Doctor::where('user_id', Auth::id())->firstOrFail();
        
        // Get the appointment, ensuring it belongs to this doctor
        $appointment = Appointment::where('doctor_id', $doctor->id)
            ->findOrFail($id);
            
        if ($appointment->status !== 'confirmed') {
            return redirect()->back()
                ->with('error', 'Only confirmed appointments can be started');
        }
        
        $appointment->status = 'in_progress';
        $appointment->save();
        
        return redirect()->route('doctor.appointments.show', $id)
            ->with('success', 'Consultation started successfully');
    }
    
    /**
     * Complete a consultation and add notes.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function completeConsultation(Request $request, $id)
    {
        // Get the current doctor
        $doctor = Doctor::where('user_id', Auth::id())->firstOrFail();
        
        // Get the appointment, ensuring it belongs to this doctor
        $appointment = Appointment::where('doctor_id', $doctor->id)
            ->findOrFail($id);
            
        $request->validate([
            'chief_complaint' => 'required|string',
            'history_observations' => 'required|string',
            'examination_findings' => 'required|string',
            'diagnosis' => 'required|string',
            'plan_recommendations' => 'required|string',
        ]);
        
        // Compile consultation notes in a structured format
        $consultationNotes = [
            'chief_complaint' => $request->chief_complaint,
            'history_observations' => $request->history_observations,
            'examination_findings' => $request->examination_findings,
            'diagnosis' => $request->diagnosis,
            'plan_recommendations' => $request->plan_recommendations,
            'completed_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ];
        
        // Store as JSON using the correct column name (notes instead of consultation_notes)
        $appointment->notes = json_encode($consultationNotes);
        $appointment->status = 'completed';  // Using 'completed' instead of 'closed' for consistency
        $appointment->save();
        
        // Notify clinic about the completed appointment
        try {
            $clinic = ClinicInfo::find($appointment->clinic_id);
            if ($clinic) {
                $notificationService = app(NotificationService::class);
                $notificationService->notifyClinicAppointmentCompleted($clinic, $appointment);
            }
        } catch (\Exception $e) {
            // Log the error but don't prevent the completion
            Log::error('Failed to send appointment completion notification: ' . $e->getMessage());
        }
        
        return redirect()->route('doctor.appointments.index')
            ->with('success', 'Consultation completed and notes saved successfully');
    }

    /**
     * Send SMS notification manually for any appointment
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sendSmsNotification($id)
    {
        // Get the current doctor
        $doctor = Doctor::where('user_id', Auth::id())->firstOrFail();
        
        // Get the appointment, ensuring it belongs to this doctor
        $appointment = Appointment::where('doctor_id', $doctor->id)
            ->findOrFail($id);

        if (!$appointment->owner_phone) {
            return redirect()->back()->with('error', 'No phone number available for this appointment');
        }

        Log::info('🚀 MANUAL SMS TRIGGER BY DOCTOR', [
            'appointment_id' => $appointment->id,
            'doctor_id' => $doctor->id,
            'patient_phone' => $appointment->owner_phone,
            'time' => now()->format('H:i:s'),
            'trigger' => 'MANUAL_BUTTON'
        ]);

        try {
            $smsService = app(SmsService::class);
            
            // Get clinic information with eager loading
            $appointment->load(['clinic', 'doctor']);
            $clinic = $appointment->clinic;
            
            // Prepare comprehensive appointment data for SMS with complete details
            $doctorName = $appointment->doctor ? $appointment->doctor->name : ($doctor->name ?? 'Available Doctor');
            $clinicName = $clinic ? $clinic->clinic_name : 'PurrfectPaw Veterinary Clinic';
            
            $appointmentData = [
                'clinic_name' => $clinicName,
                'appointment_date' => $appointment->appointment_date ? 
                    \Carbon\Carbon::parse($appointment->appointment_date)->format('F j, Y') : 'TBD',
                'appointment_time' => $appointment->formatted_time ?: 
                    ($appointment->appointment_time ? \Carbon\Carbon::parse($appointment->appointment_time)->format('g:i A') : 'TBD'),
                'doctor_name' => $doctorName,
                'pet_name' => $appointment->owner_name ?: 'your pet'
            ];
            
            $result = $smsService->sendAppointmentConfirmation($appointment->owner_phone, $appointmentData);
            
            if ($result['success']) {
                Log::info('✅ MANUAL SMS SENT SUCCESSFULLY', [
                    'appointment_id' => $appointment->id,
                    'doctor_id' => $doctor->id,
                    'phone' => $appointment->owner_phone,
                    'message_id' => $result['data']['message_id'] ?? null
                ]);
                
                return redirect()->back()->with('success', 'SMS notification sent successfully to ' . $appointment->owner_phone . ' (Message ID: ' . ($result['data']['message_id'] ?? 'N/A') . ')');
            } else {
                Log::error('❌ MANUAL SMS FAILED', [
                    'appointment_id' => $appointment->id,
                    'error' => $result['error'] ?? 'Unknown error',
                    'phone' => $appointment->owner_phone
                ]);
                
                return redirect()->back()->with('error', 'Failed to send SMS: ' . ($result['error'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            Log::error('❌ MANUAL SMS EXCEPTION', [
                'appointment_id' => $appointment->id,
                'exception' => $e->getMessage(),
                'phone' => $appointment->owner_phone
            ]);
            
            return redirect()->back()->with('error', 'SMS service error: ' . $e->getMessage());
        }
    }
}
