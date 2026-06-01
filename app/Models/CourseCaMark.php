<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseCaMark extends Model
{
    use HasFactory;

    protected $table = 'course_ca_marks';

    protected $fillable = [
        'course_intake_id',
        'user_id',
        'assessment_name',
        'marks_obtained',
        'max_marks',
        'feedback',
        'entered_by',
    ];

    public function intake()
    {
        return $this->belongsTo(CourseIntake::class, 'course_intake_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'entered_by');
    }
}
