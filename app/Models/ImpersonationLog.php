<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImpersonationLog extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at'   => 'datetime',
    ];

    public function impersonator()
    {
        return $this->belongsTo(User::class, 'impersonator_id');
    }

    public function impersonated()
    {
        return $this->belongsTo(User::class, 'impersonated_id');
    }

    /**
     * Mark the impersonation session as ended and calculate duration.
     */
    public function end(): self
    {
        $this->ended_at        = now();
        $this->duration_seconds = $this->started_at
            ? (int) $this->started_at->diffInSeconds(now())
            : null;
        $this->save();

        return $this;
    }
}
