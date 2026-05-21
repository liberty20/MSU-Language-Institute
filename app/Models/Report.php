<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'generated_by', 'report_type', 'title', 'parameters', 'file_path', 'format',
    ];

    protected $casts = [
        'parameters' => 'array',
    ];

    public function generatedByUser()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
