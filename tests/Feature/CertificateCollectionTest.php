<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Course;
use App\Models\CourseIntake;
use App\Models\CourseEnrollment;
use App\Models\Department;
use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CertificateCollectionTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $studentUser;
    protected $course;
    protected $intake;
    protected $enrollment;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup Roles
        Role::findOrCreate('ict_administrator');
        Role::findOrCreate('student');

        $this->adminUser = User::factory()->create([
            'name' => 'ICT Admin',
            'email' => 'admin@msunli.edu',
        ]);
        $this->adminUser->assignRole('ict_administrator');

        $this->studentUser = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'student@test.com',
            'primary_category' => 'Student',
        ]);
        $this->studentUser->assignRole('student');

        $dept = Department::create([
            'name' => 'Language Technology Unit',
            'code' => 'LTU',
        ]);

        $this->course = Course::create([
            'title' => 'Unified English Braille',
            'code' => 'UEB-101',
            'category' => 'Braille',
            'department_id' => $dept->id,
            'price' => 150.00,
            'currency' => 'USD',
            'duration_weeks' => 8,
            'is_published' => true,
        ]);

        $this->intake = CourseIntake::create([
            'course_id' => $this->course->id,
            'name' => 'June 2026 Batch',
            'start_date' => now()->subDays(10),
            'end_date' => now()->addDays(20),
            'status' => 'open',
            'capacity' => 20,
        ]);

        $this->enrollment = CourseEnrollment::create([
            'course_intake_id' => $this->intake->id,
            'user_id' => $this->studentUser->id,
            'enrollment_status' => 'active',
            'payment_status' => 'verified',
            'amount_paid' => 150.00,
        ]);
    }

    /**
     * Test admin can mark certificate as physically collected.
     */
    public function test_admin_can_mark_certificate_as_collected()
    {
        $this->actingAs($this->adminUser);

        $response = $this->post(route('course-enrollments.certificate', $this->enrollment->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->enrollment->refresh();

        $this->assertEquals('completed', $this->enrollment->enrollment_status);
        $this->assertNotNull($this->enrollment->certificate_code);
        $this->assertNotNull($this->enrollment->certificate_collected_at);
        $this->assertEquals($this->adminUser->id, $this->enrollment->certificate_collected_by);

        // Assert notification was sent to student
        $this->assertCount(1, $this->studentUser->notifications);
        $notification = $this->studentUser->notifications->first();
        $this->assertEquals('Certificate Collected', $notification->data['title']);
    }

    /**
     * Test bulk certificate collection works atomically.
     */
    public function test_admin_can_bulk_mark_certificates_as_collected()
    {
        $student2 = User::factory()->create([
            'name' => 'Jane Smith',
            'email' => 'jane@test.com',
            'primary_category' => 'Student',
        ]);
        $student2->assignRole('student');

        $enrollment2 = CourseEnrollment::create([
            'course_intake_id' => $this->intake->id,
            'user_id' => $student2->id,
            'enrollment_status' => 'active',
            'payment_status' => 'verified',
            'amount_paid' => 150.00,
        ]);

        $this->actingAs($this->adminUser);

        $response = $this->post(route('course-enrollments.bulk-certificate'), [
            'enrollment_ids' => [$this->enrollment->id, $enrollment2->id]
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->enrollment->refresh();
        $enrollment2->refresh();

        $this->assertEquals('completed', $this->enrollment->enrollment_status);
        $this->assertEquals('completed', $enrollment2->enrollment_status);
    }

    /**
     * Test student testimony flow: submission, moderation, editing and approval.
     */
    public function test_testimony_workflow()
    {
        // 1. Mark enrollment as completed to allow student testimony submission
        $this->enrollment->update([
            'enrollment_status' => 'completed',
        ]);

        // 2. Student submits testimony
        $this->actingAs($this->studentUser);
        $response = $this->post(route('student.testimonials.store'), [
            'enrollment_id' => $this->enrollment->id,
            'text' => 'This course changed my life!',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Your testimony has successfully sent.');

        // Verify it is placed in pending list
        $pending = SystemSetting::get('short_courses_pending_testimonials', []);
        $this->assertCount(1, $pending);
        $testimonyId = $pending[0]['id'];
        $this->assertEquals('This course changed my life!', $pending[0]['text']);

        // Verify admin received notification
        $this->assertCount(1, $this->adminUser->notifications);
        $notification = $this->adminUser->notifications->first();
        $this->assertEquals('New Testimony Pending Review', $notification->data['title']);

        // 3. Admin moderates (edits) and approves the testimony
        $this->actingAs($this->adminUser);
        $response = $this->post(route('admin.testimonials.approve'), [
            'id' => $testimonyId,
            'text' => 'This course is exceptionally life changing!',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Check it was removed from pending and added to active/published testimonials
        $this->assertCount(0, SystemSetting::get('short_courses_pending_testimonials', []));
        
        $active = SystemSetting::get('short_courses_testimonials', []);
        $this->assertCount(1, $active);
        $this->assertEquals('This course is exceptionally life changing!', $active[0]['text']);

        // 4. Admin edits active testimony
        $response = $this->post(route('admin.testimonials.update-active'), [
            'index' => 0,
            'name' => 'John D.',
            'course' => 'Braille UEB',
            'text' => 'Loved it!',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $active = SystemSetting::get('short_courses_testimonials', []);
        $this->assertEquals('John D.', $active[0]['name']);
        $this->assertEquals('Loved it!', $active[0]['text']);

        // 5. Admin deletes active testimony
        $response = $this->delete(route('admin.testimonials.destroy', 0));
        $response->assertRedirect();
        $this->assertCount(0, SystemSetting::get('short_courses_testimonials', []));
    }
}
