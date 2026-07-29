<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ServiceRequest;
use App\Models\Client;
use App\Models\Department;
use App\Models\Course;
use App\Models\CourseIntake;
use App\Models\CourseEnrollment;
use App\Models\CourseTimetable;
use App\Models\CourseAttendance;
use App\Models\CourseApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RBACAndWorkflowEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    protected $director;
    protected $instructor;
    protected $student;
    protected $secretary;
    protected $adminAssistant;
    protected $coordinator;
    protected $client;
    protected $intake;
    protected $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\UnitsSeeder::class);
        $this->seed(\Database\Seeders\MsunliHierarchySeeder::class);
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        // Users & Roles
        $this->director = User::factory()->create(['name' => 'Director', 'primary_category' => 'Management']);
        $this->director->assignRole('executive_director');

        $this->instructor = User::factory()->create(['name' => 'Instructor', 'primary_category' => 'Staff']);
        $this->instructor->assignRole('language_expert');

        $this->student = User::factory()->create(['name' => 'Student', 'primary_category' => 'Student']);
        $this->student->assignRole('student');

        $this->secretary = User::factory()->create(['name' => 'Secretary', 'primary_category' => 'Administration']);
        $this->secretary->assignRole('secretary');

        $this->adminAssistant = User::factory()->create(['name' => 'Admin Assistant', 'primary_category' => 'Administration']);
        $this->adminAssistant->assignRole('admin_assistant');

        $this->coordinator = User::factory()->create(['name' => 'Coordinator', 'primary_category' => 'Administration']);
        $this->coordinator->assignRole('coordinator');

        $this->client = Client::create([
            'contact_person' => 'Jane Client',
            'email' => 'jane@client.com',
            'phone' => '7890',
            'client_type' => 'individual',
            'is_active' => true,
        ]);

        // Create Course and Intake assigned to Instructor
        $this->course = Course::create([
            'title' => 'Advanced English',
            'code' => 'ENG401',
            'price' => 150.00,
            'duration_weeks' => 6,
            'category' => 'languages',
            'is_published' => true,
        ]);

        $this->intake = CourseIntake::create([
            'course_id' => $this->course->id,
            'name' => 'Cohort A',
            'start_date' => now()->subWeeks(2)->toDateString(),
            'end_date' => now()->addWeeks(4)->toDateString(),
            'capacity' => 20,
            'status' => 'open',
            'instructor_id' => $this->instructor->id,
        ]);
    }

    /** @test */
    public function test_director_can_access_service_requests()
    {
        $request = ServiceRequest::create([
            'title' => 'Test Request',
            'description' => 'Test Description',
            'client_id' => $this->client->id,
            'service_category' => 'translation',
            'source_language' => 'English',
            'target_language' => 'French',
            'document_type' => 'document',
            'word_count' => 100,
            'priority' => 'low',
            'status' => 'pending',
            'reference_number' => 'REQ-TEST-001',
            'department_id' => 1,
        ]);

        $this->actingAs($this->director);

        // Assert Director can view index & details
        $response = $this->get(route('service-requests.index'));
        $response->assertStatus(200);

        $response = $this->get(route('service-requests.show', $request->id));
        $response->assertStatus(200);
    }

    /** @test */
    public function test_instructor_can_extend_enrollment()
    {
        $enrollment = CourseEnrollment::create([
            'course_intake_id' => $this->intake->id,
            'user_id' => $this->student->id,
            'payment_status' => 'verified',
            'enrollment_status' => 'active',
        ]);

        $this->actingAs($this->instructor);

        $newDate = now()->addWeeks(6)->toDateString();

        $response = $this->post(route('instructor.enrollments.extend', $enrollment->id), [
            'new_expiry_date' => $newDate,
            'reason' => 'Need more time for assignments',
        ]);

        $response->assertStatus(302);
        
        $enrollment->refresh();
        /** @var \Carbon\Carbon $accessUntil */
        $accessUntil = $enrollment->access_until;
        $this->assertEquals($newDate, $accessUntil->toDateString());
        $this->assertEquals('Need more time for assignments', $enrollment->extension_reason);

        // Verify audit log exists
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'enrollment_extended',
            'subject_id' => $enrollment->id,
        ]);
    }

    /** @test */
    public function test_student_access_period_expiration()
    {
        // 1. Expired Enrollment (Intake end date in past)
        $pastIntake = CourseIntake::create([
            'course_id' => $this->course->id,
            'name' => 'Cohort Past',
            'start_date' => now()->subWeeks(6)->toDateString(),
            'end_date' => now()->subWeeks(2)->toDateString(),
            'capacity' => 20,
            'status' => 'open',
            'instructor_id' => $this->instructor->id,
        ]);

        $enrollment = CourseEnrollment::create([
            'course_intake_id' => $pastIntake->id,
            'user_id' => $this->student->id,
            'payment_status' => 'verified',
            'enrollment_status' => 'active',
        ]);

        // Add timetable
        $timetable = CourseTimetable::create([
            'course_intake_id' => $pastIntake->id,
            'date' => now()->addDays(2)->toDateString(),
            'start_time' => '10:00',
            'end_time' => '12:00',
            'venue' => 'Room 101',
        ]);

        $this->assertTrue($enrollment->is_expired);

        $this->actingAs($this->student);

        // Fetch student timetable: should not see expired course timetable
        $response = $this->get(route('student.timetable'));
        $response->assertStatus(200);
        
        $props = $response->original->getData()['page']['props'];
        $this->assertCount(0, $props['timetables']);

        // 2. Instructor extends access
        $this->actingAs($this->instructor);
        $this->post(route('instructor.enrollments.extend', $enrollment->id), [
            'new_expiry_date' => now()->addWeeks(2)->toDateString(),
            'reason' => 'Access extended',
        ]);

        $enrollment->refresh();
        $this->assertFalse($enrollment->is_expired);

        // Student fetches again: now should see timetable
        $this->actingAs($this->student);
        $response = $this->get(route('student.timetable'));
        $props = $response->original->getData()['page']['props'];
        $this->assertCount(1, $props['timetables']);
    }

    /** @test */
    public function test_instructor_attendance_management()
    {
        $enrollment = CourseEnrollment::create([
            'course_intake_id' => $this->intake->id,
            'user_id' => $this->student->id,
            'payment_status' => 'verified',
            'enrollment_status' => 'active',
        ]);

        $timetable = CourseTimetable::create([
            'course_intake_id' => $this->intake->id,
            'date' => now()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'venue' => 'Room A',
        ]);

        // 1. Authorized instructor records attendance
        $this->actingAs($this->instructor);

        $response = $this->post(route('instructor.attendance.record'), [
            'course_timetable_id' => $timetable->id,
            'attendance' => [
                [
                    'student_id' => $this->student->id,
                    'status' => 'present',
                    'remarks' => 'On time',
                ]
            ]
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('course_attendances', [
            'course_timetable_id' => $timetable->id,
            'user_id' => $this->student->id,
            'status' => 'present',
            'remarks' => 'On time',
        ]);

        // 2. Attendance index displays records
        $response = $this->get(route('instructor.attendance.index', [
            'intake_id' => $this->intake->id,
            'session_id' => $timetable->id,
        ]));
        $response->assertStatus(200);

        // 3. Unauthorized user blocked
        $this->actingAs($this->student);
        $response = $this->post(route('instructor.attendance.record'), [
            'course_timetable_id' => $timetable->id,
            'attendance' => []
        ]);
        $response->assertStatus(403);
    }

    /** @test */
    public function test_application_workflow_verification_and_return()
    {
        $application = CourseApplication::create([
            'course_intake_id' => $this->intake->id,
            'full_name' => 'Applicant Name',
            'national_id_number' => '12345678X',
            'email' => 'applicant@test.com',
            'phone' => '12345',
            'physical_address' => 'Test Address',
            'national_id_copy_path' => 'test.pdf',
            'payment_proof_path' => 'proof.pdf',
            'status' => 'pending',
        ]);

        // 1. Secretary can verify
        $this->actingAs($this->secretary);
        $response = $this->post(route('course-applications.verify', $application->id), [
            'temporary_password' => 'Pass123!',
            'comment' => 'Verified ID and proof',
        ]);

        $response->assertStatus(302);
        $application->refresh();
        $this->assertEquals('verified', $application->status);

        // Reset to pending for return test
        $application->update(['status' => 'pending']);

        // 2. Secretary can return for correction
        $response = $this->post(route('course-applications.return', $application->id), [
            'comment' => 'Missing clear photocopy of national ID.',
        ]);

        $response->assertStatus(302);
        $application->refresh();
        $this->assertEquals('returned', $application->status);

        // 3. Verifying officer can verify a returned application
        $response = $this->post(route('course-applications.verify', $application->id), [
            'temporary_password' => 'NewPass123!',
            'comment' => 'Verified corrected document',
        ]);

        $response->assertStatus(302);
        $application->refresh();
        $this->assertEquals('verified', $application->status);
    }
}
