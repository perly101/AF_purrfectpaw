<?php

require_once 'vendor/autoload.php';

// Load Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\Appointment;
use App\Models\ClinicInfo;
use App\Models\Doctor;

echo "=== CREATING TEST APPOINTMENT FOR SMS ===\n\n";

// Find clinic and doctor
$clinic = ClinicInfo::first();
$doctor = Doctor::first();

if (!$clinic || !$doctor) {
    echo "❌ Need clinic and doctor to create appointment\n";
    exit;
}

// Create a test appointment with 'assigned' status
$appointment = new Appointment();
$appointment->clinic_id = $clinic->id;
$appointment->doctor_id = $doctor->id;
$appointment->owner_name = 'SMS Test Patient';
$appointment->owner_phone = '09632879598'; // Your phone number
$appointment->status = 'assigned'; // This will show Accept/Decline buttons
$appointment->appointment_date = '2025-10-28';
$appointment->appointment_time = '14:00:00';
$appointment->created_at = now();
$appointment->updated_at = now();

$appointment->save();

echo "✅ TEST APPOINTMENT CREATED!\n";
echo "Appointment ID: {$appointment->id}\n";
echo "Status: {$appointment->status}\n";
echo "Phone: {$appointment->owner_phone}\n";
echo "Doctor: {$doctor->name}\n";
echo "Clinic: {$clinic->clinic_name}\n\n";

echo "🎯 NOW YOU CAN TEST SMS:\n";
echo "1. Login as doctor (User ID: {$doctor->user_id})\n";
echo "2. Go to: /doctor/appointments/{$appointment->id}\n";
echo "3. You will see 'Accept Appointment' button\n";
echo "4. Click Accept → SMS will be sent IMMEDIATELY!\n\n";

echo "📱 SMS will be sent to: {$appointment->owner_phone}\n";
echo "⏰ Ready to test at: " . now()->format('H:i:s') . "\n";

?>