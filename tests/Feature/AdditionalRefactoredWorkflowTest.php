<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Course;
use App\Models\CourseIntake;
use App\Models\CourseApplication;
use App\Models\Department;
use App\Models\ServiceRequest;
use App\Models\Client;
use App\Models\Assignment;
use App\Services\ReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdditionalRefactoredWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected $coordinator;
    protected $aosStaff;
    protected $tuStaff;
    protected $adminAssistant;
    protected $deputyDirector;
    protected $ictAdmin;
    protected $aosDepartment;
    protected $tuDepartment;
    protected $serviceRequest;
    protected $client;
    protected $course;
    protected $intake;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\UnitsSeeder::class);
        $this->seed(\Database\Seeders\MsunliHierarchySeeder::class);
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        // Set up Departments
        $this->aosDepartment = Department::create(['name' => 'AOS Support', 'code' => 'AOS']);
        $this->tuDepartment = Department::create(['name' => 'Translation Unit', 'code' => 'TU']);

        // Set up Roles / Users
        $this->coordinator = User::factory()->create([
            'name' => 'AOS Coordinator',
            'email' => 'coordinator@example.com',
            'department_id' => $this->aosDepartment->id,
            'primary_category' => 'Administration',
            'is_active' => true,
        ]);
        $this->coordinator->assignRole('coordinator');

        $this->aosStaff = User::factory()->create([
            'name' => 'AOS Staff Member',
            'email' => 'aos_staff@example.com',
            'department_id' => $this->aosDepartment->id,
            'primary_category' => 'Staff',
            'is_active' => true,
        ]);
        $this->aosStaff->assignRole('language_expert');

        $this->tuStaff = User::factory()->create([
            'name' => 'TU Staff Member',
            'email' => 'tu_staff@example.com',
            'department_id' => $this->tuDepartment->id,
            'primary_category' => 'Staff',
            'is_active' => true,
        ]);
        $this->tuStaff->assignRole('language_expert');

        $this->adminAssistant = User::factory()->create([
            'name' => 'Admin Assistant',
            'email' => 'aa@example.com',
            'primary_category' => 'Administration',
            'is_active' => true,
        ]);
        $this->adminAssistant->assignRole('admin_assistant');

        $this->deputyDirector = User::factory()->create([
            'name' => 'Deputy Director',
            'email' => 'dd@example.com',
            'primary_category' => 'Management',
            'is_active' => true,
        ]);
        $this->deputyDirector->assignRole('deputy_director');

        $this->ictAdmin = User::factory()->create([
            'name' => 'ICT Admin',
            'email' => 'ict@example.com',
            'primary_category' => 'Staff',
            'is_active' => true,
        ]);
        $this->ictAdmin->assignRole('ict_administrator');

        // Create Client & Service Request for assignment tests
        $this->client = Client::create([
            'contact_person' => 'Client User',
            'email' => 'client@test.com',
            'phone' => '123456',
            'client_type' => 'individual',
            'is_active' => true,
        ]);

        $this->serviceRequest = ServiceRequest::create([
            'client_id' => $this->client->id,
            'title' => 'Test Service Request',
            'description' => 'Test desc',
            'status' => 'pending',
            'service_category' => 'translation',
        ]);

        // Create Course and Intake
        $this->course = Course::create([
            'title' => 'Test Course',
            'code' => 'TEST-101',
            'category' => 'English',
            'price' => 120.00,
            'currency' => 'USD',
            'duration_weeks' => 4,
            'is_published' => true,
        ]);

        $this->intake = CourseIntake::create([
            'course_id' => $this->course->id,
            'name' => 'January 2026 Batch',
            'start_date' => '2026-01-01',
            'end_date' => '2026-02-01',
            'capacity' => 30,
            'status' => 'open',
        ]);
    }

    /** @test */
    public function coordinator_cannot_assign_tasks_to_themselves()
    {
        $response = $this->actingAs($this->coordinator)
            ->post(route('assignments.store'), [
                'service_request_id' => $this->serviceRequest->id,
                'assign_to_type' => 'coordinator',
                'assigned_to' => $this->coordinator->id,
                'notes' => 'Self assignment',
            ]);

        $response->assertSessionHasErrors(['assigned_to']);
        $this->assertFalse(Assignment::where('assigned_to', $this->coordinator->id)->exists());
    }

    /** @test */
    public function coordinator_cannot_assign_tasks_to_staff_in_aos_unit()
    {
        $response = $this->actingAs($this->coordinator)
            ->post(route('assignments.store'), [
                'service_request_id' => $this->serviceRequest->id,
                'assign_to_type' => 'staff',
                'assigned_to' => $this->aosStaff->id,
                'notes' => 'Assigning to AOS staff',
            ]);

        $response->assertSessionHasErrors(['assigned_to']);
        $this->assertFalse(Assignment::where('assigned_to', $this->aosStaff->id)->exists());
    }

    /** @test */
    public function coordinator_can_assign_tasks_to_eligible_staff_outside_aos_unit()
    {
        $response = $this->actingAs($this->coordinator)
            ->post(route('assignments.store'), [
                'service_request_id' => $this->serviceRequest->id,
                'assign_to_type' => 'staff',
                'assigned_to' => $this->tuStaff->id,
                'notes' => 'Assigning to TU staff',
            ]);

        $response->assertRedirect(route('assignments.index'));
        $this->assertTrue(Assignment::where('assigned_to', $this->tuStaff->id)->exists());
    }

    /** @test */
    public function course_application_workflow_bypasses_recommendation_and_allows_coordinator_to_approve()
    {
        Notification::fake();

        // 1. Create a course application
        $app = CourseApplication::create([
            'course_intake_id' => $this->intake->id,
            'full_name' => 'Jane Student',
            'national_id_number' => 'ID-5566',
            'national_id_copy_path' => 'ids/jane.pdf',
            'email' => 'jane@student.com',
            'phone' => '077123456',
            'physical_address' => 'Harare',
            'payment_proof_path' => 'payments/proof.jpg',
            'status' => 'pending',
        ]);

        // 2. Admin Assistant verifies the application
        $response = $this->actingAs($this->adminAssistant)
            ->post(route('course-applications.verify', $app->id), [
                'temporary_password' => 'JanePass123!',
                'comment' => 'Payment verified.',
            ]);

        $response->assertRedirect(route('course-applications.index'));
        $this->assertEquals('verified', $app->fresh()->status);

        // Verify that coordinators are notified
        Notification::assertSentTo($this->coordinator, \App\Notifications\SystemNotification::class);

        // 3. Deputy Director recommendation is deprecated (should abort 403)
        $recommendResponse = $this->actingAs($this->deputyDirector)
            ->post(route('course-applications.recommend', $app->id), [
                'comment' => 'Looks good',
            ]);
        $recommendResponse->assertStatus(403);

        // 4. Check ReminderService lists verified application as outstanding task for Coordinator
        $reminderService = resolve(ReminderService::class);
        $coordinatorTasks = $reminderService->getOutstandingTasks($this->coordinator);
        $taskIds = collect($coordinatorTasks)->pluck('id')->toArray();
        $this->assertContains("course-app-approve-{$app->id}", $taskIds);

        // Deputy Director and Executive Director should NOT see CourseApplication tasks
        $ddTasks = $reminderService->getOutstandingTasks($this->deputyDirector);
        $ddTaskIds = collect($ddTasks)->pluck('id')->toArray();
        $this->assertNotContains("course-app-recommend-{$app->id}", $ddTaskIds);

        $edUser = User::whereHas('roles', fn($q) => $q->where('name', 'executive_director'))->first();
        if ($edUser) {
            $edTasks = $reminderService->getOutstandingTasks($edUser);
            $edTaskIds = collect($edTasks)->pluck('id')->toArray();
            $this->assertNotContains("course-app-approve-{$app->id}", $edTaskIds);
        }

        // 5. Coordinator approves verified application directly
        $approveResponse = $this->actingAs($this->coordinator)
            ->post(route('course-applications.approve', $app->id), [
                'comment' => 'Approved by Coordinator.',
            ]);

        $approveResponse->assertSessionHasNoErrors();
        $this->assertEquals('enrolled', $app->fresh()->status);

        // 6. Verify enrollment and user creation
        $studentUser = User::where('email', 'jane@student.com')->first();
        $this->assertNotNull($studentUser);
        $this->assertTrue($studentUser->hasRole('student'));
        $this->assertDatabaseHas('course_enrollments', [
            'course_intake_id' => $this->intake->id,
            'user_id' => $studentUser->id,
            'enrollment_status' => 'active',
        ]);
    }

    /** @test */
    public function coordinator_can_access_dashboard_audit_trail_courses_quotations_and_notices_without_403()
    {
        // Coordinator can access Dashboard
        $response = $this->actingAs($this->coordinator)->get(route('dashboard'));
        $response->assertStatus(200);

        // Coordinator can access Audit Trail
        $response = $this->actingAs($this->coordinator)->get(route('admin.audit-trail'));
        $response->assertStatus(200);

        // Coordinator can access Courses index
        $response = $this->actingAs($this->coordinator)->get(route('courses.index'));
        $response->assertStatus(200);

        // Coordinator can access Quotations index
        $response = $this->actingAs($this->coordinator)->get(route('quotations.index'));
        $response->assertStatus(200);

        // Coordinator can access Notices index
        $response = $this->actingAs($this->coordinator)->get(route('notices.index'));
        $response->assertStatus(200);

        // Coordinator can access Reports index
        $response = $this->actingAs($this->coordinator)->get(route('reports.index'));
        $response->assertStatus(200);

        // Coordinator is blocked from Finance index
        $response = $this->actingAs($this->coordinator)->get(route('finance.index'));
        $response->assertStatus(403);
    }
}
