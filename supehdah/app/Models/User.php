<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * User model representing application users.
 *
 * @property int $id
 * @property string $first_name
 * @property string|null $middle_name
 * @property string $last_name
 * @property string $email
 * @property string|null $phone_number
 * @property string|null $gender
 * @property string|null $birthday
 * @property string $password
 * @property string $role
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
 
protected $fillable = [
    'first_name', 'middle_name', 'last_name', 'email', 'phone_number', 
    'gender', 'birthday', 'password', 'role', 'google_id', 'google_token',
    'google_refresh_token', 'avatar', 'device_token', 'clinic_id',
];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    
    /**
     * Get the user's full name
     */
    public function getFullNameAttribute()
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }
    
    // app/Models/User.php

public function clinicInfo()
{
    return $this->hasOne(ClinicInfo::class, 'user_id');
}

/**
 * Get the clinic that this user belongs to (if staff).
 */
public function clinic()
{
    return $this->belongsTo(Clinic::class, 'clinic_id');
}

/**
 * Get the clinics owned by this user.
 */
public function ownedClinics()
{
    return $this->hasMany(Clinic::class, 'owner_id');
}

public function doctorProfile()
{
    return $this->hasOne(Doctor::class, 'user_id');
}

public function pets()
{
    return $this->hasMany(Pet::class);
}

/**
 * Get the OTP verification code associated with the user.
 */
public function verificationOtp()
{
    return $this->hasOne(EmailVerificationOtp::class);
}

/**
 * Get the notifications for this user.
 */
public function notifications()
{
    return $this->morphMany(Notification::class, 'notifiable');
}

}
