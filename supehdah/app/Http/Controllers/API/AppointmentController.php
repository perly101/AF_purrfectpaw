<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentFieldValue;
use App\Models\ClinicField;
use App\Models\ClinicInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AppointmentController extends Controller
{
    /**
     * Create a new appointment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $clinicId
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, $clinicId)
    {
        try {
            // Validate request
            $validatedData = $request->validate([
                'owner_name' => 'required|string|max:255',
                'owner_phone' => 'required|string|max:20',
                'appointment_date' => 'required|date',
                'appointment_time' => 'required|string',
                'responses' => 'sometimes|array',
            ]);

            // Find the clinic
            $clinic = ClinicInfo::findOrFail($clinicId);
            
            // Log the request data for debugging
            Log::info('=== APPOINTMENT BOOKING DEBUG ===');
            Log::info('Mobile app sent:', [
                'clinic_id' => $clinicId,
                'owner_name' => $request->owner_name,
                'appointment_date' => $request->appointment_date,
                'appointment_time' => $request->appointment_time,
                'raw_request' => $request->all()
            ]);
            
            // Create the appointment data
            $appointmentData = [
                'clinic_id' => $clinic->id,
                'owner_name' => $request->owner_name,
                'owner_phone' => $request->owner_phone,
                'appointment_date' => $request->appointment_date,
                'appointment_time' => $request->appointment_time,
                'status' => 'confirmed',
            ];
            
            Log::info('About to store in database:', $appointmentData);

            // Create appointment with date and time
            $appointment = Appointment::create($appointmentData);
            
            Log::info('Appointment created with ID:', [
                'id' => $appointment->id,
                'stored_date' => $appointment->appointment_date,
                'stored_time' => $appointment->appointment_time,
                'created_at' => $appointment->created_at
            ]);

            // Process custom field responses if provided
            if ($request->has('responses') && is_array($request->responses)) {
                foreach ($request->responses as $response) {
                    if (isset($response['field_id']) && isset($response['value'])) {
                        // If field_id isn't numeric, it might be a special field like pet_name
                        $fieldId = $response['field_id'];
                        
                        // For non-numeric field IDs, try to find or create a corresponding field
                        if (!is_numeric($fieldId)) {
                            // Look up or create the field
                            $field = ClinicField::firstOrCreate(
                                ['clinic_id' => $clinic->id, 'label' => ucfirst(str_replace('_', ' ', $fieldId))],
                                [
                                    'type' => 'text',
                                    'required' => false,
                                    'order' => 99 // Put at the end of the list
                                ]
                            );
                            $fieldId = $field->id;
                        }
                        
                        // Now create the response
                        AppointmentFieldValue::create([
                            'appointment_id' => $appointment->id,
                            'clinic_field_id' => $fieldId,
                            'value' => is_array($response['value']) 
                                ? json_encode($response['value']) 
                                : $response['value'],
                        ]);
                    }
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Appointment booked successfully!',
                'appointment_id' => $appointment->id,
                'data' => $appointment
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating appointment: ' . $e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to book appointment: ' . $e->getMessage(),
                'debug_info' => [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ], 500);
        }
    }

    /**
     * Get appointments for a clinic.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $clinicId
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, $clinicId)
    {
        try {
            // Validate clinic existence
            $clinic = ClinicInfo::findOrFail($clinicId);
            
            // Get appointments
            $query = Appointment::where('clinic_id', $clinic->id);
            
            // Filter by date if provided
            if ($request->has('date')) {
                $query->whereDate('appointment_date', $request->date);
            }
            
            // Filter by status if provided
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            
            // Get results
            $appointments = $query->orderBy('appointment_date', 'asc')
                               ->orderBy('appointment_time', 'asc')
                               ->get();
            
            return response()->json([
                'status' => 'success',
                'count' => count($appointments),
                'appointments' => $appointments
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching appointments: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch appointments: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get booked slots for a specific date.
     *
     * @param  int  $clinicId
     * @param  string  $date
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBookedSlots($clinicId, $date)
    {
        try {
            // Validate clinic existence
            $clinic = ClinicInfo::findOrFail($clinicId);
            
            // Get appointments for this date that are not cancelled
            $appointments = Appointment::where('clinic_id', $clinic->id)
                               ->whereDate('appointment_date', $date)
                               ->whereNotIn('status', ['cancelled'])
                               ->get();
            
            // Format booked slots
            $bookedSlots = $appointments->map(function ($appointment) {
                return [
                    'appointment_id' => $appointment->id,
                    'start_time' => $appointment->appointment_time,
                    'owner_name' => $appointment->owner_name,
                    'status' => $appointment->status
                ];
            });
            
            return response()->json([
                'status' => 'success',
                'date' => $date,
                'bookedSlots' => $bookedSlots,
                'count' => count($bookedSlots),
                'debug' => [
                    'raw_appointments' => $appointments
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching booked slots: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch booked slots: ' . $e->getMessage()
            ], 500);
        }
    }
}
