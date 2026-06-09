<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Course;
use App\Models\CourseIntake;
use App\Models\CourseEnrollment;
use App\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManualEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $course;
    protected $intake;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\UnitsSeeder::class);
        $this->seed(\Database\Seeders\MsunliHierarchySeeder::class);

        $this->admin = User::where('email', 'admin@msunli.edu')->first();
        if (!$this->admin) {
            $this->admin = User::create([
                'name' => 'ICT Admin',
                'email' => 'admin.test@msunli.edu',
                'password' => bcrypt('password'),
                'is_active' => true,
            ]);
            $this->admin->assignRole('ict_administrator');
        }

        $this->course = Course::create([
            'title' => 'Professional Shona Course',
            'code' => 'SHO-PROF',
            'category' => 'Shona',
            'price' => 100.00,
            'currency' => 'USD',
            'duration_weeks' => 6,
            'is_published' => true,
        ]);

        $this->intake = CourseIntake::create([
            'course_id' => $this->course->id,
            'name' => 'Intake A',
            'start_date' => '2026-06-01',
            'end_date' => '2026-07-31',
            'capacity' => 20,
            'status' => 'open',
        ]);
    }

    /** @test */
    public function admin_can_manually_enroll_new_student()
    {
        $response = $this->actingAs($this->admin)
            ->post(route('course-enrollments.manual'), [
                'course_intake_id' => $this->intake->id,
                'name' => 'John Walkin',
                'email' => 'john.walkin@example.com',
                'phone' => '123456789',
            ]);

        $response->assertRedirect();
        
        $user = User::where('email', 'john.walkin@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('John Walkin', $user->name);
        $this->assertTrue($user->hasRole('student'));

        $enrollment = CourseEnrollment::where('user_id', $user->id)
            ->where('course_intake_id', $this->intake->id)
            ->first();
        $this->assertNotNull($enrollment);
        $this->assertEquals('verified', $enrollment->payment_status);
        $this->assertEquals('active', $enrollment->enrollment_status);

        // Verify ActivityLog
        $log = ActivityLog::where('action', 'manual_enrollment')->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('John Walkin', $log->description);
    }

    /** @test */
    public function admin_can_manually_enroll_existing_user()
    {
        $student = User::create([
            'name' => 'Existing Student',
            'email' => 'existing@example.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('course-enrollments.manual'), [
                'course_intake_id' => $this->intake->id,
                'user_id' => $student->id,
            ]);

        $response->assertRedirect();

        $this->assertTrue($student->fresh()->hasRole('student'));

        $enrollment = CourseEnrollment::where('user_id', $student->id)
            ->where('course_intake_id', $this->intake->id)
            ->first();
        $this->assertNotNull($enrollment);
        $this->assertEquals('verified', $enrollment->payment_status);
    }
}
