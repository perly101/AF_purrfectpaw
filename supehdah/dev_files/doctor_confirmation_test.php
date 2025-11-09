<?php
/**
 * TEST DOCTOR APPOINTMENT CONFIRMATION SMS
 * 
 * This simulates what happens when a doctor confirms an appointment
 */

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🩺 DOCTOR APPOINTMENT CONFIRMATION SMS TEST 🩺\n\n";

// Test phone number
$testPhone = '09632879598'; // Your phone number

try {
    $smsService = new App\Services\SmsService();
    
    // Simulate what the doctor dashboard does when confirming
    $appointmentData = [
        'clinic_name' => 'PurrfectPaw Veterinary Clinic',
        'appointment_date' => \Carbon\Carbon::now()->addDay()->format('F j, Y'),
        'appointment_time' => '2:00 PM',
        'doctor_name' => 'Dr. Maria Santos',
        'pet_name' => 'Max'
    ];
    
    echo "📋 Simulating doctor confirmation...\n";
    echo "👩‍⚕️ Doctor: " . $appointmentData['doctor_name'] . "\n";
    echo "🏥 Clinic: " . $appointmentData['clinic_name'] . "\n";
    echo "📅 Date: " . $appointmentData['appointment_date'] . "\n";
    echo "⏰ Time: " . $appointmentData['appointment_time'] . "\n";
    echo "🐕 Pet: " . $appointmentData['pet_name'] . "\n";
    echo "📱 Sending to: {$testPhone}\n\n";
    
    // Send the SMS exactly like the doctor controller does
    $result = $smsService->sendAppointmentConfirmation($testPhone, $appointmentData);
    
    if ($result['success']) {
        echo "✅ DOCTOR CONFIRMATION SMS SENT!\n";
        echo "📧 Message ID: " . ($result['data']['message_id'] ?? 'N/A') . "\n";
        echo "📊 Status: " . ($result['data']['status'] ?? 'N/A') . "\n";
        echo "\n🎯 SUCCESS! Your appointment confirmation system is working!\n";
        echo "📲 Check your phone for the confirmation message.\n";
    } else {
        echo "❌ DOCTOR CONFIRMATION FAILED!\n";
        echo "💥 Error: " . ($result['error'] ?? 'Unknown error') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "🎉 SYSTEM READY FOR TOMORROW! 🎉\n";
echo "When doctors confirm appointments, patients will receive SMS.\n";
echo str_repeat("=", 50) . "\n";
?>