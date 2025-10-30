<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Doctor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Show the doctor dashboard
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Get the current doctor
        $doctor = Doctor::where('user_id', Auth::id())->firstOrFail();
        
        // Get today's date in Y-m-d format
        $today = Carbon::today()->format('Y-m-d');
        
        // Get today's appointments
        $todayAppointments = Appointment::where('doctor_id', $doctor->id)
            ->whereDate('appointment_date', $today)
            ->orderBy('appointment_time')
            ->get();
            
        // Get upcoming appointments (future dates)
        $upcomingAppointments = Appointment::where('doctor_id', $doctor->id)
            ->whereDate('appointment_date', '>', $today)
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->limit(10)
            ->get();
            
        // Get pending actions (appointments needing attention)
        $pendingActions = Appointment::where('doctor_id', $doctor->id)
            ->where(function($query) {
                $query->where('status', 'assigned')
                      ->orWhere('status', 'completed');
            })
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();
        
        // Count statistics
        $totalPatients = Appointment::where('doctor_id', $doctor->id)
            ->distinct('owner_name', 'owner_phone')
            ->count();
            
        $completedAppointments = Appointment::where('doctor_id', $doctor->id)
            ->whereIn('status', ['completed', 'closed'])
            ->count();
            
        // Count completed appointments that don't yet have consultation notes
        $pendingConsultations = Appointment::where('doctor_id', $doctor->id)
            ->where('status', 'completed')
            ->whereNull('consultation_notes')
            ->count();
            
        return view('doctor.dashboard', compact(
            'doctor',
            'todayAppointments',
            'upcomingAppointments',
            'pendingActions',
            'totalPatients',
            'completedAppointments',
            'pendingConsultations'
        ));
    }
}
