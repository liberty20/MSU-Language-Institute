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
    ];

    protected $casts = [
        'certificate_issued_at' => 'datetime',
        'amount_paid' => 'decimal:2',
    ];

    public function intake()
    {
        return $this->belongsTo(CourseIntake::class, 'course_intake_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
