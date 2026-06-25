<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'code', 'category', 'description', 'department_id',
        'price', 'currency', 'duration_weeks', 'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'price' => 'decimal:2',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function intakes()
    {
        return $this->hasMany(CourseIntake::class);
    }

    public function notices()
    {
        return $this->hasMany(Notice::class);
    }

    public function announcements()
    {
        return $this->hasMany(Announcement::class);
    }
}
