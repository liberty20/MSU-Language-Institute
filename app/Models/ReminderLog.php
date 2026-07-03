<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReminderLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'remindable_type',
        'remindable_id',
        'module',
        'trigger_type',
        'priority',
        'channels',
        'sent_at',
        'notification_id',
        'acknowledged_at',
        'dismissed_at',
        'task_completed_at',
    ];

    protected $casts = [
        'channels' => 'array',
        'sent_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'dismissed_at' => 'datetime',
        'task_completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function remindable()
    {
        return $this->morphTo();
    }
}
