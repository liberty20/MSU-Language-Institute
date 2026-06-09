<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        $temp = \App\Services\UserBackupService::$shouldBackup;
        \App\Services\UserBackupService::$shouldBackup = false;

        try {
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'manage users', 'manage roles', 'manage clients', 'manage service requests',
            'create service requests', 'view service requests', 'manage quotations',
            'view quotations', 'approve quotations', 'manage assignments', 'view assignments',
            'manage tasks', 'view tasks', 'manage procurement', 'approve procurement',
            'view reports', 'manage system',
            'manage schedule', 'manage correspondence', 'manage inquiries',
            'manage executive communications', 'manage administrative documentation',
            'manage executive notifications',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $roles = [
            'executive_director' => Permission::all(),
            'deputy_director'    => ['manage users', 'view service requests', 'approve quotations', 'view assignments', 'view reports', 'approve procurement', 'view quotations'],
            'admin_assistant'    => ['manage procurement', 'view reports', 'manage clients', 'manage quotations', 'view quotations'],
            'secretary'          => [
                'manage service requests', 'view reports', 'manage assignments', 'view assignments', 
                'view tasks', 'view quotations', 'manage schedule', 'manage correspondence', 
                'manage inquiries', 'manage executive communications', 'manage administrative documentation', 
                'manage executive notifications'
            ],
            'receptionist'       => ['manage clients', 'create service requests', 'view service requests'],
            'language_expert'    => ['view assignments', 'view tasks', 'manage tasks'],
            'part_time_staff'    => ['view assignments', 'view tasks', 'manage tasks'],
            'ict_administrator'  => Permission::all(),
            'client'             => ['create service requests', 'view service requests'],
            'student'            => [],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($rolePermissions);
        }

        $users = [
            ['name' => 'Executive Director', 'email' => 'executive.director@msunli.edu', 'role' => 'executive_director'],
            ['name' => 'Deputy Director', 'email' => 'deputy.director@msunli.edu', 'role' => 'deputy_director'],
            ['name' => 'Admin Assistant', 'email' => 'admin.assistant@msunli.edu', 'role' => 'admin_assistant'],
            ['name' => 'Secretary', 'email' => 'secretary@msunli.edu', 'role' => 'secretary'],
            ['name' => 'Receptionist', 'email' => 'receptionist@msunli.edu', 'role' => 'receptionist'],
            ['name' => 'Language Expert', 'email' => 'expert@msunli.edu', 'role' => 'language_expert'],
            ['name' => 'Part Time Staff', 'email' => 'parttime@msunli.edu', 'role' => 'part_time_staff'],
            ['name' => 'ICT Administrator', 'email' => 'admin@msunli.edu', 'role' => 'ict_administrator'],
            ['name' => 'Test Client', 'email' => 'client@example.com', 'role' => 'client'],
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name'      => $userData['name'],
                    'password'  => Hash::make('password'),
                    'is_active' => true,
                ]
            );
            $user->syncRoles([$userData['role']]);
        }
        } finally {
            \App\Services\UserBackupService::$shouldBackup = $temp;
        }
    }
}
