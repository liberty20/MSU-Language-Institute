<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Client;
use App\Models\ServiceRequest;
use App\Models\Assignment;
use App\Services\ReminderService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutstandingTaskReminderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_reminder_service_detects_outstanding_staff_tasks()
    {
        $admin = User::factory()->create();
        $admin->assignRole('ict_administrator');

        $specialist = User::factory()->create(['primary_category' => 'Staff']);
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
            'deadline' => now()->addDays(5)->format('Y-m-d'),
        ]);

        $assignment = Assignment::create([
            'service_request_id' => $serviceRequest->id,
            'assigned_to' => $specialist->id,
            'assigned_by' => $admin->id,
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
        $admin = User::factory()->create();
        $admin->assignRole('ict_administrator');

        $specialist = User::factory()->create(['primary_category' => 'Staff']);
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
            'deadline' => now()->addDays(5)->format('Y-m-d'),
        ]);

        $assignment = Assignment::create([
            'service_request_id' => $serviceRequest->id,
            'assigned_to' => $specialist->id,
            'assigned_by' => $admin->id,
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
}
