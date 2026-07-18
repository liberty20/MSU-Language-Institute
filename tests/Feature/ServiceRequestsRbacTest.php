<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Client;
use App\Models\ServiceRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceRequestsRbacTest extends TestCase
{
    use RefreshDatabase;

    protected $adminAssistant;
    protected $secretary;
    protected $coordinator;
    protected $client;
    protected $clientUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        // 1. Create Roles & Users
        $this->adminAssistant = User::factory()->create(['name' => 'Admin Assistant', 'primary_category' => 'Staff']);
        $this->adminAssistant->assignRole('admin_assistant');

        $this->secretary = User::factory()->create(['name' => 'Secretary', 'primary_category' => 'Staff']);
        $this->secretary->assignRole('secretary');

        $this->coordinator = User::factory()->create(['name' => 'Coordinator', 'primary_category' => 'Staff']);
        $this->coordinator->assignRole('coordinator');

        $this->client = Client::create([
            'client_type'    => 'individual',
            'contact_person' => 'John Doe',
            'email'          => 'johndoe@example.com',
            'phone'          => '123456789',
            'address'        => '123 Client St',
            'status'         => 'active',
        ]);
        $this->clientUser = User::where('email', 'johndoe@example.com')->first();
    }

    public function test_admin_assistant_can_access_service_requests_index_and_create()
    {
        $response = $this->actingAs($this->adminAssistant)->get(route('service-requests.index'));
        $response->assertStatus(200);

        $responseCreate = $this->actingAs($this->adminAssistant)->get(route('service-requests.create'));
        $responseCreate->assertStatus(200);

        $responseStore = $this->actingAs($this->adminAssistant)->post(route('service-requests.store'), [
            'client_id' => $this->client->id,
            'service_category' => 'translation',
            'title' => 'Admin Asst Test Request',
            'description' => 'Test description',
            'priority' => 'medium',
        ]);
        $responseStore->assertRedirect(route('service-requests.index'));
        $this->assertDatabaseHas('service_requests', ['title' => 'Admin Asst Test Request']);
    }

    public function test_secretary_can_access_service_requests_index_and_create()
    {
        $response = $this->actingAs($this->secretary)->get(route('service-requests.index'));
        $response->assertStatus(200);

        $responseCreate = $this->actingAs($this->secretary)->get(route('service-requests.create'));
        $responseCreate->assertStatus(200);

        $responseStore = $this->actingAs($this->secretary)->post(route('service-requests.store'), [
            'client_id' => $this->client->id,
            'service_category' => 'translation',
            'title' => 'Secretary Test Request',
            'description' => 'Test description',
            'priority' => 'medium',
        ]);
        $responseStore->assertRedirect(route('service-requests.index'));
        $this->assertDatabaseHas('service_requests', ['title' => 'Secretary Test Request']);
    }

    public function test_coordinator_can_access_index_but_cannot_create_or_store()
    {
        // 1. Can view index
        $responseIndex = $this->actingAs($this->coordinator)->get(route('service-requests.index'));
        $responseIndex->assertStatus(200);

        // 2. Cannot view create form
        $responseCreate = $this->actingAs($this->coordinator)->get(route('service-requests.create'));
        $responseCreate->assertStatus(403);

        // 3. Cannot submit store post request
        $responseStore = $this->actingAs($this->coordinator)->post(route('service-requests.store'), [
            'client_id' => $this->client->id,
            'service_category' => 'translation',
            'title' => 'Coordinator Test Request',
            'description' => 'Test description',
            'priority' => 'medium',
        ]);
        $responseStore->assertStatus(403);
        $this->assertDatabaseMissing('service_requests', ['title' => 'Coordinator Test Request']);
    }

    public function test_service_request_edit_permissions_restricted_to_owner_client_and_pending_only()
    {
        $serviceRequest = ServiceRequest::create([
            'submitted_by' => $this->clientUser->id,
            'client_id' => $this->client->id,
            'service_category' => 'translation',
            'title' => 'Pending Service Request',
            'description' => 'Test description',
            'priority' => 'medium',
            'source_language' => 'English',
            'target_language' => ['Shona'],
            'status' => 'pending',
        ]);

        // 1. Client owner can view edit form and submit update when status is pending
        $response = $this->actingAs($this->clientUser)->get(route('service-requests.edit', $serviceRequest->id));
        $response->assertStatus(200);

        $response = $this->actingAs($this->clientUser)->put(route('service-requests.update', $serviceRequest->id), [
            'title' => 'Updated Pending Request',
            'service_category' => 'translation',
            'priority' => 'medium',
            'source_language' => 'English',
            'target_language' => ['Shona'],
            'description' => 'Updated description',
        ]);
        $response->assertRedirect(route('service-requests.show', $serviceRequest->id));
        $this->assertDatabaseHas('service_requests', ['title' => 'Updated Pending Request']);

        // 2. Staff/Admins cannot access edit or submit update (should get 403)
        $response = $this->actingAs($this->secretary)->get(route('service-requests.edit', $serviceRequest->id));
        $response->assertStatus(403);

        $response = $this->actingAs($this->secretary)->put(route('service-requests.update', $serviceRequest->id), [
            'title' => 'Staff Hack',
            'service_category' => 'translation',
            'priority' => 'medium',
            'source_language' => 'English',
            'target_language' => ['Shona'],
            'description' => 'Updated description',
        ]);
        $response->assertStatus(403);

        // 3. Client owner cannot edit or update if request is not pending (e.g. status is in_progress)
        $serviceRequest->update(['status' => 'in_progress']);

        $response = $this->actingAs($this->clientUser)->get(route('service-requests.edit', $serviceRequest->id));
        $response->assertStatus(403);

        $response = $this->actingAs($this->clientUser)->put(route('service-requests.update', $serviceRequest->id), [
            'title' => 'Client Update In-Progress',
            'service_category' => 'translation',
            'priority' => 'medium',
            'source_language' => 'English',
            'target_language' => ['Shona'],
            'description' => 'Updated description',
        ]);
        $response->assertStatus(403);
    }

    public function test_coordinator_task_delegation_workflow()
    {
        // 1. Setup director & regular staff
        $director = User::factory()->create(['name' => 'Director', 'primary_category' => 'Staff']);
        $director->assignRole('executive_director');

        $expert = User::factory()->create(['name' => 'Expert Staff', 'primary_category' => 'Staff']);
        $expert->assignRole('language_expert');

        // Create a Service Request
        $serviceRequest = ServiceRequest::create([
            'client_id' => $this->client->id,
            'submitted_by' => $this->clientUser->id,
            'title' => 'Delegation Test Request',
            'description' => 'Test',
            'service_category' => 'translation',
            'status' => 'approved',
        ]);

        // 2. Director assigns Coordinator
        $response = $this->actingAs($director)->post(route('assignments.store'), [
            'service_request_id' => $serviceRequest->id,
            'assign_to_type' => 'coordinator',
            'assigned_to' => $this->coordinator->id,
            'role_in_task' => 'reviewer',
            'notes' => 'Please coordinate this task.',
        ]);
        $response->assertRedirect(route('assignments.index'));

        // 3. Assert status is now pending_coordinator_action and assigned_to is coordinator
        $serviceRequest->refresh();
        $this->assertEquals('pending_coordinator_action', $serviceRequest->status);
        $this->assertEquals($this->coordinator->id, $serviceRequest->assigned_to);

        // 4. Assert coordinator has reminder to decide
        $service = resolve(\App\Services\ReminderService::class);
        $tasks = $service->getOutstandingTasks($this->coordinator);
        $this->assertNotEmpty(array_filter($tasks, fn($t) => $t['id'] === "coordinator-decision-{$serviceRequest->id}"));

        // 5. Coordinator accesses the Show view and sees eligibleStaff
        $responseShow = $this->actingAs($this->coordinator)->get(route('service-requests.show', $serviceRequest->id));
        $responseShow->assertStatus(200);

        // Test option A: Perform Task
        // Let's create another request to test Perform option
        $sr2 = ServiceRequest::create([
            'client_id' => $this->client->id,
            'submitted_by' => $this->clientUser->id,
            'title' => 'Perform Personally Test Request',
            'description' => 'Test',
            'service_category' => 'translation',
            'status' => 'pending_coordinator_action',
            'assigned_to' => $this->coordinator->id,
        ]);

        $responsePerform = $this->actingAs($this->coordinator)->post(route('service-requests.perform', $sr2->id));
        $responsePerform->assertRedirect();
        $sr2->refresh();
        $this->assertEquals('in_progress', $sr2->status);
        $this->assertEquals($this->coordinator->id, $sr2->assigned_to);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'service_request_perform',
            'user_id' => $this->coordinator->id,
        ]);

        // Test option B: Delegate Task
        $responseDelegate = $this->actingAs($this->coordinator)->post(route('service-requests.delegate', $serviceRequest->id), [
            'assigned_to' => $expert->id,
            'instructions' => 'Translate Chapter 1.',
        ]);
        $responseDelegate->assertRedirect();

        // 6. Assert Service Request status is 'assigned' after delegation (not in_progress until staff accepts)
        $serviceRequest->refresh();
        $this->assertEquals('assigned', $serviceRequest->status);
        $this->assertEquals($expert->id, $serviceRequest->assigned_to);

        // 7. Assert Assignment is updated
        $assignment = \App\Models\Assignment::where('service_request_id', $serviceRequest->id)->first();
        $this->assertNotNull($assignment);
        $this->assertEquals($expert->id, $assignment->assigned_to);
        $this->assertEquals($this->coordinator->id, $assignment->assigned_by);
        $this->assertEquals('Translate Chapter 1.', $assignment->notes);

        // 8. Assert Activity Log records delegation
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'service_request_delegate',
            'user_id' => $this->coordinator->id,
        ]);

        // 9. Assert delegated staff member is notified
        $this->assertTrue($expert->unreadNotifications()->where('data->title', 'Task Delegated to You')->exists());

        // 10. Assert coordinator reminder is cleared, and delegated staff has task assignment reminder
        $tasksCoordAfter = $service->getOutstandingTasks($this->coordinator);
        $this->assertEmpty(array_filter($tasksCoordAfter, fn($t) => $t['id'] === "coordinator-decision-{$serviceRequest->id}"));

        $tasksExpert = $service->getOutstandingTasks($expert);
        $this->assertNotEmpty(array_filter($tasksExpert, fn($t) => $t['id'] === "assignment-{$assignment->id}"));

        // 11. Staff member accepts the assignment → SR should move to in_progress
        $responseAccept = $this->actingAs($expert)->put(route('assignments.update', $assignment->id), [
            'status' => 'accepted',
        ]);
        $responseAccept->assertRedirect();

        $serviceRequest->refresh();
        $this->assertEquals('in_progress', $serviceRequest->status);
    }

    public function test_completed_tasks_module_includes_assignments_and_requests()
    {
        // 1. Create a completed service request
        $sr1 = ServiceRequest::create([
            'client_id' => $this->client->id,
            'service_category' => 'translation',
            'title' => 'Completed SR Title',
            'description' => 'Test',
            'submitted_by' => $this->clientUser->id,
            'status' => 'completed',
        ]);

        // 2. Create a completed service request containing a completed assignment
        $sr2 = ServiceRequest::create([
            'client_id' => $this->client->id,
            'service_category' => 'editing',
            'title' => 'Assigned SR Title',
            'description' => 'Test',
            'submitted_by' => $this->clientUser->id,
            'status' => 'completed',
        ]);

        $assignment = \App\Models\Assignment::create([
            'service_request_id' => $sr2->id,
            'assigned_to' => $this->coordinator->id,
            'assigned_by' => $this->adminAssistant->id,
            'role_in_task' => 'Interpreter',
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $sr2->refresh();
        $sr2->status = 'completed';
        $sr2->save();

        // 3. Request completed-tasks.index as coordinator
        $response = $this->actingAs($this->coordinator)->get(route('completed-tasks.index'));
        $response->assertStatus(200);

        // Assert that both sr1 and the assignment are in the paginated items
        $inertiaData = $response->original->getData()['page']['props']['serviceRequests']['data'];

        $ids = collect($inertiaData)->pluck('id')->all();
        $this->assertContains('sr_' . $sr1->id, $ids);
        $this->assertContains('asg_' . $assignment->id, $ids);

        // 4. Assert that in-progress, review, or admin_submission requests are excluded
        $sr3 = ServiceRequest::create([
            'client_id' => $this->client->id,
            'service_category' => 'editing',
            'title' => 'In-Progress SR Title',
            'description' => 'Test',
            'submitted_by' => $this->clientUser->id,
            'status' => 'in_progress',
        ]);

        $sr4 = ServiceRequest::create([
            'client_id' => $this->client->id,
            'service_category' => 'editing',
            'title' => 'Review SR Title',
            'description' => 'Test',
            'submitted_by' => $this->clientUser->id,
            'status' => 'review',
        ]);

        $response2 = $this->actingAs($this->coordinator)->get(route('completed-tasks.index'));
        $inertiaData2 = $response2->original->getData()['page']['props']['serviceRequests']['data'];
        $ids2 = collect($inertiaData2)->pluck('id')->all();

        $this->assertNotContains('sr_' . $sr3->id, $ids2);
        $this->assertNotContains('sr_' . $sr4->id, $ids2);

        // 5. Assert that approved Quotations and completed sub-tasks are excluded
        $quotation = \App\Models\Quotation::create([
            'service_request_id' => $sr1->id,
            'client_id' => $this->client->id,
            'amount' => 500,
            'status' => 'approved',
            'description' => 'Test quotation',
        ]);
        
        $subtask = \App\Models\Task::create([
            'assignment_id' => $assignment->id,
            'title' => 'Translate Sub-part',
            'status' => 'completed',
        ]);

        $response3 = $this->actingAs($this->coordinator)->get(route('completed-tasks.index'));
        $inertiaData3 = $response3->original->getData()['page']['props']['serviceRequests']['data'];
        $ids3 = collect($inertiaData3)->pluck('id')->all();

        $this->assertNotContains('quotation_' . $quotation->id, $ids3);
        $this->assertNotContains('task_' . $subtask->id, $ids3);
    }
}
