<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Boot the application
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Appointment;
use App\Models\User;

echo "=== DEBUGGING APPOINTMENT OWNERSHIP ===\n\n";

echo "=== All Appointments in Database ===\n";
$appointments = Appointment::select('id', 'user_id', 'owner_name', 'owner_phone', 'appointment_date', 'status')->get();

foreach ($appointments as $apt) {
    echo "ID: {$apt->id} | user_id: " . ($apt->user_id ?: 'NULL') . " | owner: {$apt->owner_name} | phone: {$apt->owner_phone} | date: {$apt->appointment_date} | status: {$apt->status}\n";
}

echo "\n=== All Users ===\n";
$users = User::select('id', 'first_name', 'middle_name', 'last_name', 'email', 'phone_number')->get();

foreach ($users as $user) {
    $fullName = trim(($user->first_name ?? '') . ' ' . ($user->middle_name ?? '') . ' ' . ($user->last_name ?? ''));
    echo "ID: {$user->id} | Name: {$fullName} | Email: {$user->email} | Phone: {$user->phone_number}\n";
    
    // Show what appointments this user would match
    echo "  -> Appointments by user_id: " . Appointment::where('user_id', $user->id)->count() . "\n";
    if ($user->phone_number) {
        echo "  -> Appointments by phone: " . Appointment::where('owner_phone', $user->phone_number)->count() . "\n";
    }
    if ($fullName) {
        echo "  -> Appointments by name: " . Appointment::where('owner_name', $fullName)->count() . "\n";
    }
    echo "\n";
}