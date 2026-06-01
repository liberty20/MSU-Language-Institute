<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShortCoursesTables extends Migration
{
    public function up()
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('code')->unique();
            $table->string('category'); // e.g. Braille, Sign Language, Swahili, French, IT
            $table->text('description')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('currency', 10)->default('USD');
            $table->integer('duration_weeks');
            $table->boolean('is_published')->default(false);
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
            $table->timestamps();
        });

        Schema::create('course_intakes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->string('name'); // e.g. "Winter 2026 Intake", "June 2026 Batch"
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('capacity');
            $table->string('status')->default('draft'); // draft, open, closed, completed
            $table->unsignedBigInteger('instructor_id')->nullable();
            $table->foreign('course_id')->references('id')->on('courses')->onDelete('cascade');
            $table->foreign('instructor_id')->references('id')->on('users')->onDelete('set null');
            $table->timestamps();
        });

        Schema::create('course_enrollments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_intake_id');
            $table->unsignedBigInteger('user_id');
            $table->string('payment_status')->default('pending'); // pending, verified, failed
            $table->string('payment_proof_path')->nullable();
            $table->decimal('amount_paid', 10, 2)->default(0.00);
            $table->string('enrollment_status')->default('pending'); // pending, active, completed, dropped
            $table->string('certificate_code')->nullable()->unique();
            $table->timestamp('certificate_issued_at')->nullable();
            $table->foreign('course_intake_id')->references('id')->on('course_intakes')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('course_enrollments');
        Schema::dropIfExists('course_intakes');
        Schema::dropIfExists('courses');
    }
}
