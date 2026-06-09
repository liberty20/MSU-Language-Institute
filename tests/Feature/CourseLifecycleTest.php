<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Course;
use App\Models\CourseIntake;
use App\Models\CourseEnrollment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CourseLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected $student;
    protected $course;
    protected $intake;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\UnitsSeeder::class);
        $this->seed(\Database\Seeders\MsunliHierarchySeeder::class);

        $this->student = User::create([
            'name' => 'John Student',
            'email' => 'student@example.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $this->student->assignRole('student');

        $this->course = Course::create([
            'title' => 'Lifecycle Shona Course',
            'code' => 'SHO-LIFE',
            'category' => 'Shona',
            'price' => 100.00,
            'currency' => 'USD',
            'duration_weeks' => 6,
            'is_published' => true,
        ]);
    }

    /** @test */
    public function command_transitions_expired_intakes_to_completed()
    {
        $expiredIntake = CourseIntake::create([
            'course_id' => $this->course->id,
            'name' => 'Expired Intake',
            'start_date' => '2026-05-01',
            'end_date' => '2026-06-01', // Ended in the past
            'capacity' => 20,
            'status' => 'open',
        ]);

        $this->artisan('courses:update-statuses')
            ->assertExitCode(0);

        $this->assertEquals('completed', $expiredIntake->fresh()->status);
    }

    /** @test */
    public function student_cannot_enroll_into_completed_or_closed_intake()
    {
        $completedIntake = CourseIntake::create([
            'course_id' => $this->course->id,
            'name' => 'Completed Intake',
            'start_date' => '2026-05-01',
            'end_date' => '2026-06-01',
            'capacity' => 20,
            'status' => 'completed',
        ]);

        $file = UploadedFile::fake()->image('proof.jpg');

        $response = $this->actingAs($this->student)
            ->post(route('courses.apply'), [
                'course_intake_id' => $completedIntake->id,
                'full_name' => 'Applicant Name',
                'national_id_number' => 'ID-123',
                'national_id_copy' => UploadedFile::fake()->create('id.pdf', 100),
                'email' => 'applicant@example.com',
                'phone' => '123456789',
                'physical_address' => 'Gweru',
                'payment_proof' => $file,
            ]);

        $response->assertSessionHas('error');
        $this->assertFalse(CourseEnrollment::where('course_intake_id', $completedIntake->id)->exists());
    }
}
