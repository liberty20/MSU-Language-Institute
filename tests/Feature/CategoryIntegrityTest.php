<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CategoryIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles
        Role::firstOrCreate(['name' => 'client', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'language_expert', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'ict_administrator', 'guard_name' => 'web']);
    }

    /** @test */
    public function it_enforces_category_role_consistency()
    {
        // 1. Create a user without roles (defaults to Staff)
        $user = User::factory()->create([
            'primary_category' => 'Staff'
        ]);

        $this->assertEquals('Staff', $user->primary_category);

        // 2. Assigning client role should change primary_category to Client
        $user->assignRole('client');
        $this->assertEquals('Client', $user->primary_category);

        // 3. Trying to mix client and student roles should throw InvalidArgumentException
        $this->expectException(\InvalidArgumentException::class);
        $user->assignRole('student');
    }

    /** @test */
    public function it_blocks_unauthorized_category_changes()
    {
        // Create a user with client role
        $user = User::factory()->create();
        $user->assignRole('client');

        // Normal user trying to change category by changing role
        $normalUser = User::factory()->create();
        $this->actingAs($normalUser);

        $this->expectException(\InvalidArgumentException::class);
        $user->syncRoles(['student']);
    }

    /** @test */
    public function it_allows_authorized_category_changes_for_privileged_admin()
    {
        // Create a user with client role
        $user = User::factory()->create();
        $user->assignRole('client');

        // Privileged Admin
        $admin = User::factory()->create();
        $admin->assignRole('ict_administrator');
        $this->actingAs($admin);

        // Should allow changing category
        $user->syncRoles(['student']);
        $this->assertEquals('Student', $user->primary_category);
    }

    /** @test */
    public function it_blocks_course_enrollment_for_non_students()
    {
        // 1. Create a non-student user (Staff)
        $user = User::factory()->create([
            'primary_category' => 'Staff'
        ]);

        // Create a course and intake
        $department = \App\Models\Department::create(['name' => 'LCSU', 'code' => 'LCSU']);
        $course = \App\Models\Course::create([
            'title' => 'Short Course',
            'code' => 'SC101',
            'category' => 'French',
            'price' => 100.00,
            'duration_weeks' => 8,
            'department_id' => $department->id
        ]);
        $intake = \App\Models\CourseIntake::create([
            'course_id' => $course->id, 
            'name' => 'Intake 1', 
            'start_date' => now(), 
            'end_date' => now()->addMonth(), 
            'capacity' => 20,
            'status' => 'active'
        ]);

        // Expect Exception when attempting to create a course enrollment for Staff
        $this->expectException(\InvalidArgumentException::class);
        \App\Models\CourseEnrollment::create([
            'user_id' => $user->id,
            'course_intake_id' => $intake->id,
            'payment_status' => 'verified',
            'amount_paid' => 100.00,
            'enrollment_status' => 'active'
        ]);
    }

    /** @test */
    public function it_allows_course_enrollment_for_students()
    {
        // 1. Create a student user
        $user = User::factory()->create([
            'primary_category' => 'Student'
        ]);
        $user->assignRole('student');

        // Create a course and intake
        $department = \App\Models\Department::create(['name' => 'LCSU', 'code' => 'LCSU']);
        $course = \App\Models\Course::create([
            'title' => 'Short Course 2',
            'code' => 'SC102',
            'category' => 'French',
            'price' => 100.00,
            'duration_weeks' => 8,
            'department_id' => $department->id
        ]);
        $intake = \App\Models\CourseIntake::create([
            'course_id' => $course->id, 
            'name' => 'Intake 2', 
            'start_date' => now(), 
            'end_date' => now()->addMonth(), 
            'capacity' => 20,
            'status' => 'active'
        ]);

        // This should succeed without exceptions
        $enrollment = \App\Models\CourseEnrollment::create([
            'user_id' => $user->id,
            'course_intake_id' => $intake->id,
            'payment_status' => 'verified',
            'amount_paid' => 100.00,
            'enrollment_status' => 'active'
        ]);

        $this->assertNotNull($enrollment);
        $this->assertEquals($user->id, $enrollment->user_id);
    }
}
