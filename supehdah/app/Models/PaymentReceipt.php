<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PaymentReceipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'receipt_number',
        'appointment_id',
        'clinic_id',
        'doctor_id',
        'patient_name',
        'doctor_name',
        'service_description',
        'amount',
        'payment_method',
        'payment_date',
        'processed_by',
        'notes'
    ];

    protected $dates = ['payment_date', 'created_at', 'updated_at'];

    /**
     * Generate a unique receipt number
     */
    public static function generateReceiptNumber()
    {
        $year = date('Y');
        $lastReceipt = self::whereYear('created_at', $year)
            ->orderBy('created_at', 'desc')
            ->first();
        
        $nextNumber = $lastReceipt ? (int) substr($lastReceipt->receipt_number, -5) + 1 : 1;
        return 'RCPT-' . $year . '-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Get the appointment that this receipt belongs to
     */
    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Get the clinic that this receipt belongs to
     */
    public function clinic()
    {
        return $this->belongsTo(ClinicInfo::class, 'clinic_id');
    }

    /**
     * Get the doctor that provided the service
     */
    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}
