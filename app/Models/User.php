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
    use HasApiTokens, HasFactory, Notifiable;
    use HasRoles {
        assignRole as spatieAssignRole;
        syncRoles as spatieSyncRoles;
    }

    public static $ignoreCategoryIntegrity = false;

    protected $fillable = [
        'name', 'email', 'password', 'phone', 'department_id', 'section_id', 'msunli_role_id', 'avatar', 'is_active', 'primary_category',
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

    public function section()
    {
        return $this->belongsTo(MsunliSection::class, 'section_id');
    }

    public function msunliRole()
    {
        return $this->belongsTo(MsunliRole::class, 'msunli_role_id');
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class, 'assigned_to');
    }

    public function serviceRequests()
    {
        return $this->hasMany(ServiceRequest::class, 'submitted_by');
    }

    public function courseEnrollments()
    {
        return $this->hasMany(CourseEnrollment::class);
    }

    public function instructedIntakes()
    {
        return $this->hasMany(CourseIntake::class, 'instructor_id');
    }

    public function submissions()
    {
        return $this->hasMany(CourseAssignmentSubmission::class, 'user_id');
    }

    public function courseCaMarks()
    {
        return $this->hasMany(CourseCaMark::class, 'user_id');
    }

    public function getRoleNameAttribute()
    {
        return $this->getRoleNames()->first() ?? 'N/A';
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($user) {
            if (empty($user->primary_category)) {
                $user->primary_category = 'Staff';
            }

            if (!in_array($user->primary_category, ['Staff', 'Student', 'Client'])) {
                $user->primary_category = 'Staff';
            }

            if (in_array($user->primary_category, ['Student', 'Client'])) {
                if ($user->department_id !== null || $user->section_id !== null || $user->msunli_role_id !== null) {
                    throw new \InvalidArgumentException("Category conflict: A Student or Client cannot be assigned to a department, section, or hierarchy role.");
                }
            }
        });
    }

    public function getCategoryForRoles($roleNames)
    {
        $roleNames = collect($roleNames)->map(fn($r) => is_string($r) ? $r : $r->name)->toArray();
        if (in_array('client', $roleNames)) {
            return 'Client';
        }
        if (in_array('student', $roleNames)) {
            return 'Student';
        }
        return 'Staff';
    }

    public function updatePrimaryCategory()
    {
        $roles = $this->roles->pluck('name')->toArray();
        $category = $this->getCategoryForRoles($roles);
        if ($this->primary_category !== $category) {
            $temp = static::$ignoreCategoryIntegrity;
            static::$ignoreCategoryIntegrity = true;
            $this->primary_category = $category;
            $this->save();
            static::$ignoreCategoryIntegrity = $temp;
        }
    }

    public function validateRoleChanges(array $newRoles)
    {
        if (static::$ignoreCategoryIntegrity) {
            return;
        }

        $newRoleNames = collect($newRoles)
            ->map(function ($role) {
                if (is_string($role)) return $role;
                if (is_numeric($role)) {
                    $r = \Spatie\Permission\Models\Role::find($role);
                    return $r ? $r->name : null;
                }
                if ($role instanceof \Spatie\Permission\Models\Role) return $role->name;
                return null;
            })
            ->filter()
            ->unique()
            ->toArray();

        if (empty($newRoleNames)) {
            return;
        }

        $hasClient = in_array('client', $newRoleNames);
        $hasStudent = in_array('student', $newRoleNames);
        $hasStaff = false;
        foreach ($newRoleNames as $role) {
            if ($role !== 'client' && $role !== 'student') {
                $hasStaff = true;
            }
        }

        $categoriesPresent = 0;
        if ($hasClient) $categoriesPresent++;
        if ($hasStudent) $categoriesPresent++;
        if ($hasStaff) $categoriesPresent++;

        if ($categoriesPresent > 1) {
            throw new \InvalidArgumentException("Role category conflict: A user cannot be assigned roles from multiple categories simultaneously.");
        }

        $currentRoles = $this->roles->pluck('name')->toArray();
        if (!empty($currentRoles)) {
            $currentCategory = $this->getCategoryForRoles($currentRoles);
            $newCategory = $this->getCategoryForRoles($newRoleNames);

            if ($currentCategory !== $newCategory) {
                $authUser = auth()->user();
                $isAuthorized = $authUser && $authUser->hasAnyRole(['ict_administrator', 'executive_director']);
                
                if (!$isAuthorized && (\App\Services\UserBackupService::$isSyncing || (app()->runningInConsole() && !auth()->check()))) {
                    $isAuthorized = true;
                }

                if (!$isAuthorized) {
                    throw new \InvalidArgumentException("Role integrity violation: Changing a user's primary category from {$currentCategory} to {$newCategory} is not allowed without privileged administrator authorization.");
                }
            }
        }
    }

    public function assignRole(...$roles)
    {
        $rolesToCheck = is_array($roles[0] ?? null) ? $roles[0] : $roles;
        if ($rolesToCheck instanceof \Illuminate\Support\Collection) {
            $rolesToCheck = $rolesToCheck->toArray();
        }
        $existingRoles = $this->roles->pluck('name')->toArray();
        $combinedRoles = array_merge($existingRoles, $rolesToCheck);
        
        $this->validateRoleChanges($combinedRoles);

        $oldRoles = $this->roles->pluck('name')->toArray();
        $oldCategory = $this->primary_category;

        $result = $this->spatieAssignRole(...$roles);

        $this->load('roles');
        $newRoles = $this->roles->pluck('name')->toArray();
        $newCategory = $this->getCategoryForRoles($newRoles);

        $this->updatePrimaryCategory();
        $this->auditRoleCategoryChange($oldRoles, $newRoles, $oldCategory, $newCategory);

        return $result;
    }

    public function syncRoles(...$roles)
    {
        $resolvedRoles = is_array($roles[0] ?? null) ? $roles[0] : $roles;
        if ($resolvedRoles instanceof \Illuminate\Support\Collection) {
            $resolvedRoles = $resolvedRoles->toArray();
        }

        $this->validateRoleChanges($resolvedRoles);

        $oldRoles = $this->roles->pluck('name')->toArray();
        $oldCategory = $this->primary_category;

        $result = $this->spatieSyncRoles(...$roles);

        $this->load('roles');
        $newRoles = $this->roles->pluck('name')->toArray();
        $newCategory = $this->getCategoryForRoles($newRoles);

        $this->updatePrimaryCategory();
        $this->auditRoleCategoryChange($oldRoles, $newRoles, $oldCategory, $newCategory);

        return $result;
    }

    protected function auditRoleCategoryChange($oldRoles, $newRoles, $oldCategory, $newCategory)
    {
        sort($oldRoles);
        sort($newRoles);

        if ($oldRoles === $newRoles) {
            return;
        }

        $user = auth()->user();
        $actorStr = $user ? "{$user->name} ({$user->email})" : 'System/Process';

        $properties = [
            'user_performing_action' => $actorStr,
            'ip_address' => request() ? request()->ip() : null,
            'date_and_time' => now()->toIso8601String(),
            'previous_roles' => $oldRoles,
            'new_roles' => $newRoles,
            'previous_category' => $oldCategory,
            'new_category' => $newCategory,
            'action_type' => 'role_change',
        ];

        \App\Models\ActivityLog::log(
            'role_category_change',
            "Changed roles for user {$this->name} ({$this->email}). Category: {$oldCategory} -> {$newCategory}",
            $this,
            $properties
        );
    }
}

