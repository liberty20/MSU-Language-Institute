<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UpdateRbacServiceRequests extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Spatie registers permissions to the global registrar. Forget cache to avoid sync issues.
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Ensure permissions exist
        $viewPerm = Permission::firstOrCreate(['name' => 'view service requests', 'guard_name' => 'web']);
        $createPerm = Permission::firstOrCreate(['name' => 'create service requests', 'guard_name' => 'web']);
        $managePerm = Permission::firstOrCreate(['name' => 'manage service requests', 'guard_name' => 'web']);

        // 1. Grant to Admin Assistant
        $adminAssistant = Role::firstOrCreate(['name' => 'admin_assistant', 'guard_name' => 'web']);
        $adminAssistant->givePermissionTo([$viewPerm, $createPerm, $managePerm]);

        // 2. Grant to Secretary
        $secretary = Role::firstOrCreate(['name' => 'secretary', 'guard_name' => 'web']);
        $secretary->givePermissionTo([$viewPerm, $createPerm, $managePerm]);

        // 3. Restrict Coordinator from creating service requests
        $coordinator = Role::firstOrCreate(['name' => 'coordinator', 'guard_name' => 'web']);
        $coordinator->revokePermissionTo($createPerm);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $createPerm = Permission::firstOrCreate(['name' => 'create service requests', 'guard_name' => 'web']);

        // 1. Revoke from Admin Assistant
        $adminAssistant = Role::firstOrCreate(['name' => 'admin_assistant', 'guard_name' => 'web']);
        $adminAssistant->revokePermissionTo(['view service requests', 'create service requests', 'manage service requests']);

        // 2. Revoke from Secretary
        $secretary = Role::firstOrCreate(['name' => 'secretary', 'guard_name' => 'web']);
        $secretary->revokePermissionTo(['view service requests', 'create service requests']);

        // 3. Re-grant to Coordinator
        $coordinator = Role::firstOrCreate(['name' => 'coordinator', 'guard_name' => 'web']);
        if ($createPerm) {
            $coordinator->givePermissionTo($createPerm);
        }
    }
}
