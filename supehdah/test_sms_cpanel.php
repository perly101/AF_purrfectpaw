<?php
/**
 * SMS Tester for cPanel Linux Terminal
 * 
 * This script tests SMS functionality directly in production environment
 * Run with: php test_sms_cpanel.php
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== SMS TESTER FOR CPANEL PRODUCTION ===\n";
echo "Testing SMS functionality...\n\n";

// Load environment variables if available
if (file_exists('.env')) {
    $envFile = file_get_contents('.env');
    $lines = explode("\n", $envFile);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
    echo "✓ Environment file loaded\n";
} else {
    echo "⚠ No .env file found, using hardcoded values\n";
}

// SMS Configuration (same as SmsService)
$SMS_API_KEY = $_ENV['SMS_API_KEY'] ?? '6dff29a20c4ad21b0ff30725e15c23d0';
$SMS_SENDER_NAME = $_ENV['SMS_SENDER_NAME'] ?? 'AutoRepair';
$SMS_BASE_URL = 'https://semaphore.co/api/v4/messages';

echo "SMS Configuration:\n";
echo "- API Key: " . substr($SMS_API_KEY, 0, 10) . "...\n";
echo "- Sender Name: {$SMS_SENDER_NAME}\n";
echo "- Base URL: {$SMS_BASE_URL}\n\n";

/**
 * Format phone number to Philippine format
 */
function formatPhoneNumber($phoneNumber) {
    $phone = preg_replace('/[^0-9]/', '', $phoneNumber);
    
    if (substr($phone, 0, 1) === '0') {
        $phone = '63' . substr($phone, 1);
    }
    
    if (substr($phone, 0, 2) !== '63') {
        $phone = '63' . $phone;
    }
    
    return $phone;
}

/**
 * Clean message for SMS compatibility
 */
function cleanMessage($message) {
    // Remove emojis and special characters
    $cleanMessage = preg_replace('/[\x{1F600}-\x{1F64F}]/u', '', $message);
    $cleanMessage = preg_replace('/[\x{1F300}-\x{1F5FF}]/u', '', $cleanMessage);
    $cleanMessage = preg_replace('/[\x{1F680}-\x{1F6FF}]/u', '', $cleanMessage);
    $cleanMessage = preg_replace('/[\x{2600}-\x{26FF}]/u', '', $cleanMessage);
    $cleanMessage = preg_replace('/[\x{2700}-\x{27BF}]/u', '', $cleanMessage);
    
    $cleanMessage = str_replace(['•', '▪', '▫', '‣'], '-', $cleanMessage);
    $cleanMessage = str_replace(['📋', '📞', '🐾', '❌'], '', $cleanMessage);
    
    $cleanMessage = mb_convert_encoding($cleanMessage, 'UTF-8', 'UTF-8');
    $cleanMessage = preg_replace('/\n{3,}/', "\n\n", $cleanMessage);
    $cleanMessage = preg_replace('/[ ]{2,}/', ' ', $cleanMessage);
    
    if (strlen($cleanMessage) > 1600) {
        $cleanMessage = substr($cleanMessage, 0, 1597) . '...';
    }
    
    return trim($cleanMessage);
}

/**
 * Send SMS using cURL
 */
function sendSMS($phoneNumber, $message, $apiKey, $senderName, $baseUrl) {
    $formattedPhone = formatPhoneNumber($phoneNumber);
    $cleanMessage = cleanMessage($message);
    
    echo "Sending SMS to: {$formattedPhone}\n";
    echo "Message length: " . strlen($cleanMessage) . " characters\n";
    echo "Message preview: " . substr($cleanMessage, 0, 100) . "...\n\n";
    
    $postData = [
        'apikey' => $apiKey,
        'number' => $formattedPhone,
        'message' => $cleanMessage,
        'sendername' => $senderName
    ];
    
    // Initialize cURL
    $curl = curl_init();
    
    curl_setopt_array($curl, [
        CURLOPT_URL => $baseUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($postData),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded'
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT => 'PurrfectPaw SMS Tester/1.0'
    ]);
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    
    curl_close($curl);
    
    if ($error) {
        return [
            'success' => false,
            'error' => 'cURL Error: ' . $error,
            'http_code' => $httpCode
        ];
    }
    
    $responseData = json_decode($response, true);
    
    return [
        'success' => $httpCode == 200,
        'http_code' => $httpCode,
        'response' => $responseData,
        'raw_response' => $response
    ];
}

