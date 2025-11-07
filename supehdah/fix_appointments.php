<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Appointment;

echo "=== FIXING APPOINTMENT DATA CORRUPTION ===\n\n";

// Get users
$pearl = User::where('first_name', 'Pearl')->where('last_name', 'Petallo')->first();
$kaya = User::where('first_name', 'Kaya')->where('last_name', 'Petallo')->first();

echo "Pearl (User {$pearl->id}): {$pearl->first_name} {$pearl->last_name}\n";
echo "Kaya (User {$kaya->id}): {$kaya->first_name} {$kaya->last_name}\n\n";

// Fix appointments based on owner_name
echo "=== FIXING BASED ON OWNER NAME ===\n";

// Pearl's appointments should go to Pearl (user_id 1)
$pearlAppointments = Appointment::where('owner_name', 'Pearl Clava Petallo')->get();
echo "Found " . $pearlAppointments->count() . " appointments for Pearl Clava Petallo\n";

foreach ($pearlAppointments as $apt) {
    echo "  - Updating appointment {$apt->id} to user_id={$pearl->id}\n";
    $apt->user_id = $pearl->id;
    $apt->save();
}

// Kaya's appointments should go to Kaya (user_id 7)  
$kayaAppointments = Appointment::where('owner_name', 'Kaya C. Petallo')->get();
echo "Found " . $kayaAppointments->count() . " appointments for Kaya C. Petallo\n";

foreach ($kayaAppointments as $apt) {
    echo "  - Updating appointment {$apt->id} to user_id={$kaya->id}\n";
    $apt->user_id = $kaya->id;
    $apt->save();
}

echo "\n=== VERIFICATION ===\n";
$appointments = Appointment::select('id', 'user_id', 'owner_name', 'owner_phone')->get();
foreach ($appointments as $apt) {
    echo "ID: {$apt->id} | user_id: " . ($apt->user_id ?: 'NULL') . " | Owner: {$apt->owner_name} | Phone: {$apt->owner_phone}\n";
}

echo "\n=== NOW EACH USER SHOULD SEE ONLY THEIR OWN APPOINTMENTS ===\n";
echo "Pearl (user_id=1): " . Appointment::where('user_id', 1)->count() . " appointments\n";
echo "Kaya (user_id=7): " . Appointment::where('user_id', 7)->count() . " appointments\n";