<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name', 'email', 'password', 'phone', 'department_id', 'avatar', 'is_active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active'         => 'boolean',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class, 'assigned_to');
    }

    public function serviceRequests()
    {
        return $this->hasMany(ServiceRequest::class, 'submitted_by');
    }

    public function getRoleNameAttribute()
    {
        return $this->getRoleNames()->first() ?? 'N/A';
    }
}
