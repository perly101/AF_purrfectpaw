<?php

require_once 'vendor/autoload.php';

// Load Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Services\SmsService;

echo "=== CLEANED SMS TEST (NO REFUNDS) ===\n\n";

$yourPhone = "09632879598";
echo "📱 Testing CLEANED SMS to: $yourPhone\n";
echo "⏰ Time: " . now()->format('H:i:s') . "\n\n";

try {
    $smsService = new SmsService();
    
    // Test appointment confirmation with cleaned message
    $appointmentData = [
        'clinic_name' => 'Pearl Veterinary Clinic',
        'appointment_date' => 'October 28, 2025',
        'appointment_time' => '2:00 PM',
        'doctor_name' => 'Dr. Smith',
        'pet_name' => 'Fluffy'
    ];
    
    echo "🧹 TESTING CLEANED APPOINTMENT MESSAGE...\n";
    
    $startTime = microtime(true);
    $result = $smsService->sendAppointmentConfirmation($yourPhone, $appointmentData);
    $endTime = microtime(true);
    
    $milliseconds = round(($endTime - $startTime) * 1000, 2);
    
    echo "⚡ Processing: {$milliseconds}ms\n\n";
    
    if ($result['success']) {
        echo "✅ CLEANED SMS SENT SUCCESSFULLY!\n";
        echo "📧 Message ID: " . ($result['data']['message_id'] ?? 'N/A') . "\n";
        echo "📊 Status: " . ($result['data']['status'] ?? 'Unknown') . "\n";
        echo "📱 Recipient: " . ($result['data']['recipient'] ?? 'N/A') . "\n\n";
        
        echo "🎯 CHECK SEMAPHORE DASHBOARD:\n";
        echo "✅ Status should be 'Sent' or 'Delivered' (NOT refunded)\n";
        echo "✅ Message should be visible and complete\n";
        echo "✅ No emojis or special characters\n\n";
        
        echo "📱 CHECK YOUR PHONE - SMS should arrive clean!\n";
        
    } else {
        echo "❌ SMS FAILED!\n";
        echo "Error: " . ($result['error'] ?? 'Unknown') . "\n";
        echo "Full response: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== VERIFICATION ===\n";
echo "1. Check Semaphore dashboard - should NOT be refunded\n";
echo "2. Message should be readable without emojis\n";
echo "3. Status should be Sent/Delivered\n";
echo "4. SMS should arrive on your phone\n";

?>