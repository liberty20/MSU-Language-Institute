<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Course;
use App\Models\CourseIntake;
use App\Models\CourseEnrollment;
use App\Models\CourseTimetable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class StudentTimetableTest extends TestCase
{
    use RefreshDatabase;

    protected $student;
    protected $instructor;
    protected $course;
    protected $intake;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\UnitsSeeder::class);
        $this->seed(\Database\Seeders\MsunliHierarchySeeder::class);

        // Create student
        $this->student = User::create([
            'name' => 'John Student',
            'email' => 'student.john@msunli.edu',
            'password' => bcrypt('password'),
            'primary_category' => 'Student',
            'is_active' => true,
        ]);
        $this->student->assignRole('student');

        // Create instructor
        $this->instructor = User::create([
            'name' => 'Instructor Jane',
            'email' => 'instructor.jane@msunli.edu',
            'password' => bcrypt('password'),
            'primary_category' => 'Staff',
            'is_active' => true,
        ]);
        $this->instructor->assignRole('language_expert');

        // Create course and intake
        $this->course = Course::create([
            'title' => 'Advanced French Language',
            'code' => 'FRE-ADV',
            'category' => 'French',
            'price' => 250.00,
            'currency' => 'USD',
            'duration_weeks' => 8,
            'is_published' => true,
        ]);

        $this->intake = CourseIntake::create([
            'course_id' => $this->course->id,
            'name' => 'French Intake Q3',
            'start_date' => '2026-06-01',
            'end_date' => '2026-08-31',
            'capacity' => 25,
            'status' => 'open',
            'instructor_id' => $this->instructor->id,
        ]);

        // Enroll student
        CourseEnrollment::create([
            'user_id' => $this->student->id,
            'course_intake_id' => $this->intake->id,
            'enrollment_status' => 'active',
            'payment_status' => 'verified',
            'amount_paid' => 250.00,
        ]);
    }

    /** @test */
    public function student_timetable_only_displays_current_and_upcoming_sessions()
    {
        $today = Carbon::now(config('app.timezone'))->toDateString();
        $yesterday = Carbon::now(config('app.timezone'))->subDay()->toDateString();
        $tomorrow = Carbon::now(config('app.timezone'))->addDay()->toDateString();

        // Create 3 sessions: yesterday, today, and tomorrow
        $pastSession = CourseTimetable::create([
            'course_intake_id' => $this->intake->id,
            'date' => $yesterday,
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
            'venue' => 'Room A',
            'session_type' => 'Lecture',
            'created_by' => $this->instructor->id,
        ]);

        $currentSession = CourseTimetable::create([
            'course_intake_id' => $this->intake->id,
            'date' => $today,
            'start_time' => '11:00:00',
            'end_time' => '13:00:00',
            'venue' => 'Room B',
            'session_type' => 'Tutorial',
            'created_by' => $this->instructor->id,
        ]);

        $upcomingSession = CourseTimetable::create([
            'course_intake_id' => $this->intake->id,
            'date' => $tomorrow,
            'start_time' => '14:00:00',
            'end_time' => '16:00:00',
            'venue' => 'Room C',
            'session_type' => 'Practical',
            'created_by' => $this->instructor->id,
        ]);

        // Act: Fetch student timetable page
        $response = $this->actingAs($this->student)
            ->get(route('student.timetable'));

        $response->assertStatus(200);

        // Assert: Get timetables returned in Inertia
        $response->assertInertia(function ($page) use ($currentSession, $upcomingSession, $pastSession) {
            $timetables = $page->toArray()['props']['timetables'];
            
            // Check count
            $this->assertCount(2, $timetables);

            $ids = collect($timetables)->pluck('id')->toArray();
            
            // Should contain today and tomorrow
            $this->assertContains($currentSession->id, $ids);
            $this->assertContains($upcomingSession->id, $ids);

            // Should NOT contain yesterday
            $this->assertNotContains($pastSession->id, $ids);
        });

        // Assert: Yesterday's record is preserved in database
        $this->assertDatabaseHas('course_timetables', ['id' => $pastSession->id]);

        // Assert: Non-student timetable calls (like model query directly or for instructors) still return all records
        $allTimetables = CourseTimetable::all();
        $this->assertCount(3, $allTimetables);
    }

    /** @test */
    public function student_timetable_is_timezone_aware()
    {
        // Set app timezone to UTC
        config(['app.timezone' => 'UTC']);
        
        // Mock current time using Carbon
        Carbon::setTestNow(Carbon::createFromFormat('Y-m-d H:i:s', '2026-07-02 23:30:00', 'UTC'));

        // In UTC today is 2026-07-02.
        // Let's create two sessions:
        // Session A on 2026-07-02
        $sessionA = CourseTimetable::create([
            'course_intake_id' => $this->intake->id,
            'date' => '2026-07-02',
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
            'venue' => 'Room A',
            'session_type' => 'Lecture',
            'created_by' => $this->instructor->id,
        ]);

        // Session B on 2026-07-03
        $sessionB = CourseTimetable::create([
            'course_intake_id' => $this->intake->id,
            'date' => '2026-07-03',
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
            'venue' => 'Room B',
            'session_type' => 'Lecture',
            'created_by' => $this->instructor->id,
        ]);

        // Request under UTC: both are today or in future.
        $response1 = $this->actingAs($this->student)->get(route('student.timetable'));
        $response1->assertInertia(function ($page) use ($sessionA, $sessionB) {
            $timetables = $page->toArray()['props']['timetables'];
            $this->assertCount(2, $timetables);
        });

        // Now shift timezone to Tokyo (UTC+9)
        // 2026-07-02 23:30:00 UTC = 2026-07-03 08:30:00 in Asia/Tokyo.
        // Under Asia/Tokyo, the current date is 2026-07-03.
        // Therefore, 2026-07-02 should now be considered EXPIRED.
        config(['app.timezone' => 'Asia/Tokyo']);

        $response2 = $this->actingAs($this->student)->get(route('student.timetable'));
        $response2->assertInertia(function ($page) use ($sessionA, $sessionB) {
            $timetables = $page->toArray()['props']['timetables'];
            $this->assertCount(1, $timetables);
            $this->assertEquals($sessionB->id, $timetables[0]['id']);
        });

        // Reset Carbon mock
        Carbon::setTestNow();
    }
}
