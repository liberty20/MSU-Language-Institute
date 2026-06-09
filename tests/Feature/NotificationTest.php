<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Assignment;
use App\Models\Task;
use App\Models\CourseEnrollment;
use App\Models\CourseAssignment;
use App\Models\CourseAssignmentSubmission;
use App\Models\CourseIntake;
use App\Models\Course;
use App\Models\Quotation;
use App\Models\ServiceRequest;
use App\Models\Client;
use App\Models\Department;
use App\Notifications\SystemNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $studentUser;
    protected $instructorUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup Spatie Roles & Permissions
        Permission::findOrCreate('view service requests');
        Permission::findOrCreate('view reports');

        Role::findOrCreate('ict_administrator');
        $adminRole = Role::findOrCreate('admin_assistant');
        $adminRole->givePermissionTo('view service requests');
        
        Role::findOrCreate('student');
        Role::findOrCreate('language_expert');

        $this->adminUser = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@msunli.edu',
            'is_active' => true,
        ]);
        $this->adminUser->assignRole('admin_assistant');

        $this->studentUser = User::factory()->create([
            'name' => 'Student User',
            'email' => 'student@msunli.edu',
            'is_active' => true,
        ]);
        $this->studentUser->assignRole('student');

        $this->instructorUser = User::factory()->create([
            'name' => 'Instructor User',
            'email' => 'instructor@msunli.edu',
            'is_active' => true,
        ]);
        $this->instructorUser->assignRole('language_expert');
    }

    /**
     * Test Artisan command generated notifications.
     */
    public function test_deadlines_check_artisan_command()
    {
        // 1. Create a task assigned to student (via assignment)
        $client = Client::create([
            'contact_person' => 'Client',
            'email' => 'client@example.com',
            'organization' => 'Corp',
        ]);
        $req = ServiceRequest::create([
            'client_id' => $client->id,
            'reference_number' => 'SR-TEST-01',
            'service_category' => 'translation',
            'title' => 'Test Service Request',
            'description' => 'Test Description',
            'priority' => 'high',
            'status' => 'pending',
        ]);
        $assignment = Assignment::create([
            'service_request_id' => $req->id,
            'assigned_to' => $this->instructorUser->id,
            'assigned_by' => $this->adminUser->id,
            'role_in_task' => 'Lead Translator',
            'status' => 'in_progress',
        ]);
        $task = Task::create([
            'assignment_id' => $assignment->id,
            'title' => 'Overdue Translation task',
            'description' => 'Translate standard document.',
            'status' => 'in_progress',
            'priority' => 'high',
            'due_date' => now()->subDays(2),
        ]);

        // Run the command
        Artisan::call('deadlines:check');

        // Verify notification was created in database
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->instructorUser->id,
            'type' => 'App\Notifications\SystemNotification',
        ]);

        $notification = $this->instructorUser->notifications()->get()->first(function ($n) {
            return isset($n->data['alert_type']) && $n->data['alert_type'] === 'overdue';
        });
        $this->assertNotNull($notification);
        $this->assertEquals('overdue', $notification->data['alert_type']);
        $this->assertEquals($task->id, $notification->data['task_id']);
        $this->assertEquals('checkDeadlines', $notification->data['trigger_source']);
    }

    /**
     * Test notification validation and cleanup.
     */
    public function test_get_notifications_cleans_orphans_and_validates_permissions()
    {
        $this->actingAs($this->adminUser);

        // 1. Create an orphaned notification (referencing deleted task)
        $this->adminUser->notify(new SystemNotification(
            'Tasks',
            'Task Overdue',
            'Your task is overdue!',
            route('completed-tasks.index'),
            ['task_id' => 9999, 'alert_type' => 'overdue'],
            'checkDeadlines'
        ));

        // 2. Create a notification referencing a deleted quotation action url
        $this->adminUser->notify(new SystemNotification(
            'Quotations',
            'Quotation Approved',
            'Your quotation QT-001 has been approved.',
            'http://127.0.0.1:8000/quotations/9999',
            [],
            'Quotation::updated'
        ));

        // Verify they are initially in DB
        $this->assertEquals(2, $this->adminUser->notifications()->count());

        // Get notifications via API
        $response = $this->getJson(route('notifications.index'));
        $response->assertStatus(200);

        // Verify they were cleaned up (deleted) from database and not returned
        $this->assertEquals(0, $this->adminUser->notifications()->count());
        $response->assertJsonCount(0, 'notifications');
    }

    /**
     * Test notifications displayed only to authorized roles.
     */
    public function test_role_based_notification_isolation()
    {
        // 1. Create a notification referencing an admin page (/admin/settings)
        // Sent to a student user
        $this->studentUser->notify(new SystemNotification(
            'System Alerts',
            'Settings Changed',
            'System settings updated.',
            'http://127.0.0.1:8000/admin/settings',
            [],
            'SettingsController::update'
        ));

        $this->actingAs($this->studentUser);

        // Get notifications
        $response = $this->getJson(route('notifications.index'));
        $response->assertStatus(200);

        // Verify the student cannot see the admin settings notification (it is deleted/filtered out)
        $this->assertEquals(0, $this->studentUser->notifications()->count());
        $response->assertJsonCount(0, 'notifications');
    }
}
