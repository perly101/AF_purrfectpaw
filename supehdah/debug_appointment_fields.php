<?php

require_once 'vendor/autoload.php';

// Load Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\Appointment;

echo "=== APPOINTMENT PHONE FIELD DEBUG ===\n\n";

$appointment = Appointment::first();

if ($appointment) {
    echo "Sample Appointment Data:\n";
    echo "ID: " . $appointment->id . "\n";
    echo "patient_phone: " . ($appointment->patient_phone ?? 'NULL') . "\n";
    echo "owner_phone: " . ($appointment->owner_phone ?? 'NULL') . "\n"; 
    
    // Get all columns
    $attributes = $appointment->getAttributes();
    echo "\nAll phone-related fields:\n";
    foreach ($attributes as $key => $value) {
        if (strpos(strtolower($key), 'phone') !== false) {
            echo "$key: " . ($value ?? 'NULL') . "\n";
        }
    }
    
    echo "\nAll appointment fields:\n";
    foreach ($attributes as $key => $value) {
        echo "$key: " . (is_string($value) ? substr($value, 0, 50) : $value) . "\n";
    }
} else {
    echo "No appointments found.\n";
}

?>