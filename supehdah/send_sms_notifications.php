<?php
// Quick script to check recent appointments and prepare SMS sending
require_once 'vendor/autoload.php';

// Bootstrap Laravel and load environment
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Ensure .env is loaded
if (file_exists('.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

use App\Models\Appointment;
use App\Services\SmsService;
use Carbon\Carbon;

echo "=== PurrfectPaw SMS Notification System ===\n\n";

// Get recent appointments that need SMS notifications
echo "Checking recent confirmed/cancelled appointments with phone numbers...\n\n";

$appointments = Appointment::whereNotNull('owner_phone')
    ->whereIn('status', ['confirmed', 'cancelled'])
    ->where('updated_at', '>=', Carbon::today()->subDays(7)) // Last 7 days
    ->orderBy('updated_at', 'desc')
    ->limit(20)
    ->get(['id', 'owner_name', 'owner_phone', 'status', 'appointment_date', 'appointment_time', 'updated_at']);

if ($appointments->isEmpty()) {
    echo "No recent confirmed/cancelled appointments found with phone numbers.\n";
    exit(0);
}

echo "Found " . $appointments->count() . " appointments:\n";
echo str_repeat("-", 80) . "\n";

foreach ($appointments as $apt) {
    $updatedAt = Carbon::parse($apt->updated_at)->format('M j, Y H:i');
    echo sprintf("ID: %d | %s | %s | %s | %s %s | Updated: %s\n",
        $apt->id,
        $apt->owner_name,
        $apt->owner_phone,
        strtoupper($apt->status),
        $apt->appointment_date,
        $apt->appointment_time,
        $updatedAt
    );
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "SMS Configuration Check:\n";
echo "SMS_ENABLED: " . (env('SMS_ENABLED', false) ? 'true' : 'false') . "\n";
echo "SMS_API_KEY: " . (env('SMS_API_KEY') ? 'Set (length: ' . strlen(env('SMS_API_KEY')) . ')' : 'Not set') . "\n";
echo "SMS_SENDER_NAME: " . (env('SMS_SENDER_NAME') ?: 'Not set') . "\n\n";

// Ask user which appointments to send SMS for
echo "Options:\n";
echo "1. Send SMS to ALL appointments above\n";
echo "2. Send SMS to specific appointment ID (enter ID)\n";
echo "3. Preview message for appointment ID (enter 'preview ID')\n";
echo "4. Exit\n\n";

echo "Enter choice (1/2/3/4 or appointment ID): ";
$input = trim(fgets(STDIN));

$smsService = new SmsService();

switch ($input) {
    case '1':
        echo "\nSending SMS to all " . $appointments->count() . " appointments...\n";
        $successCount = 0;
        $failCount = 0;
        
        foreach ($appointments as $apt) {
            echo "Processing ID {$apt->id} ({$apt->owner_name})...";
            
            $appointmentData = [
                'clinic_name' => 'PurrfectPaw Veterinary Clinic',
                'appointment_date' => Carbon::parse($apt->appointment_date)->format('F j, Y'),
                'appointment_time' => Carbon::parse($apt->appointment_time)->format('g:i A'),
                'doctor_name' => 'Available Doctor',
                'pet_name' => $apt->owner_name
            ];
            
            if ($apt->status === 'confirmed') {
                $result = $smsService->sendAppointmentConfirmation($apt->owner_phone, $appointmentData);
            } else {
                $result = $smsService->sendAppointmentCancellation($apt->owner_phone, $appointmentData);
            }
            
            if ($result['success']) {
                echo " ✓ Sent\n";
                $successCount++;
            } else {
                echo " ✗ Failed: " . $result['error'] . "\n";
                $failCount++;
            }
            
            // Small delay to avoid rate limiting
            sleep(1);
        }
        
        echo "\nSummary: {$successCount} sent, {$failCount} failed\n";
        break;
        
    case '3':
    case (strpos($input, 'preview') === 0):
        $parts = explode(' ', $input);
        $id = isset($parts[1]) ? (int)$parts[1] : 0;
        
        if ($id > 0) {
            $apt = $appointments->where('id', $id)->first();
            if ($apt) {
                $appointmentData = [
                    'clinic_name' => 'PurrfectPaw Veterinary Clinic',
                    'appointment_date' => Carbon::parse($apt->appointment_date)->format('F j, Y'),
                    'appointment_time' => Carbon::parse($apt->appointment_time)->format('g:i A'),
                    'doctor_name' => 'Available Doctor',
                    'pet_name' => $apt->owner_name
                ];
                
                echo "\nPreview for appointment ID {$id}:\n";
                echo "To: {$apt->owner_phone}\n";
                echo "Status: {$apt->status}\n";
                echo str_repeat("-", 50) . "\n";
                
                if ($apt->status === 'confirmed') {
                    $message = $smsService->buildAppointmentConfirmationMessage($appointmentData);
                    echo "CONFIRMATION MESSAGE:\n";
                } else {
                    $message = $smsService->buildAppointmentCancellationMessage($appointmentData);
                    echo "CANCELLATION MESSAGE:\n";
                }
                echo $message . "\n";
                echo str_repeat("-", 50) . "\n";
            } else {
                echo "Appointment ID {$id} not found in the list.\n";
            }
        } else {
            echo "Please specify an appointment ID: preview 123\n";
        }
        break;
        
    case '4':
        echo "Exiting...\n";
        exit(0);
        
    default:
        // Assume it's an appointment ID
        $id = (int)$input;
        if ($id > 0) {
            $apt = $appointments->where('id', $id)->first();
            if ($apt) {
                echo "\nSending SMS to appointment ID {$id} ({$apt->owner_name})...\n";
                
                $appointmentData = [
                    'clinic_name' => 'PurrfectPaw Veterinary Clinic',
                    'appointment_date' => Carbon::parse($apt->appointment_date)->format('F j, Y'),
                    'appointment_time' => Carbon::parse($apt->appointment_time)->format('g:i A'),
                    'doctor_name' => 'Available Doctor',
                    'pet_name' => $apt->owner_name
                ];
                
                if ($apt->status === 'confirmed') {
                    $result = $smsService->sendAppointmentConfirmation($apt->owner_phone, $appointmentData);
                } else {
                    $result = $smsService->sendAppointmentCancellation($apt->owner_phone, $appointmentData);
                }
                
                if ($result['success']) {
                    echo "✓ SMS sent successfully!\n";
                    echo "Message ID: " . ($result['data']['message_id'] ?? 'N/A') . "\n";
                } else {
                    echo "✗ Failed to send SMS: " . $result['error'] . "\n";
                }
            } else {
                echo "Appointment ID {$id} not found in the list.\n";
            }
        } else {
            echo "Invalid choice. Please enter 1, 2, 3, 4, or an appointment ID.\n";
        }
        break;
}

echo "\nDone.\n";