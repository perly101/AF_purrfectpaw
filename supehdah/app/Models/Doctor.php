<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'clinic_id',
        'user_id',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'phone_number',
        'gender',
        'birthday',
        'photo',
        'specialization',
        'license_number',
        'experience_years',
        'availability_status',
        'bio',
    ];
    
    /**
     * Get the clinic that the doctor belongs to.
     */
    public function clinic()
    {
        return $this->belongsTo(ClinicInfo::class, 'clinic_id');
    }
    
    /**
     * Get the user account associated with the doctor.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    /**
     * Get full name of the doctor.
     */
    public function getFullNameAttribute()
    {
        if ($this->middle_name) {
            return "{$this->first_name} {$this->middle_name} {$this->last_name}";
        }
        
        return "{$this->first_name} {$this->last_name}";
    }
    
    /**
     * Get availability status in human readable format.
     */
    public function getStatusTextAttribute()
    {
        return [
            'active' => 'Active',
            'on_leave' => 'On Leave',
            'not_accepting' => 'Not Accepting Appointments',
        ][$this->availability_status] ?? 'Unknown';
    }

    /**
     * Get the notifications for this doctor.
     */
    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }

    /**
     * Get the doctor's name (for compatibility)
     */
    public function getNameAttribute()
    {
        return $this->getFullNameAttribute();
    }
}
