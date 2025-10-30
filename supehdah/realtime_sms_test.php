<?php

require_once 'vendor/autoload.php';

// Load Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Services\SmsService;

echo "=== REAL-TIME SMS VERIFICATION TEST ===\n\n";

// Test with your exact phone number
$yourPhone = "09632879598";
echo "📱 Testing SMS to YOUR PHONE: $yourPhone\n";
echo "⏰ Current time: " . now()->format('H:i:s') . "\n\n";

try {
    $smsService = new SmsService();
    
    // Create a simple test message
    $testMessage = "🧪 TEST SMS from PurrfectPaw\n\n";
    $testMessage .= "Time: " . now()->format('H:i:s') . "\n";
    $testMessage .= "If you receive this, SMS is working!\n";
    $testMessage .= "Reply 'OK' to confirm delivery.";
    
    echo "📤 Sending test SMS now...\n";
    
    $startTime = microtime(true);
    $result = $smsService->sendSms($yourPhone, $testMessage);
    $endTime = microtime(true);
    
    $processingTime = round(($endTime - $startTime) * 1000, 2);
    
    echo "⚡ Processing time: {$processingTime}ms\n\n";
    
    if ($result['success']) {
        echo "✅ SMS SENT SUCCESSFULLY!\n";
        echo "📧 Message ID: " . ($result['data']['message_id'] ?? 'N/A') . "\n";
        echo "📊 Status: " . ($result['data']['status'] ?? 'Unknown') . "\n";
        echo "🌐 Network: " . ($result['data']['network'] ?? 'Unknown') . "\n";
        echo "📱 Recipient: " . ($result['data']['recipient'] ?? 'N/A') . "\n";
        echo "⏰ Sent at: " . now()->format('H:i:s') . "\n\n";
        
        echo "🎯 CHECK YOUR PHONE NOW!\n";
        echo "📲 The SMS should arrive within 1-5 minutes\n";
        echo "⚠️  If not received, check:\n";
        echo "   • Phone signal strength\n";
        echo "   • SMS/Spam folder\n";
        echo "   • Network provider delays\n";
        echo "   • Phone number accuracy: $yourPhone\n\n";
        
        // Check Semaphore status meanings
        $status = $result['data']['status'] ?? 'Unknown';
        echo "📋 STATUS MEANING:\n";
        switch (strtolower($status)) {
            case 'pending':
                echo "   'Pending' = SMS is queued and will be delivered shortly\n";
                break;
            case 'sent':
                echo "   'Sent' = SMS has been delivered to your network\n";
                break;
            case 'queued':
                echo "   'Queued' = SMS is waiting in line for delivery\n";
                break;
            default:
                echo "   '$status' = Check Semaphore documentation\n";
        }
        
    } else {
        echo "❌ SMS FAILED!\n";
        echo "Error: " . ($result['error'] ?? 'Unknown error') . "\n";
        echo "Response: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== TEST COMPLETE ===\n";
echo "💡 TIP: If SMS arrives, then doctor confirmations should work too!\n";

?>