// Test Cases
$testCases = [
    [
        'name' => 'Basic SMS Test',
        'phone' => '09123456789', // Change this to your test number
        'message' => 'Test SMS from PurrfectPaw cPanel production server. This is a basic functionality test.'
    ],
    [
        'name' => 'Appointment Confirmation Test',
        'phone' => '09123456789', // Change this to your test number
        'message' => "=== APPOINTMENT CONFIRMED ===\n\nCLINIC: PurrfectPaw Veterinary Clinic\nAPPOINTMENT DETAILS:\nDate: November 8, 2024\nTime: 10:00 AM\nPatient: Fluffy\n\nATTENDING VETERINARIAN:\nDr. John Smith\n(PurrfectPaw Veterinary Clinic)\n\nPREPARATION CHECKLIST:\n- Arrive 15 minutes prior to appointment\n- Bring medical history records\n- List any current medications\n- Prepare questions for the veterinarian\n\nThank you for trusting PurrfectPaw with Fluffy's healthcare.\n\nBest regards,\nPurrfectPaw Team"
    ]
];

echo "Starting SMS Tests...\n";
echo str_repeat("=", 50) . "\n\n";

foreach ($testCases as $index => $testCase) {
    $testNumber = $index + 1;
    echo "TEST {$testNumber}: {$testCase['name']}\n";
    echo str_repeat("-", 30) . "\n";
    
    $result = sendSMS(
        $testCase['phone'],
        $testCase['message'],
        $SMS_API_KEY,
        $SMS_SENDER_NAME,
        $SMS_BASE_URL
    );
    
    if ($result['success']) {
        echo "✅ SUCCESS!\n";
        if (isset($result['response'][0])) {
            $responseData = $result['response'][0];
            echo "Message ID: " . ($responseData['message_id'] ?? 'N/A') . "\n";
            echo "Status: " . ($responseData['status'] ?? 'N/A') . "\n";
            echo "Network: " . ($responseData['network'] ?? 'N/A') . "\n";
            echo "Account Balance: " . ($responseData['account_balance'] ?? 'N/A') . "\n";
        }
    } else {
        echo "❌ FAILED!\n";
        echo "HTTP Code: " . $result['http_code'] . "\n";
        if (isset($result['error'])) {
            echo "Error: " . $result['error'] . "\n";
        }
        if (isset($result['response']['message'])) {
            echo "API Error: " . $result['response']['message'] . "\n";
        }
    }
    
    echo "Raw Response: " . ($result['raw_response'] ?? 'No response') . "\n";
    echo "\n" . str_repeat("=", 50) . "\n\n";
    
    // Wait between tests to avoid rate limiting
    if ($index < count($testCases) - 1) {
        echo "Waiting 3 seconds before next test...\n\n";
        sleep(3);
    }
}

// System Information
echo "SYSTEM INFORMATION:\n";
echo "- PHP Version: " . phpversion() . "\n";
echo "- Server Time: " . date('Y-m-d H:i:s') . "\n";
echo "- Server Timezone: " . date_default_timezone_get() . "\n";
echo "- cURL Available: " . (function_exists('curl_init') ? 'Yes' : 'No') . "\n";
echo "- JSON Available: " . (function_exists('json_encode') ? 'Yes' : 'No') . "\n";
echo "- OpenSSL Available: " . (extension_loaded('openssl') ? 'Yes' : 'No') . "\n\n";

// Connection Test
echo "CONNECTIVITY TEST:\n";
$testUrl = 'https://semaphore.co';
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $testUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_NOBODY => true,
    CURLOPT_SSL_VERIFYPEER => true
]);
$result = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$error = curl_error($curl);
curl_close($curl);

if ($httpCode == 200) {
    echo "✅ Can connect to Semaphore API\n";
} else {
    echo "❌ Cannot connect to Semaphore API\n";
    echo "HTTP Code: {$httpCode}\n";
    if ($error) {
        echo "Error: {$error}\n";
    }
}

echo "\n=== SMS TESTING COMPLETED ===\n";
echo "Instructions for cPanel:\n";
echo "1. Upload this file to your Laravel root directory\n";
echo "2. SSH into your cPanel or use File Manager Terminal\n";
echo "3. Run: php test_sms_cpanel.php\n";
echo "4. Update the phone numbers in the script with real test numbers\n";
echo "5. Check your SMS balance at https://semaphore.co/\n\n";
?>