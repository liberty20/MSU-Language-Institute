<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ImpersonationLog;
use App\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ImpersonationTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $targetUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::firstOrCreate(['name' => 'super_administrator', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);

        // Create users
        $this->admin = User::create([
            'name' => 'Liberty Mapfumo',
            'email' => 'mapfumol@staff.msu.ac.zw',
            'password' => bcrypt('password'),
            'primary_category' => 'Staff',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $this->admin->assignRole('super_administrator');

        $this->targetUser = User::create([
            'name' => 'Test Student',
            'email' => 'student@example.com',
            'password' => bcrypt('password'),
            'primary_category' => 'Student',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $this->targetUser->assignRole('student');
    }

    public function test_super_admin_can_impersonate_user_with_reason()
    {
        // 1. Authenticate as Super Admin
        $this->actingAs($this->admin);

        // Pre-auth confirmation session variable is needed
        session(['auth.password_confirmed_at' => time()]);

        // 2. Start impersonation
        $response = $this->post(route('admin.impersonate.start', $this->targetUser->id), [
            'reason' => 'Testing student account troubleshooting',
        ]);

        $response->assertRedirect(route('dashboard'));

        // Assert session has correct keys
        $this->assertEquals($this->targetUser->id, session('impersonating_user_id'));
        $this->assertEquals($this->admin->id, session('impersonator_id'));

        // 3. Make request to dashboard and assert we see impersonated user
        $response = $this->get(route('dashboard'));

        // Because targetUser is a Student, it redirects to student.courses
        $response->assertRedirect(route('student.courses'));

        // 4. Assert impersonation audit log was created
        $this->assertDatabaseHas('impersonation_logs', [
            'impersonator_id' => $this->admin->id,
            'impersonated_id' => $this->targetUser->id,
            'reason' => 'Testing student account troubleshooting',
        ]);

        // 5. Assert activity log has been recorded
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->admin->id,
            'action' => 'impersonation_started',
        ]);
    }

    public function test_super_admin_can_stop_impersonating()
    {
        $this->actingAs($this->admin);
        session([
            'auth.password_confirmed_at' => time(),
            'impersonating_user_id' => $this->targetUser->id,
            'impersonator_id' => $this->admin->id,
            'impersonator_name' => $this->admin->name,
            'impersonation_log_id' => ImpersonationLog::create([
                'impersonator_id' => $this->admin->id,
                'impersonated_id' => $this->targetUser->id,
                'reason' => 'Test reason',
                'started_at' => now(),
            ])->id,
        ]);

        $response = $this->post(route('admin.impersonate.stop'));
        $response->assertRedirect(route('dashboard'));

        // Assert session keys are cleared
        $this->assertNull(session('impersonating_user_id'));
        $this->assertNull(session('impersonator_id'));

        // Assert audit ended_at is set
        $this->assertDatabaseHas('impersonation_logs', [
            'impersonator_id' => $this->admin->id,
            'impersonated_id' => $this->targetUser->id,
        ]);
        
        $log = ImpersonationLog::first();
        $this->assertNotNull($log->ended_at);
        $this->assertNotNull($log->duration_seconds);
    }

    public function test_super_administrator_cannot_be_deleted_or_suspended()
    {
        $this->actingAs($this->admin);

        // 1. Try to delete the Super Administrator user
        try {
            $this->admin->delete();
            $this->fail('Deleting a Super Administrator user did not throw an exception.');
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals('The Super Administrator account cannot be deleted.', $e->getMessage());
        }

        // 2. Try to suspend/deactivate the Super Administrator user
        try {
            $this->admin->is_active = false;
            $this->admin->save();
            $this->fail('Deactivating a Super Administrator user did not throw an exception.');
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals('The Super Administrator account cannot be suspended.', $e->getMessage());
        }

        // 3. Try to remove the super_administrator role
        try {
            $this->admin->syncRoles(['student']);
            $this->fail('Removing the super_administrator role did not throw an exception.');
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals('The Super Administrator role cannot be removed from this user.', $e->getMessage());
        }

        // 4. Try to delete the super_administrator Spatie role itself
        $role = Role::findByName('super_administrator', 'web');
        try {
            $role->delete();
            $this->fail('Deleting the super_administrator role did not throw an exception.');
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals('The Super Administrator role cannot be deleted.', $e->getMessage());
        }

        // 5. Try to rename the super_administrator Spatie role
        try {
            $role->name = 'modified_role_name';
            $role->save();
            $this->fail('Renaming the super_administrator role did not throw an exception.');
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals('The Super Administrator role cannot be renamed or modified.', $e->getMessage());
        }
    }
}
