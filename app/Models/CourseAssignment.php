<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_intake_id',
        'title',
        'description',
        'attachment_path',
        'due_date',
        'max_marks',
        'created_by',
    ];

    protected $casts = [
        'due_date' => 'datetime',
    ];

    public function intake()
    {
        return $this->belongsTo(CourseIntake::class, 'course_intake_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submissions()
    {
        return $this->hasMany(CourseAssignmentSubmission::class);
    }

    public function documents()
    {
        return $this->morphMany(UploadedDocument::class, 'documentable');
    }
}
