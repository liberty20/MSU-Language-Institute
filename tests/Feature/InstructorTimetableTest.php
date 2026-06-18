<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Course;
use App\Models\CourseIntake;
use App\Models\CourseTimetable;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InstructorTimetableTest extends TestCase
{
    use RefreshDatabase;

    protected $instructor;
    protected $course;
    protected $intake;
    protected $department;

    protected function setUp(): void
    {
        parent::setUp();
        \Carbon\Carbon::setTestNow('2026-06-01');

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        // Create department
        $this->department = Department::create([
            'name' => 'Languages',
            'code' => 'LANG',
        ]);

        // Create instructor user with role
        $this->instructor = User::create([
            'name' => 'Instructor Joe',
            'email' => 'joe@msunli.edu',
            'password' => bcrypt('password'),
            'is_active' => true,
            'department_id' => $this->department->id,
        ]);
        $this->instructor->assignRole('language_expert');

        // Create course
        $this->course = Course::create([
            'title' => 'Introduction to Shona',
            'code' => 'SHN101',
            'category' => 'Shona',
            'price' => 100.00,
            'currency' => 'USD',
            'duration_weeks' => 12,
            'department_id' => $this->department->id,
            'is_published' => true,
        ]);

        // Create intake batch
        $this->intake = CourseIntake::create([
            'course_id' => $this->course->id,
            'name' => 'Intake 2026 Batch A',
            'instructor_id' => $this->instructor->id,
            'start_date' => '2026-06-01',
            'end_date' => '2026-12-31',
            'status' => 'open',
            'capacity' => 30,
        ]);
    }

    protected function tearDown(): void
    {
        \Carbon\Carbon::setTestNow();
        parent::tearDown();
    }

    /** @test */
    public function instructor_can_schedule_class_session()
    {
        $response = $this->actingAs($this->instructor)
            ->post(route('instructor.timetable.store'), [
                'course_intake_id' => $this->intake->id,
                'date' => '2026-06-08',
                'start_time' => '09:00',
                'end_time' => '11:00',
                'venue' => 'Language Lab A',
                'session_type' => 'Practical',
                'notes' => 'Bring headphones',
            ]);

        $response->assertRedirect();
        
        $timetable = CourseTimetable::first();
        $this->assertNotNull($timetable);
        $this->assertEquals($this->intake->id, $timetable->course_intake_id);
        $this->assertEquals('2026-06-08', $timetable->date->toDateString());
        $this->assertEquals('09:00', $timetable->start_time);
        $this->assertEquals('11:00', $timetable->end_time);
        $this->assertEquals('Language Lab A', $timetable->venue);
        $this->assertEquals('Practical', $timetable->session_type);
        $this->assertEquals('Bring headphones', $timetable->notes);
        $this->assertEquals($this->instructor->id, $timetable->created_by);
    }

    /** @test */
    public function scheduler_detects_intake_batch_conflict()
    {
        // Schedule a class first
        CourseTimetable::create([
            'course_intake_id' => $this->intake->id,
            'date' => '2026-06-08',
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
            'venue' => 'Language Lab A',
            'session_type' => 'Lecture',
            'created_by' => $this->instructor->id,
        ]);

        // Try to schedule overlapping session for the same intake
        $response = $this->actingAs($this->instructor)
            ->post(route('instructor.timetable.store'), [
                'course_intake_id' => $this->intake->id,
                'date' => '2026-06-08',
                'start_time' => '10:00',
                'end_time' => '12:00',
                'venue' => 'Room 15',
                'session_type' => 'Tutorial',
            ]);

        $response->assertSessionHasErrors('conflict');
        $this->assertEquals(1, CourseTimetable::count());
    }

    /** @test */
    public function scheduler_detects_venue_conflict_except_online()
    {
        // Create another instructor and intake
        $instructor2 = User::create([
            'name' => 'Instructor Bob',
            'email' => 'bob@msunli.edu',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $instructor2->assignRole('language_expert');

        $intake2 = CourseIntake::create([
            'course_id' => $this->course->id,
            'name' => 'Intake 2026 Batch B',
            'instructor_id' => $instructor2->id,
            'start_date' => '2026-06-01',
            'end_date' => '2026-12-31',
            'status' => 'open',
            'capacity' => 30,
        ]);

        // Schedule first class in Venue A
        CourseTimetable::create([
            'course_intake_id' => $this->intake->id,
            'date' => '2026-06-08',
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
            'venue' => 'Room 10',
            'session_type' => 'Lecture',
            'created_by' => $this->instructor->id,
        ]);

        // Overlapping time, same venue Room 10
        $response = $this->actingAs($instructor2)
            ->post(route('instructor.timetable.store'), [
                'course_intake_id' => $intake2->id,
                'date' => '2026-06-08',
                'start_time' => '10:00',
                'end_time' => '12:00',
                'venue' => 'Room 10',
                'session_type' => 'Lecture',
            ]);

        $response->assertSessionHasErrors('conflict');

        // Overlapping time, online venue should not conflict
        $responseOnline = $this->actingAs($instructor2)
            ->post(route('instructor.timetable.store'), [
                'course_intake_id' => $intake2->id,
                'date' => '2026-06-08',
                'start_time' => '10:00',
                'end_time' => '12:00',
                'venue' => 'https://meet.google.com/abc-defg-hij',
                'session_type' => 'Lecture',
            ]);

        $responseOnline->assertRedirect();
        $this->assertEquals(2, CourseTimetable::count());
    }

    /** @test */
    public function scheduler_detects_instructor_conflict()
    {
        // Create another intake assigned to the same instructor
        $intake2 = CourseIntake::create([
            'course_id' => $this->course->id,
            'name' => 'Intake 2026 Batch B',
            'instructor_id' => $this->instructor->id,
            'start_date' => '2026-06-01',
            'end_date' => '2026-12-31',
            'status' => 'open',
            'capacity' => 30,
        ]);

        // Schedule first class for instructor
        CourseTimetable::create([
            'course_intake_id' => $this->intake->id,
            'date' => '2026-06-08',
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
            'venue' => 'Room 10',
            'session_type' => 'Lecture',
            'created_by' => $this->instructor->id,
        ]);

        // Try to schedule overlapping session for same instructor on Batch B
        $response = $this->actingAs($this->instructor)
            ->post(route('instructor.timetable.store'), [
                'course_intake_id' => $intake2->id,
                'date' => '2026-06-08',
                'start_time' => '10:00',
                'end_time' => '12:00',
                'venue' => 'Room 12',
                'session_type' => 'Tutorial',
            ]);

        $response->assertSessionHasErrors('conflict');
    }

    /** @test */
    public function instructor_can_copy_weekly_timetable()
    {
        // Create 2 sessions in source week (Mon 2026-06-08)
        CourseTimetable::create([
            'course_intake_id' => $this->intake->id,
            'date' => '2026-06-08', // Monday
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
            'venue' => 'Room 10',
            'session_type' => 'Lecture',
            'created_by' => $this->instructor->id,
        ]);

        CourseTimetable::create([
            'course_intake_id' => $this->intake->id,
            'date' => '2026-06-10', // Wednesday
            'start_time' => '14:00:00',
            'end_time' => '16:00:00',
            'venue' => 'Room 10',
            'session_type' => 'Practical',
            'created_by' => $this->instructor->id,
        ]);

        // Copy to next week (Mon 2026-06-15)
        $response = $this->actingAs($this->instructor)
            ->post(route('instructor.timetable.copy'), [
                'source_week' => '2026-06-08',
                'target_week' => '2026-06-15',
            ]);

        $response->assertRedirect();
        
        $timetables = CourseTimetable::orderBy('date')->get();
        $this->assertEquals(4, $timetables->count());

        $copied1 = $timetables->firstWhere('date', '>=', '2026-06-15');
        $this->assertNotNull($copied1);
        $this->assertEquals('2026-06-15', $copied1->date->toDateString());
        $this->assertEquals('09:00:00', $copied1->start_time);
        $this->assertEquals('Lecture', $copied1->session_type);

        $copied2 = $timetables->where('date', '>=', '2026-06-15')->last();
        $this->assertNotNull($copied2);
        $this->assertEquals('2026-06-17', $copied2->date->toDateString());
        $this->assertEquals('14:00:00', $copied2->start_time);
        $this->assertEquals('Practical', $copied2->session_type);
    }

    /** @test */
    public function unauthorized_instructor_cannot_modify_other_timetables()
    {
        $instructor2 = User::create([
            'name' => 'Instructor Bob',
            'email' => 'bob@msunli.edu',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $instructor2->assignRole('language_expert');

        $session = CourseTimetable::create([
            'course_intake_id' => $this->intake->id,
            'date' => '2026-06-08',
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
            'venue' => 'Room 10',
            'created_by' => $this->instructor->id,
        ]);

        // Instructor 2 tries to update it
        $response = $this->actingAs($instructor2)
            ->put(route('instructor.timetable.update', $session->id), [
                'date' => '2026-06-08',
                'start_time' => '10:00',
                'end_time' => '12:00',
                'venue' => 'Room 20',
                'session_type' => 'Lecture',
            ]);

        $response->assertStatus(404); // Scoped resolution fail
    }

    /** @test */
    public function instructor_can_schedule_daily_timetable_with_multiple_dates()
    {
        $response = $this->actingAs($this->instructor)
            ->post(route('instructor.timetable.store'), [
                'course_intake_id' => $this->intake->id,
                'schedule_type' => 'daily',
                'dates' => ['2026-06-15', '2026-06-16'],
                'start_time' => '09:00',
                'end_time' => '11:00',
                'venue' => 'Language Lab A',
                'session_type' => 'Practical',
                'notes' => 'Bring headphones',
            ]);

        $response->assertRedirect();
        
        $this->assertEquals(2, CourseTimetable::count());
        $timetable1 = CourseTimetable::whereDate('date', '2026-06-15')->first();
        $this->assertNotNull($timetable1);
        $this->assertEquals('Language Lab A', $timetable1->venue);

        $timetable2 = CourseTimetable::whereDate('date', '2026-06-16')->first();
        $this->assertNotNull($timetable2);
        $this->assertEquals('Language Lab A', $timetable2->venue);
    }

    /** @test */
    public function instructor_can_schedule_weekly_timetable_with_recurrence()
    {
        $response = $this->actingAs($this->instructor)
            ->post(route('instructor.timetable.store'), [
                'course_intake_id' => $this->intake->id,
                'schedule_type' => 'weekly',
                'start_date' => '2026-06-08', // Monday
                'end_date' => '2026-06-21', // Sunday (2 weeks duration)
                'days_of_week' => ['Monday', 'Wednesday'],
                'day_schedules' => [
                    'Monday' => ['start_time' => '10:00', 'end_time' => '12:00'],
                    'Wednesday' => ['start_time' => '10:00', 'end_time' => '12:00'],
                ],
                'venue' => 'Room 10',
                'session_type' => 'Lecture',
            ]);

        $response->assertRedirect();

        // 2026-06-08 (Mon), 2026-06-10 (Wed), 2026-06-15 (Mon), 2026-06-17 (Wed)
        $this->assertEquals(4, CourseTimetable::count());
        $this->assertTrue(CourseTimetable::whereDate('date', '2026-06-08')->exists());
        $this->assertTrue(CourseTimetable::whereDate('date', '2026-06-10')->exists());
        $this->assertTrue(CourseTimetable::whereDate('date', '2026-06-15')->exists());
        $this->assertTrue(CourseTimetable::whereDate('date', '2026-06-17')->exists());
    }

    /** @test */
    public function scheduler_detects_weekly_timetable_conflict()
    {
        // Schedule a class first on Wednesday 2026-06-10 at 10:00-12:00
        CourseTimetable::create([
            'course_intake_id' => $this->intake->id,
            'date' => '2026-06-10',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'venue' => 'Room 10',
            'session_type' => 'Lecture',
            'created_by' => $this->instructor->id,
        ]);

        // Attempt weekly scheduling that overlaps on Wednesday 2026-06-10
        $response = $this->actingAs($this->instructor)
            ->post(route('instructor.timetable.store'), [
                'course_intake_id' => $this->intake->id,
                'schedule_type' => 'weekly',
                'start_date' => '2026-06-08',
                'end_date' => '2026-06-14',
                'days_of_week' => ['Wednesday'], // Overlaps on Wednesday 2026-06-10
                'day_schedules' => [
                    'Wednesday' => ['start_time' => '11:00', 'end_time' => '13:00'],
                ],
                'venue' => 'Room 10',
                'session_type' => 'Lecture',
            ]);

        $response->assertSessionHasErrors('conflict');
        $this->assertEquals(1, CourseTimetable::count()); // none were persisted due to rollback
    }
}
