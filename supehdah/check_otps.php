<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use App\Models\EmailVerificationOtp;
use App\Models\User;

echo "=== Checking OTP Records ===\n";
$otps = EmailVerificationOtp::with('user')->get();
echo "Total OTP records: " . $otps->count() . "\n\n";

foreach ($otps as $otp) {
    echo "OTP ID: {$otp->id}\n";
    echo "User ID: {$otp->user_id}\n"; 
    echo "User Email: {$otp->email}\n";
    echo "User Name: " . ($otp->user ? $otp->user->name : 'N/A') . "\n";
    echo "OTP Code: {$otp->otp}\n";
    echo "Expires: {$otp->expires_at}\n";
    echo "Created: {$otp->created_at}\n";
    echo "Expired: " . ($otp->expires_at->isPast() ? 'Yes' : 'No') . "\n";
    echo "---\n";
}

echo "\n=== Recent Users ===\n";
$users = User::orderBy('created_at', 'desc')->take(5)->get();
foreach ($users as $user) {
    echo "User ID: {$user->id}, Email: {$user->email}, Name: {$user->name}\n";
    echo "Verified: " . ($user->email_verified_at ? 'Yes' : 'No') . "\n";
    echo "Created: {$user->created_at}\n";
    echo "---\n";
}