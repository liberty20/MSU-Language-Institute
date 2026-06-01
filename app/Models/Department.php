<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'head_id'];

    public function courses()
    {
        return $this->hasMany(Course::class, 'department_id');
    }

    public function serviceRequests()
    {
        return $this->hasMany(ServiceRequest::class, 'department_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'department_id');
    }
}
