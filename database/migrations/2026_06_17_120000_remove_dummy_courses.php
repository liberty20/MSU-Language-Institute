<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Course;

class RemoveDummyCourses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Delete all courses created by the seeder by their codes.
        // Cascade delete constraints on foreign keys will automatically clean up
        // course_intakes, course_enrollments, course_timetables, course_assignments,
        // course_assignment_submissions, and course_ca_marks.
        Course::whereIn('code', ['ZSL-101', 'BR-101', 'SWA-101', 'TRANS-201'])->delete();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // No rollback needed as we are cleaning up dummy data
    }
}
