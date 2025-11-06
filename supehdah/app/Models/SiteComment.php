<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\ClinicInfo;

class SiteComment extends Model
{
    use HasFactory;

    protected $table = 'site_comments';

    protected $fillable = [
        'clinic_id',
        'name',
        'email',
        'rating',
        'comment',
    ];

    /**
     * (Optional) relation to clinic info if used.
     */
    public function clinic()
    {
        return $this->belongsTo(ClinicInfo::class, 'clinic_id');
    }
}
