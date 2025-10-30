<?php

require_once 'vendor/autoload.php';

// Load Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Services\SmsService;
use App\Models\Appointment;

echo "=== INSTANT SMS DELIVERY TEST ===\n";
echo "Testing immediate SMS delivery...\n\n";

// Get the latest appointment to test with
$appointment = Appointment::with(['clinic', 'doctor'])
    ->orderBy('created_at', 'desc')
    ->first();

if (!$appointment) {
    echo "❌ No appointments found to test with\n";
    exit;
}

echo "📱 Testing SMS to: " . ($appointment->owner_phone ?: '(no phone)') . "\n";
echo "🏥 Clinic: " . ($appointment->clinic ? $appointment->clinic->clinic_name : 'Unknown') . "\n";
echo "👨‍⚕️ Doctor: " . ($appointment->doctor ? $appointment->doctor->name : 'No doctor assigned') . "\n";
echo "⏰ Time: " . now()->format('H:i:s') . "\n\n";

if (!$appointment->owner_phone) {
    echo "❌ No phone number to test with\n";
    exit;
}

try {
    $startTime = microtime(true);
    
    $smsService = app(SmsService::class);
    
    $appointmentData = [
        'clinic_name' => $appointment->clinic ? $appointment->clinic->clinic_name : 'PurrfectPaw',
        'appointment_date' => $appointment->appointment_date ? 
            \Carbon\Carbon::parse($appointment->appointment_date)->format('F j, Y') : 'TBD',
        'appointment_time' => $appointment->formatted_time ?: 
            ($appointment->appointment_time ? \Carbon\Carbon::parse($appointment->appointment_time)->format('g:i A') : 'TBD'),
        'doctor_name' => $appointment->doctor ? $appointment->doctor->name : 'Available Doctor',
        'pet_name' => $appointment->owner_name ?: 'your pet'
    ];
    
    echo "🚀 SENDING SMS NOW...\n";
    
    $result = $smsService->sendAppointmentConfirmation($appointment->owner_phone, $appointmentData);
    
    $endTime = microtime(true);
    $duration = round(($endTime - $startTime) * 1000, 2); // milliseconds
    
    echo "\n⚡ SMS PROCESSING TIME: {$duration}ms\n";
    
    if ($result['success']) {
        echo "✅ SMS SENT SUCCESSFULLY!\n";
        echo "📧 Message ID: " . ($result['data']['message_id'] ?? 'N/A') . "\n";
        echo "📊 Status: " . ($result['data']['status'] ?? 'Unknown') . "\n";
        echo "⏰ Sent at: " . now()->format('H:i:s') . "\n";
        echo "📱 To: " . $appointment->owner_phone . "\n";
        echo "\n🎯 SMS DELIVERED IMMEDIATELY - CHECK YOUR PHONE!\n";
    } else {
        echo "❌ SMS FAILED\n";
        echo "Error: " . ($result['message'] ?? 'Unknown error') . "\n";
        if (isset($result['error'])) {
            echo "Details: " . $result['error'] . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== TEST COMPLETE ===\n";

?>