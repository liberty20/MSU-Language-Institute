<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseIntake extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id', 'name', 'start_date', 'end_date', 'capacity',
        'status', 'instructor_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function enrollments()
    {
        return $this->hasMany(CourseEnrollment::class);
    }

    public function timetables()
    {
        return $this->hasMany(CourseTimetable::class, 'course_intake_id');
    }

    public function assignments()
    {
        return $this->hasMany(CourseAssignment::class, 'course_intake_id');
    }

    public function caMarks()
    {
        return $this->hasMany(CourseCaMark::class, 'course_intake_id');
    }
}

