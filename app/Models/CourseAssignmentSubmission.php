<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseAssignmentSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_assignment_id',
        'user_id',
        'submitted_at',
        'attachment_path',
        'notes',
        'status',
        'marks_obtained',
        'feedback',
        'graded_by',
        'graded_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
    ];

    public function assignment()
    {
        return $this->belongsTo(CourseAssignment::class, 'course_assignment_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function grader()
    {
        return $this->belongsTo(User::class, 'graded_by');
    }
}
