<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Approval extends Model
{
    use HasFactory;

    protected $fillable = [
        'approvable_type',
        'approvable_id',
        'stage',
        'approver_id',
        'status',
        'comments',
        'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function approvable()
    {
        return $this->morphTo();
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
