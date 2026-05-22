<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_number', 'client_id', 'service_category', 'title',
        'description', 'source_language', 'target_language', 'priority',
        'status', 'submitted_by', 'assigned_to', 'deadline', 'notes',
        'rating', 'review_comments',
    ];

    protected $casts = [
        'deadline' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->reference_number)) {
                $model->reference_number = 'SR-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function quotations()
    {
        return $this->hasMany(Quotation::class);
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }

    public function getServiceLabelAttribute()
    {
        return ucwords(str_replace('_', ' ', $this->service_category));
    }

    public function documents()
    {
        return $this->morphMany(UploadedDocument::class, 'documentable');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
