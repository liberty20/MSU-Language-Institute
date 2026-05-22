<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_request_id',
        'quotation_id',
        'client_id',
        'amount_paid',
        'bank_used',
        'proof_file_path',
        'status',
        'notes',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
        'verified_at' => 'datetime',
    ];

    public function serviceRequest()
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
