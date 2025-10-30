<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $fillable = [
        'clinic_id', 
        'doctor_id',
        'owner_name', 
        'owner_phone', 
        'appointment_date',
        'appointment_time',
        'status',
        'notes',
        // Keep the physical column name available too
        'consultation_notes'
    ];
    
    // Setting the appropriate date fields according to README specifications
    protected $dates = ['created_at', 'updated_at'];
    
    // We're handling appointment_date separately for better control over the format
    
    /**
     * Get the appointment date attribute formatted properly
     *
     * @param  string|null  $value
     * @return \Carbon\Carbon|null
     */
    public function getAppointmentDateAttribute($value)
    {
        if (!$value) return null;
        
        try {
            // Using Philippines timezone (UTC+8) as specified in README
            return \Carbon\Carbon::parse($value, 'Asia/Manila');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error parsing appointment date in model', [
                'value' => $value,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Accessor for legacy `notes` attribute. Some code still uses `$appointment->notes`.
     * Map it to the actual `consultation_notes` column.
     */
    public function getNotesAttribute()
    {
        return $this->attributes['consultation_notes'] ?? null;
    }

    /**
     * Mutator for legacy `notes` attribute. Writes into `consultation_notes` column.
     */
    public function setNotesAttribute($value)
    {
        $this->attributes['consultation_notes'] = $value;
    }
    
    /**
     * Set the appointment date attribute to ensure it's in YYYY-MM-DD format
     *
     * @param  string|null  $value
     * @return void
     */
    public function setAppointmentDateAttribute($value)
    {
        if (!$value) {
            $this->attributes['appointment_date'] = null;
            return;
        }
        
        try {
            $date = \Carbon\Carbon::parse($value, 'Asia/Manila');
            $this->attributes['appointment_date'] = $date->format('Y-m-d');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error setting appointment date', [
                'value' => $value,
                'error' => $e->getMessage()
            ]);
            $this->attributes['appointment_date'] = $value;
        }
    }
    
    /**
     * Set the appointment time attribute to ensure it's in HH:MM:SS format
     *
     * @param  string|null  $value
     * @return void
     */
    public function setAppointmentTimeAttribute($value)
    {
        if (!$value) {
            $this->attributes['appointment_time'] = null;
            return;
        }
        
        try {
            $time = \Carbon\Carbon::parse($value, 'Asia/Manila');
            $this->attributes['appointment_time'] = $time->format('H:i:s');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error setting appointment time', [
                'value' => $value,
                'error' => $e->getMessage()
            ]);
            $this->attributes['appointment_time'] = $value;
        }
    }
    
    /**
     * Get the formatted appointment time (12-hour format with AM/PM)
     *
     * @return string|null
     */
    public function getFormattedTimeAttribute()
    {
        if (!$this->appointment_time) return null;
        
        try {
            // Format time to 12-hour format with AM/PM
            return \Carbon\Carbon::parse($this->appointment_time, 'Asia/Manila')->format('g:i A');
        } catch (\Exception $e) {
            return $this->appointment_time;
        }
    }

    public function customValues()
    {
        return $this->hasMany(AppointmentFieldValue::class);
    }

    public function clinic()
    {
        return $this->belongsTo(ClinicInfo::class, 'clinic_id');
    }
    
    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }
    
    /**
     * This is a convenience method that returns the appointment itself as "patient"
     * since the patient information is stored directly in the appointments table
     */
    public function patient()
    {
        return $this->belongsTo(self::class, 'id', 'id');
    }
}
