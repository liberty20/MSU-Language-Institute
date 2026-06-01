<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_intake_id',
        'full_name',
        'national_id_number',
        'national_id_copy_path',
        'email',
        'phone',
        'physical_address',
        'payment_proof_path',
        'temporary_password',
        'status',
        'verification_status',
        'verified_by',
        'verified_at',
        'recommendation_status',
        'recommended_by',
        'recommended_at',
        'approval_status',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'recommended_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function intake()
    {
        return $this->belongsTo(CourseIntake::class, 'course_intake_id');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function recommendedBy()
    {
        return $this->belongsTo(User::class, 'recommended_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function logs()
    {
        return $this->hasMany(CourseApplicationLog::class, 'course_application_id')->orderBy('created_at', 'desc');
    }
}
