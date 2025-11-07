<?php
/**
 * Quick SMS Test for cPanel
 * Usage: php quick_sms_test.php [phone_number] [message]
 * Example: php quick_sms_test.php 09123456789 "Test message"
 */

// Get command line arguments
$phone = $argv[1] ?? '09632879598'; // Default test number
$message = $argv[2] ?? 'Test SMS from PurrfectPaw production server - ' . date('H:i:s');

echo "=== QUICK SMS TEST ===\n";
echo "Phone: {$phone}\n";
echo "Message: {$message}\n\n";

// SMS API Configuration
$apiKey = '6dff29a20c4ad21b0ff30725e15c23d0';
$senderName = 'AutoRepair';
$baseUrl = 'https://semaphore.co/api/v4/messages';

// Format phone number
function formatPhone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (substr($phone, 0, 1) === '0') {
        $phone = '63' . substr($phone, 1);
    }
    if (substr($phone, 0, 2) !== '63') {
        $phone = '63' . $phone;
    }
    return $phone;
}

$formattedPhone = formatPhone($phone);
echo "Formatted Phone: {$formattedPhone}\n";

// Send SMS
$postData = [
    'apikey' => $apiKey,
    'number' => $formattedPhone,
    'message' => $message,
    'sendername' => $senderName
];

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $baseUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($postData),
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => true
]);

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$error = curl_error($curl);
curl_close($curl);

echo "HTTP Code: {$httpCode}\n";

if ($error) {
    echo "❌ cURL Error: {$error}\n";
} else {
    $responseData = json_decode($response, true);
    if ($httpCode == 200 && isset($responseData[0]['status'])) {
        echo "✅ SMS SENT!\n";
        echo "Message ID: " . ($responseData[0]['message_id'] ?? 'N/A') . "\n";
        echo "Status: " . ($responseData[0]['status'] ?? 'N/A') . "\n";
        echo "Balance: " . ($responseData[0]['account_balance'] ?? 'N/A') . "\n";
    } else {
        echo "❌ SMS FAILED!\n";
        echo "Response: {$response}\n";
    }
}

echo "\nRaw Response:\n{$response}\n";
?>