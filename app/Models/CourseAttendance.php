<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_timetable_id',
        'user_id',
        'status',
        'remarks',
        'recorded_by',
    ];

    public function timetable()
    {
        return $this->belongsTo(CourseTimetable::class, 'course_timetable_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
