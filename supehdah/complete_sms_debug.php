<?php

require_once 'vendor/autoload.php';

// Load Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\Appointment;
use App\Services\SmsService;

echo "=== COMPLETE SMS DEBUGGING ===\n\n";

// 1. Check SMS Configuration
echo "1. SMS CONFIGURATION CHECK:\n";
echo "SMS_ENABLED: " . env('SMS_ENABLED', 'not set') . "\n";
echo "SMS_API_KEY: " . (env('SMS_API_KEY') ? 'SET (' . substr(env('SMS_API_KEY'), 0, 10) . '...)' : 'NOT SET') . "\n";
echo "SMS_SENDER_NAME: " . env('SMS_SENDER_NAME', 'not set') . "\n\n";

// 2. Get latest appointment
echo "2. APPOINTMENT DATA:\n";
$appointment = Appointment::with(['clinic', 'doctor'])
    ->where('owner_phone', '!=', '')
    ->whereNotNull('owner_phone')
    ->orderBy('created_at', 'desc')
    ->first();

if (!$appointment) {
    echo "❌ No appointments with phone numbers found!\n";
    exit;
}

echo "Appointment ID: " . $appointment->id . "\n";
echo "Status: " . $appointment->status . "\n";
echo "Owner Phone: " . $appointment->owner_phone . "\n";
echo "Owner Name: " . $appointment->owner_name . "\n";
echo "Clinic: " . ($appointment->clinic ? $appointment->clinic->clinic_name : 'No clinic') . "\n";
echo "Doctor: " . ($appointment->doctor ? $appointment->doctor->name : 'No doctor') . "\n";
echo "Date: " . $appointment->appointment_date . "\n";
echo "Time: " . $appointment->appointment_time . "\n\n";

// 3. Test SMS Service Initialization
echo "3. SMS SERVICE TEST:\n";
try {
    $smsService = new SmsService();
    echo "✅ SmsService created successfully\n";
} catch (Exception $e) {
    echo "❌ Failed to create SmsService: " . $e->getMessage() . "\n";
    exit;
}

// 4. Test message building
echo "\n4. MESSAGE BUILDING TEST:\n";
try {
    $appointmentData = [
        'clinic_name' => $appointment->clinic ? $appointment->clinic->clinic_name : 'PurrfectPaw',
        'appointment_date' => $appointment->appointment_date ? 
            \Carbon\Carbon::parse($appointment->appointment_date)->format('F j, Y') : 'TBD',
        'appointment_time' => $appointment->formatted_time ?: 
            ($appointment->appointment_time ? \Carbon\Carbon::parse($appointment->appointment_time)->format('g:i A') : 'TBD'),
        'doctor_name' => $appointment->doctor ? $appointment->doctor->name : 'Available Doctor',
        'pet_name' => $appointment->owner_name ?: 'your pet'
    ];
    
    $message = $smsService->buildAppointmentConfirmationMessage($appointmentData);
    echo "✅ Message built successfully\n";
    echo "Message length: " . strlen($message) . " characters\n";
    echo "Message preview:\n" . substr($message, 0, 100) . "...\n\n";
} catch (Exception $e) {
    echo "❌ Failed to build message: " . $e->getMessage() . "\n";
    exit;
}

// 5. Test phone formatting
echo "5. PHONE FORMATTING TEST:\n";
$originalPhone = $appointment->owner_phone;
echo "Original phone: $originalPhone\n";

// Use reflection to access private method
$reflection = new ReflectionClass($smsService);
$formatMethod = $reflection->getMethod('formatPhoneNumber');
$formatMethod->setAccessible(true);
$formattedPhone = $formatMethod->invoke($smsService, $originalPhone);

echo "Formatted phone: $formattedPhone\n\n";

// 6. Test actual SMS sending
echo "6. ACTUAL SMS SENDING TEST:\n";
echo "Sending SMS to: $formattedPhone\n";
echo "Time: " . now()->format('H:i:s') . "\n";

try {
    $startTime = microtime(true);
    $result = $smsService->sendAppointmentConfirmation($appointment->owner_phone, $appointmentData);
    $endTime = microtime(true);
    
    $duration = round(($endTime - $startTime) * 1000, 2);
    
    echo "Processing time: {$duration}ms\n";
    echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
    
    if ($result['success']) {
        echo "\n✅ SMS SENT SUCCESSFULLY!\n";
        echo "Message ID: " . ($result['data']['message_id'] ?? 'N/A') . "\n";
        echo "Status: " . ($result['data']['status'] ?? 'Unknown') . "\n";
        echo "\n🎯 CHECK YOUR PHONE NOW!\n";
    } else {
        echo "\n❌ SMS FAILED!\n";
        echo "Error: " . ($result['error'] ?? 'Unknown error') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Exception during SMS sending: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== DEBUG COMPLETE ===\n";

?>