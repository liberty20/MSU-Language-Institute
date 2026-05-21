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
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'manage users', 'manage roles', 'manage clients', 'manage service requests',
            'create service requests', 'view service requests', 'manage quotations',
            'approve quotations', 'manage assignments', 'view assignments', 'manage tasks',
            'view tasks', 'manage procurement', 'approve procurement', 'view reports', 'manage system',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        $roles = [
            'executive_director' => Permission::all(),
            'deputy_director'    => ['view service requests', 'approve quotations', 'view assignments', 'view reports', 'approve procurement'],
            'admin_assistant'    => ['manage procurement', 'view reports', 'manage clients'],
            'secretary'          => ['manage service requests', 'view reports', 'manage assignments', 'view tasks'],
            'receptionist'       => ['manage clients', 'create service requests', 'view service requests'],
            'language_expert'    => ['view assignments', 'view tasks', 'manage tasks'],
            'part_time_staff'    => ['view assignments', 'view tasks', 'manage tasks'],
            'ict_administrator'  => Permission::all(),
            'client'             => ['create service requests', 'view service requests'],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::create(['name' => $roleName]);
            $role->givePermissionTo($rolePermissions);
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
            $user = User::create([
                'name'      => $userData['name'],
                'email'     => $userData['email'],
                'password'  => Hash::make('password'),
                'is_active' => true,
            ]);
            $user->assignRole($userData['role']);
        }
    }
}
