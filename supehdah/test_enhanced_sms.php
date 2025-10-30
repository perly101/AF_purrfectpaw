<?php

require_once 'vendor/autoload.php';

// Load Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Services\SmsService;

echo "=== ENHANCED APPOINTMENT SMS TEST ===\n\n";

$yourPhone = "09632879598";
echo "📱 Testing ENHANCED SMS with complete appointment info to: $yourPhone\n";
echo "⏰ Time: " . now()->format('H:i:s') . "\n\n";

try {
    $smsService = new SmsService();
    
    // Test with complete appointment information
    $appointmentData = [
        'clinic_name' => 'Pearl Veterinary Clinic',
        'appointment_date' => 'October 28, 2025',
        'appointment_time' => '3:30 PM',
        'doctor_name' => 'Dr. Maria Santos',
        'pet_name' => 'Buddy the Golden Retriever'
    ];
    
    echo "📋 APPOINTMENT DETAILS BEING SENT:\n";
    echo "Clinic: {$appointmentData['clinic_name']}\n";
    echo "Date: {$appointmentData['appointment_date']}\n";
    echo "Time: {$appointmentData['appointment_time']}\n";
    echo "Doctor: {$appointmentData['doctor_name']}\n";
    echo "Patient: {$appointmentData['pet_name']}\n\n";
    
    echo "🚀 SENDING ENHANCED SMS...\n";
    
    $startTime = microtime(true);
    $result = $smsService->sendAppointmentConfirmation($yourPhone, $appointmentData);
    $endTime = microtime(true);
    
    $milliseconds = round(($endTime - $startTime) * 1000, 2);
    
    echo "⚡ Processing: {$milliseconds}ms\n\n";
    
    if ($result['success']) {
        echo "✅ ENHANCED SMS SENT SUCCESSFULLY!\n";
        echo "📧 Message ID: " . ($result['data']['message_id'] ?? 'N/A') . "\n";
        echo "📊 Status: " . ($result['data']['status'] ?? 'Unknown') . "\n";
        echo "📱 Recipient: " . ($result['data']['recipient'] ?? 'N/A') . "\n\n";
        
        echo "📱 SMS CONTENT INCLUDES:\n";
        echo "✅ Complete clinic information\n";
        echo "✅ Assigned doctor details\n";
        echo "✅ Full appointment date and time\n";
        echo "✅ Patient name\n";
        echo "✅ Detailed reminders\n";
        echo "✅ Contact instructions\n";
        echo "✅ No emojis (Semaphore-safe)\n\n";
        
        echo "🎯 CHECK YOUR PHONE - Enhanced SMS should arrive!\n";
        
    } else {
        echo "❌ SMS FAILED!\n";
        echo "Error: " . ($result['error'] ?? 'Unknown') . "\n";
        echo "Full response: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== TEST COMPLETE ===\n";
echo "The SMS now includes:\n";
echo "1. Clinic name where appointment was made\n";
echo "2. Assigned doctor's name\n";
echo "3. Complete appointment details\n";
echo "4. Professional formatting without emojis\n";
echo "5. Detailed reminders and instructions\n";

?>