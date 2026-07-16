<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCourseAttendancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('course_attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_timetable_id');
            $table->unsignedBigInteger('user_id'); // Student
            $table->string('status'); // present, absent, late, excused
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('recorded_by'); // Instructor
            $table->timestamps();

            $table->foreign('course_timetable_id')->references('id')->on('course_timetables')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('recorded_by')->references('id')->on('users')->onDelete('cascade');
            
            // Unique index to prevent duplicate student attendance for same session
            $table->unique(['course_timetable_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('course_attendances');
    }
}
