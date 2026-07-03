<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Client;
use App\Models\ServiceRequest;
use App\Models\Assignment;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignmentReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_specialist_can_accept_and_reject_assignment()
    {
        $admin = User::factory()->create();
        $admin->assignRole('ict_administrator');

        $specialist = User::factory()->create();
        $specialist->assignRole('language_expert');

        $client = Client::create([
            'client_type'    => 'individual',
            'contact_person' => 'John Doe',
            'email'          => 'johndoe@example.com',
            'phone'          => '123456789',
            'address'        => '123 Client St',
            'status'         => 'active',
        ]);
        $clientUser = User::where('email', 'johndoe@example.com')->first();

        $serviceRequest = ServiceRequest::create([
            'client_id' => $client->id,
            'service_category' => 'translation',
            'title' => 'Translation Task',
            'description' => 'Test description',
            'submitted_by' => $clientUser->id,
            'status' => 'approved',
        ]);

        $assignment = Assignment::create([
            'service_request_id' => $serviceRequest->id,
            'assigned_to' => $specialist->id,
            'assigned_by' => $admin->id,
            'status' => 'assigned',
            'role_in_task' => 'Translator',
        ]);

        $this->actingAs($specialist);

        $initialCount = $admin->notifications()->count();

        // 1. Accept assignment
        $response = $this->patch(route('assignments.update', $assignment->id), [
            'status' => 'accepted',
        ]);
        $response->assertRedirect();
        
        $assignment->refresh();
        $this->assertEquals('accepted', $assignment->status);

        // Assert audit log exists
        $this->assertTrue(ActivityLog::where('action', 'assignment_accepted')->exists());

        // Assert notification sent to assigner
        $this->assertEquals($initialCount + 1, $admin->notifications()->count());
        $hasAcceptNotification = $admin->notifications()->get()->contains(function ($n) {
            return $n->data['title'] === 'Assignment Accepted';
        });
        $this->assertTrue($hasAcceptNotification);

        // 2. Reject assignment (reset status first to allow transitions)
        $assignment->status = 'assigned';
        $assignment->save();

        $beforeRejectCount = $admin->notifications()->count();

        // Must fail validation if rejection reason is missing
        $response = $this->patch(route('assignments.update', $assignment->id), [
            'status' => 'rejected',
        ]);
        $response->assertSessionHasErrors(['rejection_reason']);

        // Must succeed if rejection reason is present
        $response = $this->patch(route('assignments.update', $assignment->id), [
            'status' => 'rejected',
            'rejection_reason' => 'Too busy with other translation tasks.',
        ]);
        $response->assertRedirect();

        $assignment->refresh();
        $this->assertEquals('rejected', $assignment->status);
        $this->assertEquals('Too busy with other translation tasks.', $assignment->rejection_reason);

        // Assert audit log exists for rejection
        $this->assertTrue(ActivityLog::where('action', 'assignment_rejected')->exists());

        // Assert notification sent to assigner
        $this->assertEquals($beforeRejectCount + 1, $admin->notifications()->count());
        $hasRejectNotification = $admin->notifications()->get()->contains(function ($n) {
            return $n->data['title'] === 'Assignment Rejected';
        });
        $this->assertTrue($hasRejectNotification);
    }
}
