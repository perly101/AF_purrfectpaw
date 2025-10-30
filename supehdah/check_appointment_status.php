<?php

require_once 'vendor/autoload.php';

// Load Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\Appointment;

echo "=== APPOINTMENT STATUS CHECK ===\n\n";

$appointments = Appointment::where('owner_phone', '09632879598')
    ->orderBy('created_at', 'desc')
    ->take(5)
    ->get(['id', 'status', 'owner_name', 'appointment_date', 'appointment_time']);

echo "Your recent appointments:\n";
foreach($appointments as $apt) {
    echo "ID: {$apt->id}, Status: {$apt->status}, Date: {$apt->appointment_date}, Time: {$apt->appointment_time}\n";
}

echo "\n=== WHAT BUTTONS ARE AVAILABLE ===\n\n";

foreach($appointments as $apt) {
    echo "Appointment ID {$apt->id} (Status: {$apt->status}):\n";
    
    // Check what buttons would be shown based on status
    if ($apt->status === 'assigned') {
        echo "  ✅ Accept/Decline buttons available\n";
    }
    
    if ($apt->status === 'confirmed') {
        echo "  ℹ️  Already confirmed - no action buttons\n";
    }
    
    if (in_array($apt->status, ['assigned', 'confirmed', 'in_progress'])) {
        echo "  🔄 Status change dropdown available\n";
    }
    
    if ($apt->status === 'in_progress') {
        echo "  ✅ Complete Consultation button available\n";
    }
    
    echo "\n";
}

echo "🔍 ISSUE DIAGNOSIS:\n";
echo "If no 'Accept' buttons are available, appointments may already be confirmed.\n";
echo "To test SMS, you need an appointment with status 'assigned' or change status to 'confirmed'.\n";

?>