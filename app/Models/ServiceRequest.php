<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_number', 'client_id', 'department_id', 'service_category', 'title',
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

    public function getTargetLanguageAttribute($value)
    {
        if (empty($value)) {
            return [];
        }
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }
        if (strpos($value, ',') !== false) {
            return array_map('trim', explode(',', $value));
        }
        return [$value];
    }

    public function setTargetLanguageAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['target_language'] = json_encode(array_values(array_filter($value)));
        } else {
            $this->attributes['target_language'] = $value;
        }
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

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }
}
