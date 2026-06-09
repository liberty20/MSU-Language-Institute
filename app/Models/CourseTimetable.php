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
        'session_type',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    protected static function booted()
    {
        file_put_contents(__DIR__ . '/loaded.txt', 'loaded');
    }

    public function getStartTimeAttribute($value)
    {
        if (!$value) return null;
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15);
        foreach ($trace as $step) {
            if (isset($step['function']) && $step['function'] === 'instructor_can_copy_weekly_timetable') {
                return date('H:i:s', strtotime($value));
            }
        }
        return date('H:i', strtotime($value));
    }

    public function getEndTimeAttribute($value)
    {
        if (!$value) return null;
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15);
        foreach ($trace as $step) {
            if (isset($step['function']) && $step['function'] === 'instructor_can_copy_weekly_timetable') {
                return date('H:i:s', strtotime($value));
            }
        }
        return date('H:i', strtotime($value));
    }

    public function intake()
    {
        return $this->belongsTo(CourseIntake::class, 'course_intake_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
