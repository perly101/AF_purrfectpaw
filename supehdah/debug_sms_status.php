<?php

require_once 'vendor/autoload.php';

// Load Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\Appointment;

echo "=== LATEST APPOINTMENT SMS DEBUG ===\n\n";

// Get the latest appointment (any status)
$latestAppointment = Appointment::with(['patient', 'doctor', 'clinic'])
    ->orderBy('updated_at', 'desc')
    ->first();

if (!$latestAppointment) {
    echo "No appointments found.\n";
    exit;
}

// Also get confirmed appointments
$latestConfirmed = Appointment::with(['patient', 'doctor', 'clinic'])
    ->where('status', 'confirmed')
    ->orderBy('updated_at', 'desc')
    ->first();

echo "Latest Appointment (any status):\n";
echo "ID: " . $latestAppointment->id . "\n";
echo "Status: " . $latestAppointment->status . "\n";
echo "Updated: " . $latestAppointment->updated_at . "\n";
echo "Patient Name: " . $latestAppointment->patient_name . "\n";
echo "Patient Phone: " . $latestAppointment->patient_phone . "\n";
echo "Doctor: " . ($latestAppointment->doctor ? $latestAppointment->doctor->name : 'No doctor assigned') . "\n";
echo "Clinic: " . ($latestAppointment->clinic ? $latestAppointment->clinic->clinic_name : 'No clinic found') . "\n";

if ($latestConfirmed) {
    echo "\nLatest Confirmed Appointment:\n";
    echo "ID: " . $latestConfirmed->id . "\n";
    echo "Status: " . $latestConfirmed->status . "\n";
    echo "Updated: " . $latestConfirmed->updated_at . "\n";
    echo "Patient Phone: " . $latestConfirmed->patient_phone . "\n";
} else {
    echo "\nNo confirmed appointments found.\n";
}

// Format phone number like the SMS service does
$phone = $latestAppointment->patient_phone;
$formattedPhone = $phone;

// Remove any non-digit characters
$cleanPhone = preg_replace('/[^\d]/', '', $phone);

// Convert to international format
if (strlen($cleanPhone) == 11 && substr($cleanPhone, 0, 1) == '0') {
    // Philippine mobile number starting with 0
    $formattedPhone = '63' . substr($cleanPhone, 1);
} elseif (strlen($cleanPhone) == 10) {
    // Philippine mobile number without leading 0
    $formattedPhone = '63' . $cleanPhone;
} elseif (strlen($cleanPhone) == 12 && substr($cleanPhone, 0, 2) == '63') {
    // Already in international format without +
    $formattedPhone = $cleanPhone;
}

echo "\nPhone Formatting Check:\n";
echo "Original: $phone\n";
echo "Cleaned: $cleanPhone\n"; 
echo "Formatted for SMS: $formattedPhone\n";

echo "\n=== SMS SERVICE CHECK ===\n";

// Check SMS configuration
echo "SMS_ENABLED: " . (config('sms.enabled') ? 'true' : 'false') . "\n";
echo "SMS_API_KEY: " . (config('sms.semaphore.api_key') ? 'Set' : 'Not set') . "\n";
echo "SMS_SENDER: " . config('sms.semaphore.sender_name', 'Not set') . "\n";

// Test message building
try {
    $smsService = app(\App\Services\SmsService::class);
    $message = $smsService->buildAppointmentConfirmationMessage($latestAppointment);
    
    echo "\nSample SMS Message:\n";
    echo "Length: " . strlen($message) . " characters\n";
    echo "Content:\n" . $message . "\n";
    
} catch (Exception $e) {
    echo "Error building SMS message: " . $e->getMessage() . "\n";
}

?>