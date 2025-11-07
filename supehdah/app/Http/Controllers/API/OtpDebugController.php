<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\EmailVerificationOtp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OtpDebugController extends Controller
{
    /**
     * Debug endpoint to check OTP status for current user
     * Only available in local/development environment
     */
    public function checkOtpStatus(Request $request)
    {
        // Only allow in development
        if (!app()->environment(['local', 'development'])) {
            return response()->json(['error' => 'Not available in production'], 403);
        }
        
        $user = Auth::user();
        
        // Get all OTP records for this user
        $otpRecords = EmailVerificationOtp::where('user_id', $user->id)->get();
        
        return response()->json([
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'email_verified_at' => $user->email_verified_at
            ],
            'otp_records' => $otpRecords->map(function($record) {
                return [
                    'id' => $record->id,
                    'email' => $record->email,
                    'otp' => $record->otp,
                    'expires_at' => $record->expires_at,
                    'created_at' => $record->created_at,
                    'is_expired' => $record->expires_at->isPast()
                ];
            }),
            'total_otp_records' => $otpRecords->count()
        ]);
    }
}