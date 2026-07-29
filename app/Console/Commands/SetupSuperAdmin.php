<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SetupSuperAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'msuli:setup-super-admin {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates the super_administrator role, syncs all permissions, and assigns it to the specified email.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $email = $this->argument('email');

        $this->info("Setting up super_administrator role...");

        // 1. Ensure the role exists
        $role = Role::firstOrCreate(['name' => 'super_administrator', 'guard_name' => 'web']);
        
        // 2. Sync all permissions
        $permissions = Permission::all();
        $role->syncPermissions($permissions);
        $this->info("Synced {$permissions->count()} permissions to super_administrator role.");

        // 3. Find the user and assign the role
        $user = User::where('email', $email)->first();
        if (!$user) {
            $this->error("Error: User with email '{$email}' not found in the database.");
            return 1;
        }

        $user->assignRole('super_administrator');
        $this->info("Successfully assigned super_administrator role to {$user->name} ({$email}).");

        // 4. Ensure the session timeout default exists
        \App\Models\SystemSetting::firstOrCreate(
            ['key' => 'session_idle_timeout_minutes'],
            ['value' => 20]
        );
        $this->info("Seeded default session idle timeout (20 minutes).");

        return 0;
    }
}
