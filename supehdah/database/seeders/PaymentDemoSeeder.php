<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Appointment;
use App\Models\ClinicInfo;
use App\Models\Doctor;
use Carbon\Carbon;

class PaymentDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get the first clinic
        $clinic = ClinicInfo::first();
        if (!$clinic) {
            $this->command->info('No clinic found. Please create a clinic first.');
            return;
        }

        // Get the first doctor for this clinic
        $doctor = Doctor::where('clinic_id', $clinic->id)->first();

        // Create some completed appointments with unpaid status for demo
        $appointments = [
            [
                'owner_name' => 'John Doe',
                'owner_phone' => '+639171234567',
                'appointment_date' => Carbon::today()->subDays(1)->format('Y-m-d'),
                'appointment_time' => '10:00:00',
                'status' => 'completed',
                'payment_status' => 'unpaid',
                'notes' => json_encode([
                    'chief_complaint' => 'Routine checkup for Buddy',
                    'diagnosis' => 'Healthy dog, no issues found',
                    'plan_recommendations' => 'Regular exercise, continue current diet'
                ])
            ],
            [
                'owner_name' => 'Maria Santos',
                'owner_phone' => '+639187654321',
                'appointment_date' => Carbon::today()->format('Y-m-d'),
                'appointment_time' => '14:00:00',
                'status' => 'completed',
                'payment_status' => 'unpaid',
                'notes' => json_encode([
                    'chief_complaint' => 'Vaccination for kitten',
                    'diagnosis' => 'Healthy kitten, ready for vaccination',
                    'plan_recommendations' => 'Next vaccination in 3 weeks'
                ])
            ],
            [
                'owner_name' => 'Pedro Garcia',
                'owner_phone' => '+639199876543',
                'appointment_date' => Carbon::today()->subDays(2)->format('Y-m-d'),
                'appointment_time' => '09:30:00',
                'status' => 'completed',
                'payment_status' => 'paid',
                'amount' => 500.00,
                'payment_method' => 'cash',
                'receipt_number' => 'RCPT-2025-00001',
                'payment_date' => Carbon::today()->subDays(2)->addHours(2),
                'notes' => json_encode([
                    'chief_complaint' => 'Skin irritation',
                    'diagnosis' => 'Allergic dermatitis',
                    'plan_recommendations' => 'Prescribed medicated shampoo, avoid allergens'
                ])
            ]
        ];

        foreach ($appointments as $appointmentData) {
            $appointmentData['clinic_id'] = $clinic->id;
            $appointmentData['doctor_id'] = $doctor ? $doctor->id : null;
            
            Appointment::create($appointmentData);
        }

        $this->command->info('Demo payment data created successfully!');
        $this->command->info('- 2 unpaid completed appointments created');
        $this->command->info('- 1 paid completed appointment created');
    }
}
