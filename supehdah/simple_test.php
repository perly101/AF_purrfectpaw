<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Appointment;

echo "=== CHECKING USER APPOINTMENT SEPARATION ===\n\n";

// Check User 1 (Pearl)
echo "User 1 (Pearl) - ID: 1\n";
$user1Appointments = Appointment::where('user_id', 1)->get();
echo "Appointments with user_id=1: " . $user1Appointments->count() . "\n";

// Check User 7 (Kaya) 
echo "\nUser 7 (Kaya) - ID: 7\n";
$user7Appointments = Appointment::where('user_id', 7)->get();
echo "Appointments with user_id=7: " . $user7Appointments->count() . "\n";

// Show all appointments
echo "\n=== ALL APPOINTMENTS ===\n";
$appointments = Appointment::select('id', 'user_id', 'owner_name', 'owner_phone')->get();
foreach ($appointments as $apt) {
    echo "ID: {$apt->id} | user_id: " . ($apt->user_id ?: 'NULL') . " | Owner: {$apt->owner_name} | Phone: {$apt->owner_phone}\n";
}

echo "\n=== EXPECTED RESULTS ===\n";
echo "- User 1 should see 0 appointments (all were transferred to user 7)\n";
echo "- User 7 should see 6 appointments (ID 1-6)\n";
echo "- Appointment ID 7 should stay with NULL user_id until claimed\n";