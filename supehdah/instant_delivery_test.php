<?php

require_once 'vendor/autoload.php';

// Load Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Services\SmsService;

echo "=== INSTANT SMS DELIVERY VERIFICATION ===\n\n";

$yourPhone = "09632879598";
echo "📱 Testing INSTANT SMS to: $yourPhone\n";
echo "⏰ Start time: " . now()->format('H:i:s.u') . "\n\n";

try {
    $smsService = new SmsService();
    
    // Simple instant message
    $message = "⚡ INSTANT SMS TEST\n";
    $message .= "Time: " . now()->format('H:i:s') . "\n";
    $message .= "This should arrive IMMEDIATELY!\n";
    $message .= "No waiting required.";
    
    echo "🚀 SENDING NOW...\n";
    
    $startTime = microtime(true);
    $result = $smsService->sendSms($yourPhone, $message);
    $endTime = microtime(true);
    
    $milliseconds = round(($endTime - $startTime) * 1000, 2);
    
    echo "⚡ PROCESSING: {$milliseconds}ms\n";
    echo "⏰ End time: " . now()->format('H:i:s.u') . "\n\n";
    
    if ($result['success']) {
        echo "✅ SMS SENT INSTANTLY!\n";
        echo "📧 Message ID: " . ($result['data']['message_id'] ?? 'N/A') . "\n";
        echo "📊 Status: " . ($result['data']['status'] ?? 'Unknown') . "\n\n";
        
        echo "📱 SMS SHOULD ARRIVE NOW - CHECK YOUR PHONE!\n";
        echo "⏱️  Expected delivery: IMMEDIATE (0-30 seconds)\n";
        echo "📲 No waiting required!\n\n";
        
        // Check status meaning
        $status = $result['data']['status'] ?? 'Unknown';
        if ($status === 'Pending') {
            echo "ℹ️  'Pending' means SMS is in Semaphore's delivery queue\n";
            echo "📡 This usually delivers within 5-30 seconds\n";
        }
        
    } else {
        echo "❌ SMS FAILED!\n";
        echo "Error: " . ($result['error'] ?? 'Unknown') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== DELIVERY STATUS CHECK ===\n";
echo "If SMS doesn't arrive within 1 minute, possible issues:\n";
echo "1. Network congestion\n";
echo "2. Phone number format issue\n";
echo "3. SMS blocked by carrier\n";
echo "4. Phone in DND/airplane mode\n";
echo "5. Semaphore API rate limiting\n\n";

echo "💡 TIP: SMS is sent INSTANTLY from our side!\n";
echo "The ~400-700ms is just our processing time.\n";
echo "Actual delivery depends on your mobile network.\n";

?>