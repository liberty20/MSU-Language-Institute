<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ServiceRequest;
use App\Models\Client;
use App\Models\Department;
use App\Models\Quotation;
use App\Models\Assignment;
use App\Models\CcReview;
use App\Models\UploadedDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefactoredWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected $coordinator;
    protected $anotherCoordinator;
    protected $director;
    protected $adminAssistant;
    protected $secretary;
    protected $client;
    protected $aosDepartment;
    protected $otherDepartment;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\MsunliHierarchySeeder::class);
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        // Create Departments
        $this->aosDepartment = Department::create([
            'name' => 'Administration and Operations Support',
            'code' => 'AOS',
        ]);
        $this->otherDepartment = Department::create([
            'name' => 'Translation Unit',
            'code' => 'TU',
        ]);

        // Create Users
        $this->coordinator = User::factory()->create([
            'name' => 'AOS Coordinator',
            'email' => 'coordinator@example.com',
            'department_id' => $this->aosDepartment->id,
            'primary_category' => 'Administration',
            'is_active' => true,
        ]);
        $this->coordinator->assignRole('coordinator');

        $this->anotherCoordinator = User::factory()->create([
            'name' => 'Other Coordinator',
            'email' => 'other_coord@example.com',
            'department_id' => $this->aosDepartment->id,
            'primary_category' => 'Administration',
            'is_active' => true,
        ]);
        $this->anotherCoordinator->assignRole('coordinator');

        $this->director = User::factory()->create([
            'name' => 'Executive Director',
            'email' => 'director@example.com',
            'primary_category' => 'Management',
            'is_active' => true,
        ]);
        $this->director->assignRole('executive_director');

        $this->adminAssistant = User::factory()->create([
            'name' => 'Admin Assistant',
            'email' => 'aa@example.com',
            'primary_category' => 'Administration',
            'is_active' => true,
        ]);
        $this->adminAssistant->assignRole('admin_assistant');

        $this->secretary = User::factory()->create([
            'name' => 'Secretary',
            'email' => 'secretary@example.com',
            'primary_category' => 'Administration',
            'is_active' => true,
        ]);
        $this->secretary->assignRole('secretary');

        // Create Client
        $this->client = Client::create([
            'contact_person' => 'John Client',
            'email' => 'john@client.com',
            'phone' => '123456',
            'client_type' => 'individual',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function coordinator_has_expected_permissions_but_no_user_management_or_system_configuration()
    {
        // Coordinator can manage assignments
        $this->assertTrue($this->coordinator->hasPermissionTo('manage assignments'));
        $this->assertTrue($this->coordinator->hasPermissionTo('view assignments'));
        $this->assertTrue($this->coordinator->hasPermissionTo('view service requests'));

        // Coordinator CANNOT manage users or settings/system
        $this->assertFalse($this->coordinator->hasPermissionTo('manage users'));
        $this->assertFalse($this->coordinator->hasPermissionTo('manage system'));
    }

    /** @test */
    public function coordinator_assignment_creation_screens_exclude_aos_staff()
    {
        // Staff inside AOS
        $aosStaff = User::factory()->create([
            'name' => 'AOS Staff',
            'department_id' => $this->aosDepartment->id,
            'primary_category' => 'Administration',
            'is_active' => true,
        ]);
        $aosStaff->assignRole('part_time_staff');

        // Staff outside AOS
        $translationStaff = User::factory()->create([
            'name' => 'Translation Staff',
            'department_id' => $this->otherDepartment->id,
            'primary_category' => 'Language Services',
            'is_active' => true,
        ]);
        $translationStaff->assignRole('part_time_staff');

        // Get assignments create page
        $response = $this->actingAs($this->coordinator)->get(route('assignments.create'));
        $response->assertStatus(200);

        // Verify Translation Staff is returned/allowed, but AOS Staff is excluded
        $inertiaData = $response->original->getData()['page']['props'];
        $staffIds = collect($inertiaData['staff'])->pluck('id');

        $this->assertTrue($staffIds->contains($translationStaff->id));
        $this->assertFalse($staffIds->contains($aosStaff->id));
        $this->assertFalse($staffIds->contains($this->coordinator->id));
    }

    /** @test */
    public function full_deliverable_approval_workflow_lifecycle()
    {
        // 1. Setup Service Request Awaiting Coordinator Review
        $serviceRequest = ServiceRequest::create([
            'title' => 'Test Service Request',
            'description' => 'Test Description',
            'client_id' => $this->client->id,
            'service_category' => 'translation',
            'status' => 'review',
            'submitted_by' => $this->coordinator->id,
        ]);

        // Attach a final deliverable document uploaded by staff
        $assignment = Assignment::create([
            'service_request_id' => $serviceRequest->id,
            'assigned_to' => User::factory()->create()->id,
            'status' => 'completed',
        ]);

        $document = UploadedDocument::create([
            'filename' => 'final_translation.pdf',
            'file_path' => 'final_translation.pdf',
            'uploaded_by' => $assignment->assigned_to,
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
            'documentable_id' => $assignment->id,
            'documentable_type' => Assignment::class,
        ]);

        // 2. Coordinator CC Review request
        $responseCc = $this->actingAs($this->coordinator)->post(route('service-requests.cc-review', $serviceRequest->id), [
            'reviewer_ids' => [$this->anotherCoordinator->id],
            'notes' => 'Please review this translation.',
        ]);
        $responseCc->assertRedirect();
        
        $this->assertDatabaseHas('cc_reviews', [
            'service_request_id' => $serviceRequest->id,
            'sender_id' => $this->coordinator->id,
            'reviewer_id' => $this->anotherCoordinator->id,
            'status' => 'pending',
        ]);

        $this->assertTrue($this->anotherCoordinator->notifications()->where('data->type', 'cc_review_request')->exists());

        // 3. Reviewer Coordinator Responds to CC Review
        $ccReview = CcReview::first();
        $responseCcReply = $this->actingAs($this->anotherCoordinator)->post(route('cc-reviews.respond', $ccReview->id), [
            'comments' => 'Looks excellent to me!',
            'status' => 'approved',
        ]);
        $responseCcReply->assertRedirect();

        $this->assertDatabaseHas('cc_reviews', [
            'id' => $ccReview->id,
            'status' => 'reviewed',
            'comments' => 'Looks excellent to me!',
        ]);

        // 4. Coordinator forwards to Director
        $responseForward = $this->actingAs($this->coordinator)->post(route('service-requests.forward', $serviceRequest->id), [
            'director_id' => $this->director->id,
            'notes' => 'Ready for your final sign off.',
        ]);
        $responseForward->assertRedirect();

        $serviceRequest->refresh();
        $this->assertEquals('director_approval', $serviceRequest->status);
        $this->assertTrue($this->director->notifications()->where('data->type', 'director_approval_request')->exists());

        // 5. Director approves the request -> goes to admin_submission
        $responseApprove = $this->actingAs($this->director)->post(route('service-requests.director-approve', $serviceRequest->id), [
            'notes' => 'Approved.',
        ]);
        $responseApprove->assertRedirect();

        $serviceRequest->refresh();
        $this->assertEquals('admin_submission', $serviceRequest->status);
        $this->assertTrue($this->adminAssistant->notifications()->where('data->type', 'admin_submission_pending')->exists());

        // 6. Admin Assistant final client submission
        $quotation = Quotation::create([
            'service_request_id' => $serviceRequest->id,
            'amount' => 500.00,
            'currency' => 'USD',
            'status' => 'approved',
            'valid_until' => now()->addDays(30),
            'description' => 'Test quotation description',
            'generated_by' => $this->coordinator->id,
        ]);

        $clientUser = User::where('email', $this->client->email)->first();

        // First mock a verified payment so submission isn't blocked
        \DB::table('payments')->insert([
            'service_request_id' => $serviceRequest->id,
            'quotation_id' => $quotation->id,
            'client_id' => $clientUser ? $clientUser->id : User::factory()->create()->id,
            'amount_paid' => 500.00,
            'bank_used' => 'CBZ',
            'proof_file_path' => 'proofs/proof.jpg',
            'status' => 'verified',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $responseDeliver = $this->actingAs($this->adminAssistant)->post(route('service-requests.deliver', $serviceRequest->id), [
            'notes' => 'Sent to client.',
        ]);
        $responseDeliver->assertRedirect();

        $serviceRequest->refresh();
        $this->assertEquals('completed', $serviceRequest->status);

        // Check Director got notified of submission
        $this->director->refresh();
        $this->assertTrue($this->director->notifications()->where('type', 'App\Notifications\SystemNotification')->exists());

        // Verify Audit Trail Log
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'deliverable_submitted_to_client',
            'user_id' => $this->adminAssistant->id,
        ]);
    }

    /** @test */
    public function director_rejection_returns_to_review_and_notifies_coordinators()
    {
        $serviceRequest = ServiceRequest::create([
            'title' => 'Rejection SR',
            'description' => 'Rejection Description',
            'client_id' => $this->client->id,
            'service_category' => 'translation',
            'status' => 'director_approval',
            'submitted_by' => $this->coordinator->id,
        ]);

        $responseReject = $this->actingAs($this->director)->post(route('service-requests.director-reject', $serviceRequest->id), [
            'reason' => 'Formatting issues found on page 3.',
        ]);
        $responseReject->assertRedirect();

        $serviceRequest->refresh();
        $this->assertEquals('review', $serviceRequest->status);

        // Verify Coordinator got notified of rejection
        $this->assertEquals(1, $this->coordinator->notifications()->where('data->type', 'director_rejected_deliverable')->count());
    }

    /** @test */
    public function portal_endpoints_enforce_rbac_and_restricted_blockages()
    {
        // 1. Finance portal: Directors are blocked (403), Secretary and Admin Assistant are allowed
        $responseDir = $this->actingAs($this->director)->get(route('finance.index'));
        $responseDir->assertStatus(403);

        $responseAA = $this->actingAs($this->adminAssistant)->get(route('finance.index'));
        $responseAA->assertStatus(200);

        $responseSec = $this->actingAs($this->secretary)->get(route('finance.index'));
        $responseSec->assertStatus(200);

        // 2. Settings portal: Secretary/Admin Assistant allowed to index
        $responseSettingsSec = $this->actingAs($this->secretary)->get(route('admin.settings.index'));
        $responseSettingsSec->assertStatus(200);

        // Secretary/Admin Assistant blocked from mutating units, sections, roles
        $responseStoreUnit = $this->actingAs($this->secretary)->post(route('admin.settings.units.store'), [
            'name' => 'Forbidden Unit',
        ]);
        $responseStoreUnit->assertStatus(403);
    }

    /** @test */
    public function assignments_create_screen_returns_staff_and_coordinators_lists_correctly()
    {
        // 1. Setup inactive staff and active staff
        $activeStaff = User::factory()->create(['name' => 'Active Staff', 'is_active' => true]);
        $activeStaff->assignRole('part_time_staff');

        $inactiveStaff = User::factory()->create(['name' => 'Inactive Staff', 'is_active' => false]);
        $inactiveStaff->assignRole('part_time_staff');

        // 2. Setup active coordinator and inactive coordinator
        $activeCoordinator = User::factory()->create(['name' => 'Active Coordinator', 'is_active' => true]);
        $activeCoordinator->assignRole('coordinator');

        $inactiveCoordinator = User::factory()->create(['name' => 'Inactive Coordinator', 'is_active' => false]);
        $inactiveCoordinator->assignRole('coordinator');

        // 3. Act: Get assignments create page
        $response = $this->actingAs($this->director)->get(route('assignments.create'));
        $response->assertStatus(200);

        // 4. Assert: Get lists returned in Inertia
        $props = $response->original->getData()['page']['props'];
        $staffIds = collect($props['staff'])->pluck('id');
        $coordinatorIds = collect($props['coordinators'])->pluck('id');

        $this->assertTrue($staffIds->contains($activeStaff->id));
        $this->assertFalse($staffIds->contains($inactiveStaff->id));

        $this->assertTrue($coordinatorIds->contains($activeCoordinator->id));
        $this->assertFalse($coordinatorIds->contains($inactiveCoordinator->id));
    }

    /** @test */
    public function assignment_store_validates_recipient_active_status_and_role_mismatch()
    {
        $serviceRequest = ServiceRequest::create([
            'title' => 'Validation SR',
            'description' => 'Validation Description',
            'client_id' => $this->client->id,
            'service_category' => 'translation',
            'status' => 'pending',
            'submitted_by' => $this->director->id,
        ]);

        $activeStaff = User::factory()->create(['is_active' => true]);
        $activeStaff->assignRole('part_time_staff');

        $inactiveStaff = User::factory()->create(['is_active' => false]);
        $inactiveStaff->assignRole('part_time_staff');

        $activeCoordinator = User::factory()->create(['is_active' => true]);
        $activeCoordinator->assignRole('coordinator');

        // 1. Submit assignment with inactive recipient -> should fail
        $responseInactive = $this->actingAs($this->director)->post(route('assignments.store'), [
            'service_request_id' => $serviceRequest->id,
            'assign_to_type' => 'staff',
            'assigned_to' => $inactiveStaff->id,
            'role_in_task' => 'translator',
        ]);
        $responseInactive->assertSessionHasErrors(['assigned_to']);

        // 2. Submit with type 'coordinator' but recipient is staff -> should fail
        $responseMismatchCoord = $this->actingAs($this->director)->post(route('assignments.store'), [
            'service_request_id' => $serviceRequest->id,
            'assign_to_type' => 'coordinator',
            'assigned_to' => $activeStaff->id,
            'role_in_task' => 'translator',
        ]);
        $responseMismatchCoord->assertSessionHasErrors(['assigned_to']);

        // 3. Submit with type 'staff' but recipient is coordinator -> should fail
        $responseMismatchStaff = $this->actingAs($this->director)->post(route('assignments.store'), [
            'service_request_id' => $serviceRequest->id,
            'assign_to_type' => 'staff',
            'assigned_to' => $activeCoordinator->id,
            'role_in_task' => 'translator',
        ]);
        $responseMismatchStaff->assertSessionHasErrors(['assigned_to']);
    }

    /** @test */
    public function successful_assignment_creation_for_staff_and_coordinator()
    {
        $serviceRequest = ServiceRequest::create([
            'title' => 'Success SR',
            'description' => 'Success Description',
            'client_id' => $this->client->id,
            'service_category' => 'translation',
            'status' => 'pending',
            'submitted_by' => $this->director->id,
        ]);

        $activeStaff = User::factory()->create(['is_active' => true]);
        $activeStaff->assignRole('part_time_staff');

        $activeCoordinator = User::factory()->create(['is_active' => true]);
        $activeCoordinator->assignRole('coordinator');

        // 1. Successful staff assignment
        $responseStaff = $this->actingAs($this->director)->post(route('assignments.store'), [
            'service_request_id' => $serviceRequest->id,
            'assign_to_type' => 'staff',
            'assigned_to' => $activeStaff->id,
            'role_in_task' => 'translator',
            'notes' => 'Perform translation work.',
        ]);
        $responseStaff->assertRedirect(route('assignments.index'));
        $this->assertDatabaseHas('assignments', [
            'service_request_id' => $serviceRequest->id,
            'assigned_to' => $activeStaff->id,
            'status' => 'assigned',
        ]);

        // 2. Successful coordinator assignment
        $responseCoord = $this->actingAs($this->director)->post(route('assignments.store'), [
            'service_request_id' => $serviceRequest->id,
            'assign_to_type' => 'coordinator',
            'assigned_to' => $activeCoordinator->id,
            'role_in_task' => 'reviewer',
            'notes' => 'Perform coordinator review.',
        ]);
        $responseCoord->assertRedirect(route('assignments.index'));
        $this->assertDatabaseHas('assignments', [
            'service_request_id' => $serviceRequest->id,
            'assigned_to' => $activeCoordinator->id,
            'status' => 'assigned',
        ]);
    }

    /** @test */
    public function admin_assistant_can_request_cc_review_for_deliverable()
    {
        $serviceRequest = ServiceRequest::create([
            'title' => 'Admin Assistant CC SR',
            'description' => 'Admin Assistant CC Description',
            'client_id' => $this->client->id,
            'service_category' => 'translation',
            'status' => 'admin_submission',
            'submitted_by' => $this->coordinator->id,
        ]);

        $response = $this->actingAs($this->adminAssistant)->post(route('service-requests.cc-review', $serviceRequest->id), [
            'reviewer_ids' => [$this->coordinator->id],
            'notes' => 'Please give pre-submission review.',
        ]);
        $response->assertRedirect();

        $this->assertDatabaseHas('cc_reviews', [
            'service_request_id' => $serviceRequest->id,
            'sender_id' => $this->adminAssistant->id,
            'reviewer_id' => $this->coordinator->id,
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function directors_are_blocked_from_restricted_modules()
    {
        // 1. Can access Service Requests index & show (restored in latest prompt)
        $serviceRequest = ServiceRequest::create([
            'title' => 'Director Test SR',
            'description' => 'Description',
            'client_id' => $this->client->id,
            'service_category' => 'translation',
            'status' => 'pending',
            'submitted_by' => $this->coordinator->id,
        ]);

        $responseIndex = $this->actingAs($this->director)->get(route('service-requests.index'));
        $responseIndex->assertStatus(200);

        $responseShow = $this->actingAs($this->director)->get(route('service-requests.show', $serviceRequest->id));
        $responseShow->assertStatus(200);

        // 2. Blocked from Courses index
        $responseCourses = $this->actingAs($this->director)->get(route('courses.index'));
        $responseCourses->assertStatus(403);

        // 3. Blocked from Course Applications index
        $responseApplications = $this->actingAs($this->director)->get(route('course-applications.index'));
        $responseApplications->assertStatus(403);
    }

    /** @test */
    public function directors_can_access_deliverable_approvals_view_only_when_pending()
    {
        $serviceRequest = ServiceRequest::create([
            'title' => 'Director Approval SR',
            'description' => 'Description',
            'client_id' => $this->client->id,
            'service_category' => 'translation',
            'status' => 'pending',
            'submitted_by' => $this->coordinator->id,
        ]);

        // Attempting when status is NOT director_approval -> should return 403
        $responseBlocked = $this->actingAs($this->director)->get(route('deliverable-approvals.show', $serviceRequest->id));
        $responseBlocked->assertStatus(403);

        // Transition to director_approval -> should succeed (200)
        $serviceRequest->update(['status' => 'director_approval']);
        $responseAllowed = $this->actingAs($this->director)->get(route('deliverable-approvals.show', $serviceRequest->id));
        $responseAllowed->assertStatus(200);
    }

    /** @test */
    public function coordinator_can_approve_directly_when_direct_routing_is_enabled()
    {
        $serviceRequest = ServiceRequest::create([
            'title' => 'Direct Routing SR',
            'description' => 'Description',
            'client_id' => $this->client->id,
            'service_category' => 'translation',
            'status' => 'review',
            'submitted_by' => $this->coordinator->id,
        ]);

        // 1. By default, direct routing is disabled (Standard Approval Workflow)
        \App\Models\SystemSetting::set('deputy_system_config', [
            'site_name' => 'MSU Language Institute Portal',
            'admin_email' => 'language.institute@msu.ac.zw',
            'support_phone' => '+263 54 2260331',
            'max_upload_size' => 10,
            'maintenance_mode' => false,
            'allow_registrations' => true,
            'deliverable_direct_routing' => false,
        ]);

        $responseFail = $this->actingAs($this->coordinator)->post(route('service-requests.coordinator-approve', $serviceRequest->id), [
            'notes' => 'Direct approve attempt.'
        ]);
        $responseFail->assertStatus(403);

        // 2. Enable direct routing config setting
        \App\Models\SystemSetting::set('deputy_system_config', [
            'site_name' => 'MSU Language Institute Portal',
            'admin_email' => 'language.institute@msu.ac.zw',
            'support_phone' => '+263 54 2260331',
            'max_upload_size' => 10,
            'maintenance_mode' => false,
            'allow_registrations' => true,
            'deliverable_direct_routing' => true,
        ]);

        $responseSuccess = $this->actingAs($this->coordinator)->post(route('service-requests.coordinator-approve', $serviceRequest->id), [
            'notes' => 'Approve directly to Admin Assistant.'
        ]);
        $responseSuccess->assertRedirect();
        
        $serviceRequest->refresh();
        $this->assertEquals('admin_submission', $serviceRequest->status);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'coordinator_approved_deliverable',
            'user_id' => $this->coordinator->id,
        ]);
    }

    /** @test */
    public function directors_cannot_bypass_admin_assistant_for_submission()
    {
        $serviceRequest = ServiceRequest::create([
            'title' => 'Bypass attempt SR',
            'description' => 'Description',
            'client_id' => $this->client->id,
            'service_category' => 'translation',
            'status' => 'admin_submission',
            'submitted_by' => $this->coordinator->id,
        ]);

        // Attempting to deliver as Director -> should fail with 403
        $response = $this->actingAs($this->director)->post(route('service-requests.deliver', $serviceRequest->id), [
            'notes' => 'Attempting to send directly as Director.'
        ]);
        $response->assertStatus(403);
    }
}
