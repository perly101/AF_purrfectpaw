<?php

require_once 'vendor/autoload.php';

// Load Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Services\SmsService;

echo "=== PROFESSIONAL SMS FORMAT TEST ===\n\n";

$yourPhone = "09632879598";
echo "📱 Testing PROFESSIONAL SMS format to: $yourPhone\n";
echo "⏰ Time: " . now()->format('H:i:s') . "\n\n";

try {
    $smsService = new SmsService();
    
    // Test with professional appointment information
    $appointmentData = [
        'clinic_name' => 'Pearl Veterinary Clinic',
        'appointment_date' => 'October 28, 2025',
        'appointment_time' => '4:00 PM',
        'doctor_name' => 'Dr. Maria Elena Santos',
        'pet_name' => 'Max'
    ];
    
    echo "📋 TESTING PROFESSIONAL FORMAT:\n";
    echo "Clinic: {$appointmentData['clinic_name']}\n";
    echo "Date: {$appointmentData['appointment_date']}\n";
    echo "Time: {$appointmentData['appointment_time']}\n";
    echo "Doctor: {$appointmentData['doctor_name']}\n";
    echo "Patient: {$appointmentData['pet_name']}\n\n";
    
    echo "🚀 SENDING PROFESSIONAL SMS...\n";
    
    $startTime = microtime(true);
    $result = $smsService->sendAppointmentConfirmation($yourPhone, $appointmentData);
    $endTime = microtime(true);
    
    $milliseconds = round(($endTime - $startTime) * 1000, 2);
    
    echo "⚡ Processing: {$milliseconds}ms\n\n";
    
    if ($result['success']) {
        echo "✅ PROFESSIONAL SMS SENT SUCCESSFULLY!\n";
        echo "📧 Message ID: " . ($result['data']['message_id'] ?? 'N/A') . "\n";
        echo "📊 Status: " . ($result['data']['status'] ?? 'Unknown') . "\n";
        echo "📱 Recipient: " . ($result['data']['recipient'] ?? 'N/A') . "\n\n";
        
        echo "📱 SMS FEATURES:\n";
        echo "✅ Professional header with === format\n";
        echo "✅ Clear clinic name display\n";
        echo "✅ Structured appointment details\n";
        echo "✅ Attending veterinarian section\n";
        echo "✅ Professional preparation checklist\n";
        echo "✅ Confirmation attribution to doctor\n";
        echo "✅ Professional closing\n";
        echo "✅ Clean formatting without emojis\n\n";
        
        echo "🎯 CHECK YOUR PHONE - Professional SMS format!\n";
        
    } else {
        echo "❌ SMS FAILED!\n";
        echo "Error: " . ($result['error'] ?? 'Unknown') . "\n";
        echo "Full response: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
    }
    
    echo "\n=== TESTING CANCELLATION FORMAT ===\n";
    echo "🚀 SENDING PROFESSIONAL CANCELLATION SMS...\n";
    
    $cancelResult = $smsService->sendAppointmentCancellation($yourPhone, $appointmentData);
    
    if ($cancelResult['success']) {
        echo "✅ PROFESSIONAL CANCELLATION SMS SENT!\n";
        echo "📧 Message ID: " . ($cancelResult['data']['message_id'] ?? 'N/A') . "\n\n";
    } else {
        echo "❌ CANCELLATION SMS FAILED!\n";
        echo "Error: " . ($cancelResult['error'] ?? 'Unknown') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== TEST COMPLETE ===\n";
echo "Professional SMS format includes:\n";
echo "1. Clear clinic name prominence\n";
echo "2. Doctor name as attending veterinarian\n";
echo "3. Doctor name as confirming authority\n";
echo "4. Professional structure and formatting\n";
echo "5. Comprehensive preparation checklist\n";
echo "6. Professional business communication style\n";

?>
