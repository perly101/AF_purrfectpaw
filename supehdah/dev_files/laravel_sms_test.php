<?php
/**
 * Laravel SMS Service Tester for cPanel
 * 
 * This script tests SMS functionality using Laravel's SmsService
 * Run from Laravel root: php laravel_sms_test.php
 */

// Check if we're in Laravel root
if (!file_exists('artisan')) {
    echo "❌ Error: This script must be run from Laravel root directory\n";
    echo "Current directory: " . getcwd() . "\n";
    echo "Please navigate to your Laravel root and run: php laravel_sms_test.php\n";
    exit(1);
}

echo "=== LARAVEL SMS SERVICE TESTER ===\n";
echo "Testing SMS functionality with Laravel SmsService...\n\n";

// Bootstrap Laravel
require_once 'vendor/autoload.php';

try {
    $app = require_once 'bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    echo "✅ Laravel bootstrapped successfully\n";
} catch (Exception $e) {
    echo "❌ Failed to bootstrap Laravel: " . $e->getMessage() . "\n";
    exit(1);
}

// Test SmsService
try {
    $smsService = new App\Services\SmsService();
    echo "✅ SmsService instantiated successfully\n\n";
    
    // Test phone number - CHANGE THIS TO YOUR REAL NUMBER
    $testPhone = '09632879598'; // UPDATE THIS WITH YOUR ACTUAL PHONE NUMBER
    
    echo "📱 Testing with phone number: {$testPhone}\n";
    echo "⚠️  Make sure to update the phone number above to your actual number!\n\n";
    
    // Test 1: Basic SMS
    echo "TEST 1: Basic SMS\n";
    echo str_repeat("-", 20) . "\n";
    
    $basicMessage = "Hello from PurrfectPaw production server! This is a Laravel SmsService test at " . date('Y-m-d H:i:s');
    
    $result1 = $smsService->sendSms($testPhone, $basicMessage);
    
    if ($result1['success']) {
        echo "✅ Basic SMS sent successfully!\n";
        echo "Message ID: " . ($result1['data']['message_id'] ?? 'N/A') . "\n";
        echo "Status: " . ($result1['data']['status'] ?? 'N/A') . "\n";
    } else {
        echo "❌ Basic SMS failed!\n";
        echo "Error: " . ($result1['message'] ?? 'Unknown error') . "\n";
        if (isset($result1['error'])) {
            echo "Details: " . $result1['error'] . "\n";
        }
    }
    
    echo "\n" . str_repeat("=", 50) . "\n\n";
    
    // Wait before next test
    sleep(3);
    
    // Test 2: Appointment Confirmation SMS
    echo "TEST 2: Appointment Confirmation SMS\n";
    echo str_repeat("-", 35) . "\n";
    
    $appointmentData = [
        'clinic_name' => 'PurrfectPaw Veterinary Clinic',
        'appointment_date' => date('Y-m-d', strtotime('+1 day')),
        'appointment_time' => '10:00 AM',
        'doctor_name' => 'Dr. Sarah Johnson',
        'pet_name' => 'Fluffy'
    ];
    
    $result2 = $smsService->sendAppointmentConfirmation($testPhone, $appointmentData);
    
    if ($result2['success']) {
        echo "✅ Appointment confirmation SMS sent successfully!\n";
        echo "Message ID: " . ($result2['data']['message_id'] ?? 'N/A') . "\n";
        echo "Status: " . ($result2['data']['status'] ?? 'N/A') . "\n";
    } else {
        echo "❌ Appointment confirmation SMS failed!\n";
        echo "Error: " . ($result2['message'] ?? 'Unknown error') . "\n";
        if (isset($result2['error'])) {
            echo "Details: " . $result2['error'] . "\n";
        }
    }
    
    echo "\n" . str_repeat("=", 50) . "\n\n";
    
    // Wait before next test
    sleep(3);
    
    // Test 3: Message Preview (without sending)
    echo "TEST 3: Message Preview (No SMS sent)\n";
    echo str_repeat("-", 38) . "\n";
    
    $previewMessage = $smsService->buildAppointmentConfirmationMessage($appointmentData);
    echo "Generated message preview:\n";
    echo str_repeat("-", 25) . "\n";
    echo $previewMessage . "\n";
    echo str_repeat("-", 25) . "\n";
    echo "Message length: " . strlen($previewMessage) . " characters\n";
    
    echo "\n" . str_repeat("=", 50) . "\n\n";
    
    // Test 4: Cancellation Message Preview
    echo "TEST 4: Cancellation Message Preview\n";
    echo str_repeat("-", 36) . "\n";
    
    $cancellationMessage = $smsService->buildAppointmentCancellationMessage($appointmentData);
    echo "Generated cancellation message:\n";
    echo str_repeat("-", 31) . "\n";
    echo $cancellationMessage . "\n";
    echo str_repeat("-", 31) . "\n";
    echo "Message length: " . strlen($cancellationMessage) . " characters\n";
    
} catch (Exception $e) {
    echo "❌ Error testing SmsService: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

// Environment Info
echo "\n" . str_repeat("=", 50) . "\n";
echo "ENVIRONMENT INFORMATION:\n";
echo "- Laravel Version: " . app()->version() . "\n";
echo "- PHP Version: " . phpversion() . "\n";
echo "- Environment: " . config('app.env') . "\n";
echo "- Debug Mode: " . (config('app.debug') ? 'Enabled' : 'Disabled') . "\n";
echo "- Timezone: " . config('app.timezone') . "\n";
echo "- Current Time: " . now()->format('Y-m-d H:i:s') . "\n";

// Check SMS configuration
echo "\nSMS CONFIGURATION CHECK:\n";
$smsApiKey = env('SMS_API_KEY');
$smsSenderName = env('SMS_SENDER_NAME');

echo "- SMS API Key: " . ($smsApiKey ? 'Set (' . substr($smsApiKey, 0, 10) . '...)' : 'Using default') . "\n";
echo "- SMS Sender Name: " . ($smsSenderName ? $smsSenderName : 'Using default (AutoRepair)') . "\n";

echo "\n=== TESTING COMPLETED ===\n\n";

// Instructions
echo "INSTRUCTIONS FOR CPANEL:\n";
echo "1. Upload this file to your Laravel root directory on cPanel\n";
echo "2. Update the \$testPhone variable with your real phone number\n";
echo "3. SSH into cPanel or use File Manager Terminal\n";
echo "4. Navigate to your Laravel root directory\n";
echo "5. Run: php laravel_sms_test.php\n";
echo "6. Check your phone for SMS messages\n";
echo "7. Monitor your Semaphore account balance\n\n";

echo "IMPORTANT NOTES:\n";
echo "- Make sure your .env file has correct SMS credentials\n";
echo "- Verify that your server can make HTTPS connections\n";
echo "- Check Laravel logs in storage/logs/ for detailed information\n";
echo "- SMS costs will be deducted from your Semaphore account\n\n";
?>