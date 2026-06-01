<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStudentPortalTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('course_timetables', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_intake_id');
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('venue');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('course_intake_id')->references('id')->on('course_intakes')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('course_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_intake_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('attachment_path')->nullable();
            $table->dateTime('due_date');
            $table->integer('max_marks')->default(100);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('course_intake_id')->references('id')->on('course_intakes')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('course_assignment_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_assignment_id');
            $table->unsignedBigInteger('user_id');
            $table->dateTime('submitted_at');
            $table->string('attachment_path')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('submitted'); // submitted, graded, overdue
            $table->decimal('marks_obtained', 5, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->unsignedBigInteger('graded_by')->nullable();
            $table->dateTime('graded_at')->nullable();
            $table->timestamps();

            $table->foreign('course_assignment_id', 'cas_assignment_id_foreign')->references('id')->on('course_assignments')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('graded_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('course_ca_marks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_intake_id');
            $table->unsignedBigInteger('user_id');
            $table->string('assessment_name'); // e.g. "Quiz 1", "Midterm Test"
            $table->decimal('marks_obtained', 5, 2);
            $table->decimal('max_marks', 5, 2)->default(100.00);
            $table->text('feedback')->nullable();
            $table->unsignedBigInteger('entered_by')->nullable();
            $table->timestamps();

            $table->foreign('course_intake_id')->references('id')->on('course_intakes')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('entered_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('course_ca_marks');
        Schema::dropIfExists('course_assignment_submissions');
        Schema::dropIfExists('course_assignments');
        Schema::dropIfExists('course_timetables');
    }
}
