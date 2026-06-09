<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Course;
use App\Models\CourseIntake;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DataPersistenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed roles, permissions, and basic structure
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\UnitsSeeder::class);
        $this->seed(\Database\Seeders\MsunliHierarchySeeder::class);

        // Disable CSRF verification for tests
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        // Ensure we start with a clean backup file or mock path
        $backupPath = \App\Services\UserBackupService::getBackupFilePath();
        if (file_exists($backupPath)) {
            unlink($backupPath);
        }
    }

    protected function tearDown(): void
    {
        $backupPath = \App\Services\UserBackupService::getBackupFilePath();
        if (file_exists($backupPath)) {
            unlink($backupPath);
        }
        parent::tearDown();
    }

    /** @test */
    public function assigning_spatie_role_updates_backup_json()
    {
        $user = User::factory()->create([
            'email' => 'student.test@msunli.edu',
            'is_active' => true,
        ]);

        $backupPath = \App\Services\UserBackupService::getBackupFilePath();
        
        // Delete backup file created by user creation/seeding to isolate this check
        if (file_exists($backupPath)) {
            unlink($backupPath);
        }
        $this->assertFileDoesNotExist($backupPath);

        // Assigning student role should trigger backup
        $user->assignRole('student');

        $this->assertFileExists($backupPath);
        $backupData = json_decode(file_get_contents($backupPath), true);

        // Check that the user has the 'student' role in backup
        $this->assertNotEmpty($backupData['model_has_roles']);
        $studentRole = Role::findByName('student', 'web');
        
        $found = false;
        foreach ($backupData['model_has_roles'] as $record) {
            if ($record['model_id'] == $user->id && $record['role_id'] == $studentRole->id) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, "Student role association should be backed up.");
    }

    /** @test */
    public function removing_spatie_role_updates_backup_json()
    {
        $user = User::factory()->create([
            'email' => 'student.test@msunli.edu',
            'is_active' => true,
        ]);
        $user->assignRole('student');

        // Clear backup file to test deletion trigger
        $backupPath = \App\Services\UserBackupService::getBackupFilePath();
        if (file_exists($backupPath)) {
            unlink($backupPath);
        }

        $user->removeRole('student');

        $this->assertFileExists($backupPath);
        $backupData = json_decode(file_get_contents($backupPath), true);

        $studentRole = Role::findByName('student', 'web');
        $found = false;
        foreach ($backupData['model_has_roles'] as $record) {
            if ($record['model_id'] == $user->id && $record['role_id'] == $studentRole->id) {
                $found = true;
                break;
            }
        }
        $this->assertFalse($found, "Student role association should be removed from backup.");
    }

    /** @test */
    public function course_and_intake_modifications_trigger_backup()
    {
        $backupPath = \App\Services\UserBackupService::getBackupFilePath();
        
        // Clear backup file before test
        if (file_exists($backupPath)) {
            unlink($backupPath);
        }
        $this->assertFileDoesNotExist($backupPath);

        // Create a course
        $course = Course::create([
            'title' => 'Custom Swahili Course',
            'code' => 'SWA-CUST',
            'category' => 'Swahili',
            'description' => 'A custom swahili course',
            'price' => 100.00,
            'currency' => 'USD',
            'duration_weeks' => 4,
            'is_published' => true,
        ]);

        $this->assertFileExists($backupPath);
        $backupData = json_decode(file_get_contents($backupPath), true);
        $this->assertNotEmpty($backupData['courses']);
        
        $titles = array_column($backupData['courses'], 'title');
        $this->assertContains('Custom Swahili Course', $titles);

        // Clear backup path to test intake save trigger
        unlink($backupPath);

        $intake = CourseIntake::create([
            'course_id' => $course->id,
            'name' => 'Custom July Batch',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
            'capacity' => 20,
            'status' => 'open',
        ]);

        $this->assertFileExists($backupPath);
        $backupData = json_decode(file_get_contents($backupPath), true);
        $this->assertNotEmpty($backupData['course_intakes']);
        
        $names = array_column($backupData['course_intakes'], 'name');
        $this->assertContains('Custom July Batch', $names);
    }

    /** @test */
    public function duplicate_course_intakes_are_blocked_by_validation()
    {
        $admin = User::factory()->create([
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $admin->assignRole('ict_administrator');

        $course = Course::create([
            'title' => 'Validation ZSL Course',
            'code' => 'ZSL-VAL',
            'category' => 'Sign Language',
            'price' => 150.00,
            'currency' => 'USD',
            'duration_weeks' => 8,
            'is_published' => true,
        ]);

        // Create initial intake
        CourseIntake::create([
            'course_id' => $course->id,
            'name' => 'Summer Intake',
            'start_date' => '2026-06-01',
            'end_date' => '2026-07-31',
            'capacity' => 30,
            'status' => 'open',
        ]);

        // Attempting to create duplicate intake via controller route should fail validation
        $response = $this->actingAs($admin)
            ->from(route('courses.index'))
            ->post(route('course-intakes.store'), [
                'course_id' => $course->id,
                'name' => 'Summer Intake',
                'start_date' => '2026-06-01',
                'end_date' => '2026-07-31',
                'capacity' => 25,
                'status' => 'open',
            ]);

        $response->assertRedirect(route('courses.index'));
        $response->assertSessionHasErrors(['name']);

        // Check that only 1 intake exists in the DB
        $this->assertEquals(1, CourseIntake::where('course_id', $course->id)->count());
    }

    /** @test */
    public function updating_to_duplicate_course_intake_is_blocked_by_validation()
    {
        $admin = User::factory()->create([
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $admin->assignRole('ict_administrator');

        $course = Course::create([
            'title' => 'Validation ZSL Course 2',
            'code' => 'ZSL-VAL-2',
            'category' => 'Sign Language',
            'price' => 150.00,
            'currency' => 'USD',
            'duration_weeks' => 8,
            'is_published' => true,
        ]);

        $intake1 = CourseIntake::create([
            'course_id' => $course->id,
            'name' => 'Summer Intake A',
            'start_date' => '2026-06-01',
            'end_date' => '2026-07-31',
            'capacity' => 30,
            'status' => 'open',
        ]);

        $intake2 = CourseIntake::create([
            'course_id' => $course->id,
            'name' => 'Summer Intake B',
            'start_date' => '2026-08-01',
            'end_date' => '2026-09-30',
            'capacity' => 30,
            'status' => 'open',
        ]);

        // Attempting to update intake2 to match intake1's unique fields should fail validation
        $response = $this->actingAs($admin)
            ->from(route('courses.index'))
            ->put(route('course-intakes.update', $intake2), [
                'name' => 'Summer Intake A',
                'start_date' => '2026-06-01',
                'end_date' => '2026-07-31',
                'capacity' => 30,
                'status' => 'open',
            ]);

        $response->assertRedirect(route('courses.index'));
        $response->assertSessionHasErrors(['name']);
    }
}
