<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LearningContent extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_intake_id',
        'uploaded_by',
        'title',
        'description',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
    ];

    public function intake()
    {
        return $this->belongsTo(CourseIntake::class, 'course_intake_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
