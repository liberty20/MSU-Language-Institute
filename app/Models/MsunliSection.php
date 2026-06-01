<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MsunliSection extends Model
{
    use HasFactory;

    protected $table = 'msunli_sections';

    protected $fillable = ['unit_id', 'name', 'code'];

    public function unit()
    {
        return $this->belongsTo(Department::class, 'unit_id');
    }

    public function msunliRoles()
    {
        return $this->hasMany(MsunliRole::class, 'section_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'section_id');
    }
}
