<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseTimetable extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_intake_id',
        'date',
        'start_time',
        'end_time',
        'venue',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function intake()
    {
        return $this->belongsTo(CourseIntake::class, 'course_intake_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
