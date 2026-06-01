<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class MsunliRole extends Model
{
    use HasFactory;

    protected $table = 'msunli_roles';

    protected $fillable = ['section_id', 'role_id', 'name'];

    public function section()
    {
        return $this->belongsTo(MsunliSection::class, 'section_id');
    }

    public function spatieRole()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'msunli_role_id');
    }
}
