<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\ClinicAvailabilitySetting;
use App\Models\ClinicBreak;
use App\Models\ClinicDailySchedule;
use App\Models\ClinicInfo;
use App\Models\ClinicSpecialDate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CalendarApiController extends Controller
{
    /**
     * Get dates with availability for the next month
     *
     * @param  int  $clinicId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailabilityDates($clinicId)
    {
        $clinic = ClinicInfo::findOrFail($clinicId);
        
        $startDate = Carbon::today();
        $endDate = Carbon::today()->addMonths(3);
        
        // Get all special dates in the range
        $specialDates = ClinicSpecialDate::where('clinic_id', $clinicId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->keyBy(function($date) {
                return $date->date->format('Y-m-d');
            });
        
        // Get clinic daily schedules
        $dailySchedules = ClinicDailySchedule::where('clinic_id', $clinicId)
            ->get()
            ->keyBy('day_of_week');
        
        // Get availability settings
        $settings = ClinicAvailabilitySetting::where('clinic_id', $clinicId)->first();
        if (!$settings) {
            $settings = new ClinicAvailabilitySetting([
                'daily_limit' => 20,
                'slot_duration' => 30,
                'default_start_time' => '09:00',
                'default_end_time' => '17:00',
            ]);
        }
        
        // Get all appointments in the range
        $appointments = Appointment::where('clinic_id', $clinicId)
            ->whereBetween('appointment_date', [$startDate, $endDate])
            ->whereIn('status', ['pending', 'confirmed'])
            ->select(DB::raw('appointment_date, COUNT(*) as count'))
            ->groupBy('appointment_date')
            ->get()
            ->keyBy(function($item) {
                return Carbon::parse($item->appointment_date)->format('Y-m-d');
            });
        
        // Calculate available and closed dates
        $availableDates = [];
        $closedDates = [];
        $currentDate = $startDate->copy();
        
        while ($currentDate <= $endDate) {
            $dateString = $currentDate->format('Y-m-d');
            $dayOfWeek = $currentDate->format('l'); // Monday, Tuesday, etc.
            
            // Check if it's a special date
            if (isset($specialDates[$dateString])) {
                $specialDate = $specialDates[$dateString];
                
                if ($specialDate->is_closed) {
                    // This is a closed special date (holiday)
                    $closedDates[] = $dateString;
                } else {
                    // Get daily limit from special date or settings
                    $dailyLimit = $specialDate->daily_limit ?? $settings->daily_limit;
                    
                    // Check if we still have slots available
                    $bookedCount = isset($appointments[$dateString]) ? $appointments[$dateString]->count : 0;
                    
                    if ($bookedCount < $dailyLimit) {
                        $availableDates[] = $dateString;
                    }
                }
            } 
            // Check daily schedule
            else if (isset($dailySchedules[$dayOfWeek])) {
                if ($dailySchedules[$dayOfWeek]->is_closed) {
                    // This is a regular closed day (e.g., weekend)
                    $closedDates[] = $dateString;
                } else {
                    $dailyLimit = $dailySchedules[$dayOfWeek]->daily_limit ?? $settings->daily_limit;
                    
                    // Check if we still have slots available
                    $bookedCount = isset($appointments[$dateString]) ? $appointments[$dateString]->count : 0;
                    
                    if ($bookedCount < $dailyLimit) {
                        $availableDates[] = $dateString;
                    }
                }
            } else {
                // Default schedule for days without explicit settings
                // Assume weekends are closed by default
                if (in_array($dayOfWeek, ['Saturday', 'Sunday'])) {
                    $closedDates[] = $dateString;
                } else {
                    $availableDates[] = $dateString;
                }
            }
            
            $currentDate->addDay();
        }
        
        // Make sure we're explicitly returning both available and closed dates
        // This ensures the mobile app can properly display available days in green and closed days in red
        return response()->json([
            'dates' => $availableDates,
            'closed_dates' => $closedDates
        ]);
    }
    
    /**
     * Get available time slots for a specific date
     *
     * @param  int  $clinicId
     * @param  string  $date
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailableSlotsForDate($clinicId, $date)
    {
        $clinic = ClinicInfo::findOrFail($clinicId);
        $carbonDate = Carbon::parse($date);
        
        // Get clinic settings
        $settings = ClinicAvailabilitySetting::where('clinic_id', $clinicId)->first();
        if (!$settings) {
            $settings = new ClinicAvailabilitySetting([
                'daily_limit' => 20,
                'slot_duration' => 30,
                'default_start_time' => '09:00',
                'default_end_time' => '17:00',
            ]);
        }
        
        $dayOfWeek = $carbonDate->format('l'); // Monday, Tuesday, etc.
        
        // Check if it's a special date
        $specialDate = ClinicSpecialDate::where('clinic_id', $clinicId)
            ->where('date', $date)
            ->first();
            
        if ($specialDate && $specialDate->is_closed) {
            return response()->json(['data' => ['slots' => []]]);
        }
        
        // Get operating hours
        if ($specialDate && !$specialDate->is_closed) {
            // Use special date hours
            $startTime = Carbon::parse($specialDate->start_time);
            $endTime = Carbon::parse($specialDate->end_time);
            $dailyLimit = $specialDate->daily_limit ?? $settings->daily_limit;
            $slotDuration = $specialDate->slot_duration ?? $settings->slot_duration;
        } else {
            // Use regular schedule
            $dailySchedule = ClinicDailySchedule::where('clinic_id', $clinicId)
                ->where('day_of_week', $dayOfWeek)
                ->first();
                
            if (!$dailySchedule) {
                // Create default schedule
                $dailySchedule = new ClinicDailySchedule([
                    'day_of_week' => $dayOfWeek,
                    'start_time' => $settings->default_start_time,
                    'end_time' => $settings->default_end_time,
                    'is_closed' => in_array($dayOfWeek, ['Saturday', 'Sunday']),
                    'daily_limit' => $settings->daily_limit,
                    'slot_duration' => $settings->slot_duration,
                ]);
            }
            
            if ($dailySchedule->is_closed) {
                return response()->json(['data' => ['slots' => []]]);
            }
            
            $startTime = Carbon::parse($dailySchedule->start_time);
            $endTime = Carbon::parse($dailySchedule->end_time);
            $dailyLimit = $dailySchedule->daily_limit ?? $settings->daily_limit;
            $slotDuration = $dailySchedule->slot_duration ?? $settings->slot_duration;
        }
        
        // Check if we've reached the daily limit
        $bookedCount = Appointment::where('clinic_id', $clinicId)
            ->whereDate('appointment_date', $date)
            ->whereIn('status', ['pending', 'confirmed'])
            ->count();
            
        if ($bookedCount >= $dailyLimit) {
            return response()->json(['data' => ['slots' => []]]);
        }
        
        // Get already booked slots
        $bookedSlots = Appointment::where('clinic_id', $clinicId)
            ->whereDate('appointment_date', $date)
            ->whereIn('status', ['pending', 'confirmed'])
            ->get(['start_time', 'end_time']);
            
        // Generate all possible slots
        $availableSlots = [];
        $currentSlot = clone $startTime;
        
        while ($currentSlot->addMinutes($slotDuration)->lte($endTime)) {
            $slotStart = $currentSlot->copy()->subMinutes($slotDuration);
            $slotEnd = clone $currentSlot;
            
            $isAvailable = true;
            
            // Check if this slot overlaps with any booked slots
            foreach ($bookedSlots as $bookedSlot) {
                $bookedStart = Carbon::parse($bookedSlot->start_time);
                $bookedEnd = Carbon::parse($bookedSlot->end_time);
                
                if (
                    ($slotStart->lt($bookedEnd) && $slotEnd->gt($bookedStart)) // Overlap check
                ) {
                    $isAvailable = false;
                    break;
                }
            }
            
            // Check if this slot overlaps with any breaks
            if ($isAvailable) {
                $breaks = ClinicBreak::where('clinic_id', $clinicId)
                    ->where(function($query) use ($dayOfWeek) {
                        $query->where('day_of_week', $dayOfWeek)
                              ->orWhereNull('day_of_week');
                    })
                    ->get();
                    
                foreach ($breaks as $break) {
                    $breakStart = Carbon::parse($break->start_time);
                    $breakEnd = Carbon::parse($break->end_time);
                    
                    if (
                        ($slotStart->lt($breakEnd) && $slotEnd->gt($breakStart)) // Overlap check
                    ) {
                        $isAvailable = false;
                        break;
                    }
                }
            }
            
            if ($isAvailable) {
                $availableSlots[] = [
                    'start' => $slotStart->format('H:i'),
                    'end' => $slotEnd->format('H:i'),
                    'display_time' => $slotStart->format('g:i A') . ' - ' . $slotEnd->format('g:i A'),
                ];
            }
        }
        
        return response()->json(['data' => ['slots' => $availableSlots]]);
    }
}
