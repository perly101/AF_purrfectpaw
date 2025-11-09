<?php
/**
 * URGENT SMS TEST - Quick test for appointment confirmation
 */

// Bootstrap Laravel
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🚨 URGENT SMS TEST - APPOINTMENT CONFIRMATION 🚨\n\n";

// Your test phone number - CHANGE THIS TO YOUR ACTUAL NUMBER
$testPhone = '09632879598'; // PUT YOUR ACTUAL PHONE NUMBER HERE

try {
    $smsService = new App\Services\SmsService();
    echo "✅ SMS Service loaded\n";
    
    // Test appointment confirmation message (short version)
    $appointmentData = [
        'clinic_name' => 'PurrfectPaw Veterinary Clinic',
        'appointment_date' => 'November 9, 2025',
        'appointment_time' => '10:00 AM',
        'doctor_name' => 'Dr. Sarah Cruz',
        'pet_name' => 'Bella'
    ];
    
    echo "📱 Sending SMS to: {$testPhone}\n";
    echo "🔑 Using API Key: " . substr(env('SMS_API_KEY'), 0, 10) . "...\n\n";
    
    // Preview the message first
    $message = $smsService->buildAppointmentConfirmationMessage($appointmentData);
    echo "📝 Message to be sent:\n";
    echo "\"" . $message . "\"\n\n";
    echo "📏 Message length: " . strlen($message) . " characters\n\n";
    
    // Send the actual SMS
    echo "🚀 SENDING SMS NOW...\n";
    $result = $smsService->sendAppointmentConfirmation($testPhone, $appointmentData);
    
    if ($result['success']) {
        echo "✅ SMS SENT SUCCESSFULLY!\n";
        echo "📧 Message ID: " . ($result['data']['message_id'] ?? 'N/A') . "\n";
        echo "📊 Status: " . ($result['data']['status'] ?? 'N/A') . "\n";
        echo "🎯 Check your phone now!\n";
    } else {
        echo "❌ SMS FAILED!\n";
        echo "💥 Error: " . ($result['error'] ?? 'Unknown error') . "\n";
        if (isset($result['full_response'])) {
            echo "🔍 Full Response: " . print_r($result['full_response'], true) . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
    echo "📍 File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "⚡ URGENT TEST COMPLETE ⚡\n";
echo "If you received the SMS, the system is working!\n";
echo "If not, check the error messages above.\n";
echo str_repeat("=", 50) . "\n";
?>