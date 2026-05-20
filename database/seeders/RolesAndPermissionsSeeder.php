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
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // create permissions
        $permissions = [
            'manage users',
            'manage roles',
            'manage clients',
            'manage service requests',
            'create service requests',
            'view service requests',
            'manage quotations',
            'approve quotations',
            'manage assignments',
            'view assignments',
            'manage tasks',
            'view tasks',
            'manage procurement',
            'approve procurement',
            'view reports',
            'manage system',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // create roles and assign created permissions
        $roles = [
            'executive_director' => Permission::all(),
            'deputy_director' => ['view service requests', 'approve quotations', 'view assignments', 'view reports', 'approve procurement'],
            'secretary' => ['manage service requests', 'view reports', 'manage assignments', 'view tasks'],
            'receptionist' => ['manage clients', 'create service requests', 'view service requests'],
            'admin_assistant' => ['manage procurement', 'view reports'],
            'language_expert' => ['view assignments', 'view tasks', 'manage tasks'],
            'part_time_staff' => ['view assignments', 'view tasks', 'manage tasks'],
            'ict_administrator' => Permission::all(),
            'client' => ['create service requests', 'view service requests'],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::create(['name' => $roleName]);
            $role->givePermissionTo($rolePermissions);
        }

        // create admin user
        $admin = User::create([
            'name' => 'ICT Administrator',
            'email' => 'admin@msunli.edu',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $admin->assignRole('ict_administrator');

        // create test client
        $client = User::create([
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $client->assignRole('client');
    }
}
