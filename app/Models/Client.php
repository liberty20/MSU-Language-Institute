<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_type', 'organization', 'contact_person',
        'email', 'phone', 'address', 'status',
    ];

    public function serviceRequests()
    {
        return $this->hasMany(ServiceRequest::class);
    }

    public function getDisplayNameAttribute()
    {
        return $this->organization ?? $this->contact_person;
    }
}
