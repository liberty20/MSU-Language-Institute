<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseEnrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_intake_id', 'user_id', 'payment_status', 'payment_proof_path',
        'amount_paid', 'enrollment_status', 'certificate_code', 'certificate_issued_at',
        'certificate_collected_at', 'certificate_collected_by',
    ];

    protected $casts = [
        'certificate_issued_at' => 'datetime',
        'certificate_collected_at' => 'datetime',
        'amount_paid' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('students_only', function ($builder) {
            $builder->whereHas('user', function ($q) {
                $q->where('primary_category', 'Student');
            });
        });

        static::saving(function ($enrollment) {
            $user = $enrollment->user;
            if ($user && $user->primary_category !== 'Student') {
                throw new \InvalidArgumentException("Enrollment conflict: Only users in the Student category can be enrolled in courses.");
            }
        });
    }

    public function intake()
    {
        return $this->belongsTo(CourseIntake::class, 'course_intake_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function collectedBy()
    {
        return $this->belongsTo(User::class, 'certificate_collected_by');
    }
}
