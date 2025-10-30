<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ClinicInfo;
use App\Models\ClinicAppointment;
use App\Models\CustomField;
use App\Models\WorkingHour;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class ClinicAvailabilityController extends Controller
{
    /**
     * Get availability summary for a clinic
     * 
     * @param int $clinicId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSummary($clinicId)
    {
        // Check if clinic exists
        $clinic = ClinicInfo::find($clinicId);
        if (!$clinic) {
            return response()->json([
                'status' => 'error',
                'message' => 'Clinic not found'
            ], 404);
        }

        // Get today's date and next week dates
        $today = Carbon::now();
        $nextDays = [];
        
        // Get clinic settings
        $dailyLimit = 20; // Default value
        $slotDuration = 30; // Default 30 min
        
        // If the clinic has working hours set up, use those settings
        $settings = [
            'daily_limit' => $dailyLimit,
            'slot_duration' => $slotDuration,
        ];

        // Get working hours
        $workingHours = WorkingHour::where('clinic_id', $clinicId)->get();
        if ($workingHours->isNotEmpty()) {
            $defaultWorkingHour = $workingHours->first();
            $settings['default_start_time'] = $defaultWorkingHour->start_time;
            $settings['default_end_time'] = $defaultWorkingHour->end_time;
        }
        
        // Build today's availability
        $dayOfWeek = strtolower($today->format('l'));
        $todayWorkingHours = $workingHours->where('day', $dayOfWeek)->first();
        $todayClosed = !$todayWorkingHours || !$todayWorkingHours->is_open;
        
        // Count today's booked appointments
        $todayBookedCount = ClinicAppointment::where('clinic_id', $clinicId)
            ->whereDate('appointment_date', $today->format('Y-m-d'))
            ->count();
        
        $todayRemaining = $todayClosed ? 0 : ($dailyLimit - $todayBookedCount);
        if ($todayRemaining < 0) $todayRemaining = 0;
        
        $todayData = [
            'is_closed' => $todayClosed,
            'booked_count' => $todayBookedCount,
            'remaining_slots' => $todayRemaining,
            'daily_limit' => $dailyLimit
        ];
        
        // Build next 7 days availability
        for ($i = 1; $i <= 7; $i++) {
            $date = $today->copy()->addDays($i);
            $dayName = $date->format('l');
            $dayWorkingHours = $workingHours->where('day', strtolower($dayName))->first();
            $isClosed = !$dayWorkingHours || !$dayWorkingHours->is_open;
            
            // Count booked appointments for this day
            $bookedCount = ClinicAppointment::where('clinic_id', $clinicId)
                ->whereDate('appointment_date', $date->format('Y-m-d'))
                ->count();
                
            $remainingSlots = $isClosed ? 0 : ($dailyLimit - $bookedCount);
            if ($remainingSlots < 0) $remainingSlots = 0;
            
            $nextDays[] = [
                'date' => $date->format('Y-m-d'),
                'day_name' => $dayName,
                'is_closed' => $isClosed,
                'booked_count' => $bookedCount,
                'remaining_slots' => $remainingSlots,
                'daily_limit' => $dailyLimit
            ];
        }
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'today' => $todayData,
                'next_week' => $nextDays,
                'settings' => $settings,
                'timestamp' => $today->timestamp
            ]
        ]);
    }

    /**
     * Get available time slots for a specific date
     * 
     * @param int $clinicId
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSlots($clinicId, Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'date' => 'required|date_format:Y-m-d'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid date format',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Check if clinic exists
        $clinic = ClinicInfo::find($clinicId);
        if (!$clinic) {
            return response()->json([
                'status' => 'error',
                'message' => 'Clinic not found'
            ], 404);
        }
        
        $date = $request->date;
        $carbonDate = Carbon::parse($date);
        $dayOfWeek = strtolower($carbonDate->format('l'));
        
        // Get working hours for this day
        $workingHours = WorkingHour::where('clinic_id', $clinicId)
            ->where('day', $dayOfWeek)
            ->first();
            
        // Check if clinic is closed on this day
        if (!$workingHours || !$workingHours->is_open) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'is_available' => false,
                    'date' => $date,
                    'slots' => [],
                    'daily_limit' => 0,
                    'booked_count' => 0,
                    'slots_remaining' => 0,
                    'message' => 'Clinic is closed on this day'
                ]
            ]);
        }
        
        // Default slot duration and limits
        $slotDuration = 30; // minutes
        $dailyLimit = 20;
        
        // Generate time slots based on working hours
        $startTime = Carbon::parse($workingHours->start_time);
        $endTime = Carbon::parse($workingHours->end_time);
        $slots = [];
        
        $currentSlot = $startTime->copy();
        while ($currentSlot < $endTime) {
            $slotEnd = $currentSlot->copy()->addMinutes($slotDuration);
            
            // Check if this slot is already booked
            $isBooked = ClinicAppointment::where('clinic_id', $clinicId)
                ->whereDate('appointment_date', $date)
                ->whereTime('appointment_time', $currentSlot->format('H:i:s'))
                ->exists();
                
            if (!$isBooked) {
                $slots[] = [
                    'start' => $currentSlot->format('H:i:s'),
                    'end' => $slotEnd->format('H:i:s'),
                    'display_time' => $currentSlot->format('g:i A') . ' - ' . $slotEnd->format('g:i A')
                ];
            }
            
            $currentSlot = $slotEnd;
        }
        
        // Count booked appointments for this day
        $bookedCount = ClinicAppointment::where('clinic_id', $clinicId)
            ->whereDate('appointment_date', $date)
            ->count();
        
        $slotsRemaining = $dailyLimit - $bookedCount;
        if ($slotsRemaining < 0) $slotsRemaining = 0;
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'is_available' => true,
                'date' => $date,
                'slots' => $slots,
                'daily_limit' => $dailyLimit,
                'booked_count' => $bookedCount,
                'slots_remaining' => $slotsRemaining
            ]
        ]);
    }

    /**
     * Get custom fields for a clinic
     * 
     * @param int $clinicId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCustomFields($clinicId)
    {
        // Check if clinic exists
        $clinic = ClinicInfo::find($clinicId);
        if (!$clinic) {
            return response()->json([
                'status' => 'error',
                'message' => 'Clinic not found'
            ], 404);
        }
        
        $fields = CustomField::where('clinic_id', $clinicId)->get()
            ->map(function($field) {
                return [
                    'id' => $field->id,
                    'label' => $field->label,
                    'type' => $field->type,
                    'options' => $field->options ? json_decode($field->options) : null,
                    'required' => (bool)$field->required
                ];
            });
            
        return response()->json([
            'status' => 'success',
            'data' => $fields
        ]);
    }
    
    /**
     * Book an appointment
     * 
     * @param int $clinicId
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bookAppointment($clinicId, Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'owner_name' => 'required|string|max:255',
            'owner_phone' => 'required|string|max:20',
            'appointment_date' => 'required|date_format:Y-m-d',
            'appointment_time' => 'required|date_format:H:i:s',
            'responses' => 'array'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid appointment data',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Check if clinic exists
        $clinic = ClinicInfo::find($clinicId);
        if (!$clinic) {
            return response()->json([
                'status' => 'error',
                'message' => 'Clinic not found'
            ], 404);
        }
        
        // Check if the slot is still available
        $isBooked = ClinicAppointment::where('clinic_id', $clinicId)
            ->whereDate('appointment_date', $request->appointment_date)
            ->whereTime('appointment_time', $request->appointment_time)
            ->exists();
            
        if ($isBooked) {
            return response()->json([
                'status' => 'error',
                'message' => 'This appointment slot is already booked'
            ], 409);
        }
        
        // Log the full request data for debugging
        \Illuminate\Support\Facades\Log::info('Appointment booking request:', $request->all());
        
        // Ensure appointment time is properly formatted
        $appointmentTime = $request->appointment_time;
        if (substr_count($appointmentTime, ':') === 1) {
            $appointmentTime .= ':00';
        }
        
        // Create appointment in ClinicAppointment model
        $appointment = new ClinicAppointment();
        $appointment->clinic_id = $clinicId;
        $appointment->owner_name = $request->owner_name;
        $appointment->owner_phone = $request->owner_phone;
        $appointment->appointment_date = $request->appointment_date;
        $appointment->appointment_time = $appointmentTime;
        $appointment->display_time = $request->display_time ?? null; // Store display time if provided
        $appointment->responses = json_encode($request->responses ?? []);
        $appointment->status = 'pending';
        $appointment->save();
        
        // Also create a record in the standard Appointment model
        // This ensures the appointment shows up in the management system
        try {
            $standardAppointment = new \App\Models\Appointment();
            $standardAppointment->clinic_id = $clinicId;
            $standardAppointment->owner_name = $request->owner_name;
            $standardAppointment->owner_phone = $request->owner_phone;
            $standardAppointment->appointment_date = $request->appointment_date;
            $standardAppointment->appointment_time = $appointmentTime;
            $standardAppointment->status = 'pending';
            $standardAppointment->save();
            
            // Log success for debugging
            \Illuminate\Support\Facades\Log::info('Created appointment in both models:', [
                'clinic_appointment_id' => $appointment->id,
                'standard_appointment_id' => $standardAppointment->id
            ]);
        } catch (\Exception $e) {
            // Log error but don't fail the request
            \Illuminate\Support\Facades\Log::error('Failed to create standard appointment: ' . $e->getMessage());
        }
        
        return response()->json([
            'status' => 'success',
            'message' => 'Appointment booked successfully',
            'data' => [
                'id' => $appointment->id,
                'date' => $appointment->appointment_date,
                'time' => $appointment->appointment_time,
                'status' => $appointment->status
            ]
        ]);
    }
}
