<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Client;
use App\Models\Course;
use App\Models\CourseIntake;
use App\Models\CourseEnrollment;
use App\Models\Department;
use App\Models\CourseCaMark;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CourseEnrollmentsReportTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $studentUser;
    protected $intake;
    protected $course;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup Roles
        Role::findOrCreate('ict_administrator');
        Role::findOrCreate('admin_assistant');
        Role::findOrCreate('student');

        $this->adminUser = User::factory()->create([
            'name' => 'ICT Administrator',
            'email' => 'admin@msunli.edu',
        ]);
        $this->adminUser->assignRole('ict_administrator');

        $this->studentUser = User::factory()->create([
            'name' => 'Test Student',
            'email' => 'student@test.com',
        ]);
        $this->studentUser->assignRole('student');

        // Create Course & Intake
        $dept = Department::create([
            'name' => 'Language Technology Unit',
            'code' => 'LTU',
        ]);

        $this->course = Course::create([
            'title' => 'Basic Zimbabwean Sign Language',
            'code' => 'ZSL-101',
            'category' => 'Sign Language',
            'department_id' => $dept->id,
            'price' => 250.00,
            'currency' => 'USD',
            'duration_weeks' => 12,
            'is_published' => true,
        ]);

        $this->intake = CourseIntake::create([
            'course_id' => $this->course->id,
            'name' => 'Winter Batch 2026',
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(30),
            'status' => 'open',
            'capacity' => 30,
        ]);

        // Create Enrollment
        $enrollment = CourseEnrollment::create([
            'course_intake_id' => $this->intake->id,
            'user_id' => $this->studentUser->id,
            'enrollment_status' => 'active',
            'payment_status' => 'verified',
            'amount_paid' => 250.00,
        ]);

        // Create assessment CA mark
        CourseCaMark::create([
            'course_intake_id' => $this->intake->id,
            'user_id' => $this->studentUser->id,
            'assessment_name' => 'Final Exam',
            'marks_obtained' => 85,
            'max_marks' => 100,
        ]);
    }

    /**
     * Test admin can access enrollment page and get correct analytics.
     */
    public function test_admin_can_access_enrollment_dashboard_and_analytics()
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('course-enrollments.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Courses/Enrollments')
            ->has('summaryCards')
            ->where('summaryCards.total_courses', 1)
            ->where('summaryCards.total_enrolled_students', 1)
            ->where('summaryCards.overall_pass_rate', 100)
            ->has('intakes')
        );
    }

    /**
     * Test filter options limit results correctly.
     */
    public function test_enrollment_filtering_by_course_code_and_pass_rate()
    {
        $this->actingAs($this->adminUser);

        // Filter by matching code
        $response = $this->get(route('course-enrollments.index', ['course' => 'ZSL-101']));
        $response->assertStatus(200);
        $this->assertCount(1, $response->original->getData()['page']['props']['intakes']);

        // Filter by non-matching code
        $response = $this->get(route('course-enrollments.index', ['course' => 'NON-EXISTENT']));
        $response->assertStatus(200);
        $this->assertCount(0, $response->original->getData()['page']['props']['intakes']);
    }

    /**
     * Test PDF and CSV reports can be exported successfully.
     */
    public function test_can_export_enrollment_csv_and_pdf_reports()
    {
        $this->actingAs($this->adminUser);

        // Test CSV format export
        $response = $this->get(route('course-enrollments.export', [
            'report_type' => 'course_performance',
            'format' => 'csv',
        ]));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('attachment; filename="course_course_performance_report_', $response->headers->get('Content-Disposition'));

        // Test PDF format export
        $response = $this->get(route('course-enrollments.export', [
            'report_type' => 'pass_rate_analysis',
            'format' => 'pdf',
        ]));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }
}
