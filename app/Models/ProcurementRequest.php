<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcurementRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_number', 'title', 'description', 'estimated_cost',
        'justification', 'priority', 'status',
        'requested_by', 'department_id',
    ];

    protected $casts = [
        'estimated_cost' => 'decimal:2',
        'approved_at'    => 'datetime',
    ];

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
