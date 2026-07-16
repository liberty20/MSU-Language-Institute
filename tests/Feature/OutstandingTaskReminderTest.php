<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Client;
use App\Models\ServiceRequest;
use App\Models\Assignment;
use App\Services\ReminderService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OutstandingTaskReminderTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $client;
    protected $clientUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->admin = User::factory()->create();
        $this->admin->assignRole('ict_administrator');

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

    public function test_reminder_service_detects_outstanding_staff_tasks()
    {
        $specialist = User::factory()->create(['primary_category' => 'Staff']);
        $specialist->assignRole('language_expert');

        $serviceRequest = ServiceRequest::create([
            'client_id' => $this->client->id,
            'service_category' => 'translation',
            'title' => 'Translation Task',
            'description' => 'Test description',
            'submitted_by' => $this->clientUser->id,
            'status' => 'approved',
            'deadline' => now()->addDays(5)->format('Y-m-d'),
        ]);

        $assignment = Assignment::create([
            'service_request_id' => $serviceRequest->id,
            'assigned_to' => $specialist->id,
            'assigned_by' => $this->admin->id,
            'status' => 'assigned',
            'role_in_task' => 'Translator',
        ]);

        $service = resolve(ReminderService::class);
        $tasks = $service->getOutstandingTasks($specialist);

        // Specialist has 1 outstanding assignment review
        $this->assertCount(1, $tasks);
        $this->assertEquals("assignment-{$assignment->id}", $tasks[0]['id']);
        $this->assertEquals('high', $tasks[0]['priority']);
    }

    public function test_send_reminders_triggers_notifications_and_prevents_duplicates()
    {
        $specialist = User::factory()->create(['primary_category' => 'Staff']);
        $specialist->assignRole('language_expert');

        $serviceRequest = ServiceRequest::create([
            'client_id' => $this->client->id,
            'service_category' => 'translation',
            'title' => 'Translation Task',
            'description' => 'Test description',
            'submitted_by' => $this->clientUser->id,
            'status' => 'approved',
            'deadline' => now()->addDays(5)->format('Y-m-d'),
        ]);

        $assignment = Assignment::create([
            'service_request_id' => $serviceRequest->id,
            'assigned_to' => $specialist->id,
            'assigned_by' => $this->admin->id,
            'status' => 'assigned',
            'role_in_task' => 'Translator',
        ]);

        $initialCount = $specialist->unreadNotifications()->count();

        // Run reminders dispatch command
        $exitCode = Artisan::call('reminders:send');
        $this->assertEquals(0, $exitCode);

        // Specialist must receive 1 reminder notification
        $this->assertEquals($initialCount + 1, $specialist->unreadNotifications()->count());

        // Run reminders command again immediately -> should send 0 because of 24h duplicate check
        $exitCode2 = Artisan::call('reminders:send');
        $this->assertEquals(0, $exitCode2);

        // Unread notification count should still be initialCount + 1
        $this->assertEquals($initialCount + 1, $specialist->unreadNotifications()->count());
    }

    public function test_user_specificity_and_role_awareness()
    {
        $coordinatorA = User::factory()->create(['primary_category' => 'Staff']);
        $coordinatorA->assignRole('coordinator');

        $coordinatorB = User::factory()->create(['primary_category' => 'Staff']);
        $coordinatorB->assignRole('coordinator');

        $serviceRequest = ServiceRequest::create([
            'client_id' => $this->client->id,
            'service_category' => 'translation',
            'title' => 'Deliverable For Review',
            'description' => 'Awaiting Coordinator review',
            'submitted_by' => $this->clientUser->id,
            'status' => 'review',
            'assigned_to' => $coordinatorA->id,
        ]);

        $service = resolve(ReminderService::class);

        // Coordinator A is explicitly assigned and has role coordinator -> should see the reminder
        $tasksA = $service->getOutstandingTasks($coordinatorA);
        $this->assertNotEmpty(array_filter($tasksA, fn($t) => $t['id'] === "deliverable-review-{$serviceRequest->id}"));

        // Coordinator B has coordinator role but is NOT assigned -> should NOT see the reminder
        $tasksB = $service->getOutstandingTasks($coordinatorB);
        $this->assertEmpty(array_filter($tasksB, fn($t) => $t['id'] === "deliverable-review-{$serviceRequest->id}"));
    }

    public function test_workflow_aware_stages_for_deliverables()
    {
        $coordinator = User::factory()->create(['primary_category' => 'Staff']);
        $coordinator->assignRole('coordinator');

        $director = User::factory()->create(['primary_category' => 'Staff']);
        $director->assignRole('executive_director');

        $assistant = User::factory()->create(['primary_category' => 'Staff']);
        $assistant->assignRole('admin_assistant');

        $serviceRequest = ServiceRequest::create([
            'client_id' => $this->client->id,
            'service_category' => 'translation',
            'title' => 'Deliverable Workflow Stages',
            'description' => 'Testing stages',
            'submitted_by' => $this->clientUser->id,
            'status' => 'review',
            'assigned_to' => $coordinator->id,
        ]);

        $service = resolve(ReminderService::class);

        // Stage 1: review -> Coordinator gets reminder
        $this->assertNotEmpty(array_filter($service->getOutstandingTasks($coordinator), fn($t) => $t['id'] === "deliverable-review-{$serviceRequest->id}"));
        $this->assertEmpty(array_filter($service->getOutstandingTasks($director), fn($t) => $t['id'] === "deliverable-approve-{$serviceRequest->id}"));
        $this->assertEmpty(array_filter($service->getOutstandingTasks($assistant), fn($t) => $t['id'] === "deliverable-submit-{$serviceRequest->id}"));

        // Stage 2: director_approval -> Director gets reminder
        $serviceRequest->status = 'director_approval';
        $serviceRequest->save();

        $this->assertEmpty(array_filter($service->getOutstandingTasks($coordinator), fn($t) => $t['id'] === "deliverable-review-{$serviceRequest->id}"));
        $this->assertNotEmpty(array_filter($service->getOutstandingTasks($director), fn($t) => $t['id'] === "deliverable-approve-{$serviceRequest->id}"));
        $this->assertEmpty(array_filter($service->getOutstandingTasks($assistant), fn($t) => $t['id'] === "deliverable-submit-{$serviceRequest->id}"));

        // Stage 3: admin_submission -> Admin Assistant gets reminder
        $serviceRequest->status = 'admin_submission';
        $serviceRequest->assigned_to = $assistant->id;
        $serviceRequest->save();

        $this->assertEmpty(array_filter($service->getOutstandingTasks($coordinator), fn($t) => $t['id'] === "deliverable-review-{$serviceRequest->id}"));
        $this->assertEmpty(array_filter($service->getOutstandingTasks($director), fn($t) => $t['id'] === "deliverable-approve-{$serviceRequest->id}"));
        $this->assertNotEmpty(array_filter($service->getOutstandingTasks($assistant), fn($t) => $t['id'] === "deliverable-submit-{$serviceRequest->id}"));
    }

    public function test_cc_review_reminders_and_cleanup()
    {
        $coordinatorA = User::factory()->create(['primary_category' => 'Staff']);
        $coordinatorA->assignRole('coordinator');

        $coordinatorB = User::factory()->create(['primary_category' => 'Staff']);
        $coordinatorB->assignRole('coordinator');

        $serviceRequest = ServiceRequest::create([
            'client_id' => $this->client->id,
            'service_category' => 'translation',
            'title' => 'CC Review Deliverable',
            'description' => 'Testing CC Review',
            'submitted_by' => $this->clientUser->id,
            'status' => 'review',
        ]);

        // Insert pending CC review for Coordinator A
        $ccReviewId = DB::table('cc_reviews')->insertGetId([
            'service_request_id' => $serviceRequest->id,
            'sender_id' => $this->admin->id,
            'reviewer_id' => $coordinatorA->id,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = resolve(ReminderService::class);

        // Coordinator A should have the CC review task
        $tasksA = $service->getOutstandingTasks($coordinatorA);
        $ccTasksA = array_filter($tasksA, fn($t) => $t['id'] === "cc-review-{$ccReviewId}");
        $this->assertNotEmpty($ccTasksA);
        $this->assertEquals('CC Reviews', reset($ccTasksA)['module']);
        $this->assertEquals('Respond to CC Review', reset($ccTasksA)['required_action']);

        // Coordinator B should NOT have the CC review task
        $tasksB = $service->getOutstandingTasks($coordinatorB);
        $this->assertEmpty(array_filter($tasksB, fn($t) => $t['id'] === "cc-review-{$ccReviewId}"));

        // Simulate coordinator response to complete CC Review
        $this->actingAs($coordinatorA)->post(route('cc-reviews.respond', $ccReviewId), [
            'comments' => 'Approved deliverable',
            'status' => 'approved',
        ]);

        // Reminder should be automatically cleared from getOutstandingTasks for Coordinator A
        $this->assertEmpty(array_filter($service->getOutstandingTasks($coordinatorA), fn($t) => $t['id'] === "cc-review-{$ccReviewId}"));
    }

    public function test_automatic_reminder_removal_on_stage_transitions()
    {
        $coordinator = User::factory()->create(['primary_category' => 'Staff']);
        $coordinator->assignRole('coordinator');

        $director = User::factory()->create(['primary_category' => 'Staff']);
        $director->assignRole('executive_director');

        $assistant = User::factory()->create(['primary_category' => 'Staff']);
        $assistant->assignRole('admin_assistant');

        $serviceRequest = ServiceRequest::create([
            'client_id' => $this->client->id,
            'service_category' => 'translation',
            'title' => 'Deliverable Cleanup Test',
            'description' => 'Testing transitions',
            'submitted_by' => $this->clientUser->id,
            'status' => 'review',
            'assigned_to' => $coordinator->id,
        ]);

        $service = resolve(ReminderService::class);

        // 1. Initial State: review
        $this->assertNotEmpty(array_filter($service->getOutstandingTasks($coordinator), fn($t) => $t['id'] === "deliverable-review-{$serviceRequest->id}"));

        // Coordinator forwards to Director (review -> director_approval)
        $this->actingAs($coordinator)->post(route('service-requests.forward', $serviceRequest->id), [
            'director_id' => $director->id,
            'notes' => 'Forwarded',
        ]);

        // Review reminder should be cleared, Director approval reminder should appear
        $this->assertEmpty(array_filter($service->getOutstandingTasks($coordinator), fn($t) => $t['id'] === "deliverable-review-{$serviceRequest->id}"));
        $this->assertNotEmpty(array_filter($service->getOutstandingTasks($director), fn($t) => $t['id'] === "deliverable-approve-{$serviceRequest->id}"));

        // Director rejects the deliverable (director_approval -> review)
        $this->actingAs($director)->post(route('service-requests.director-reject', $serviceRequest->id), [
            'reason' => 'Fix formatting',
        ]);

        // Director approval reminder should be cleared, Coordinator review reminder should reappear
        $this->assertEmpty(array_filter($service->getOutstandingTasks($director), fn($t) => $t['id'] === "deliverable-approve-{$serviceRequest->id}"));
        $this->assertNotEmpty(array_filter($service->getOutstandingTasks($coordinator), fn($t) => $t['id'] === "deliverable-review-{$serviceRequest->id}"));

        // Coordinator bypasses/approves to admin_submission (using coordinatorApprove direct routing if allowed)
        // Enable direct routing config in system settings
        DB::table('system_settings')->updateOrInsert(
            ['key' => 'deputy_system_config'],
            ['value' => json_encode(['deliverable_direct_routing' => true])]
        );

        $this->actingAs($coordinator)->post(route('service-requests.coordinator-approve', $serviceRequest->id), [
            'notes' => 'Approved directly',
        ]);

        // Coordinator review reminder should be cleared, and Admin Assistant should have admin_submission reminder
        $this->assertEmpty(array_filter($service->getOutstandingTasks($coordinator), fn($t) => $t['id'] === "deliverable-review-{$serviceRequest->id}"));
        $this->assertNotEmpty(array_filter($service->getOutstandingTasks($assistant), fn($t) => $t['id'] === "deliverable-submit-{$serviceRequest->id}"));
    }
}
