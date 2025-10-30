<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response|\Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function index()
    {
        $doctor = Doctor::where('user_id', auth()->id())->first();
        
        if (!$doctor) {
            return redirect()->route('doctor.dashboard')
                ->with('error', 'No doctor profile found for this user.');
        }
        
        $notifications = $doctor->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate(10);
            
        return view('doctor.notifications.index', compact('notifications'));
    }

    /**
     * Mark a notification as read.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function markAsRead($id)
    {
        $doctor = Doctor::where('user_id', auth()->id())->first();
        
        if (!$doctor) {
            return redirect()->back()->with('error', 'No doctor profile found for this user.');
        }
        
        $notification = $doctor->notifications()->findOrFail($id);
        $notification->markAsRead();
        
        return redirect()->back()->with('success', 'Notification marked as read.');
    }

    /**
     * Mark all notifications as read.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function markAllAsRead()
    {
        $doctor = Doctor::where('user_id', auth()->id())->first();
        
        if (!$doctor) {
            return redirect()->back()->with('error', 'No doctor profile found for this user.');
        }
        
        $doctor->notifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
            
        return redirect()->back()->with('success', 'All notifications marked as read.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $doctor = Doctor::where('user_id', auth()->id())->first();
        
        if (!$doctor) {
            return redirect()->back()->with('error', 'No doctor profile found for this user.');
        }
        
        $notification = $doctor->notifications()->findOrFail($id);
        $notification->delete();
        
        return redirect()->back()->with('success', 'Notification deleted successfully.');
    }

    /**
     * Check for new notifications since a certain time
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkNewNotifications(Request $request)
    {
        $doctor = Doctor::where('user_id', auth()->id())->first();
        
        if (!$doctor) {
            return response()->json(['error' => 'No doctor profile found for this user'], 404);
        }
        
        $lastCheck = $request->input('last_check', null);
        
        $query = $doctor->notifications()
            ->orderBy('created_at', 'desc');
            
        if ($lastCheck) {
            $query->where('created_at', '>', $lastCheck);
        }
        
        $notifications = $query->get();
        
        return response()->json([
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => $doctor->notifications()->whereNull('read_at')->count(),
            'last_check' => now()->toISOString()
        ]);
    }

    /**
     * Get unread notifications count
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getNotificationCount()
    {
        $doctor = Doctor::where('user_id', auth()->id())->first();
        
        if (!$doctor) {
            return response()->json(['error' => 'No doctor profile found for this user'], 404);
        }
        
        $count = $doctor->notifications()->whereNull('read_at')->count();
        
        return response()->json(['count' => $count]);
    }
}