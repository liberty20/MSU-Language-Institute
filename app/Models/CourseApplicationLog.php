<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseApplicationLog extends Model
{
    use HasFactory;

    protected $table = 'course_application_logs';

    protected $fillable = [
        'course_application_id',
        'user_id',
        'action',
        'comment',
    ];

    public function application()
    {
        return $this->belongsTo(CourseApplication::class, 'course_application_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
