<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Bootstrap Laravel app
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Check OTP records in database
echo "=== Checking OTP Records ===\n";

try {
    $otps = DB::table('email_verification_otps')->get();
    echo "Total OTP records: " . $otps->count() . "\n";
    
    foreach ($otps as $otp) {
        echo "ID: {$otp->id}, User ID: {$otp->user_id}, Email: {$otp->email}, OTP: {$otp->otp}, Expires: {$otp->expires_at}\n";
    }
    
    echo "\n=== Checking Users ===\n";
    $users = DB::table('users')->select('id', 'email', 'email_verified_at', 'created_at')->get();
    echo "Total users: " . $users->count() . "\n";
    
    foreach ($users as $user) {
        echo "ID: {$user->id}, Email: {$user->email}, Verified: " . ($user->email_verified_at ? 'Yes' : 'No') . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}