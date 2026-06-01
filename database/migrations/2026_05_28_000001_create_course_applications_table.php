<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCourseApplicationsTable extends Migration
{
    public function up()
    {
        Schema::create('course_applications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_intake_id');
            $table->string('full_name');
            $table->string('national_id_number');
            $table->string('national_id_copy_path');
            $table->string('email');
            $table->string('phone');
            $table->text('physical_address');
            $table->string('payment_proof_path');
            $table->string('temporary_password')->nullable();
            $table->string('status')->default('pending'); // pending, verified, recommended, approved, rejected
            
            $table->string('verification_status')->default('pending'); // pending, verified, rejected
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();

            $table->string('recommendation_status')->default('pending'); // pending, recommended, rejected
            $table->unsignedBigInteger('recommended_by')->nullable();
            $table->timestamp('recommended_at')->nullable();

            $table->string('approval_status')->default('pending'); // pending, approved, rejected
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();

            $table->text('rejection_reason')->nullable();
            
            $table->foreign('course_intake_id')->references('id')->on('course_intakes')->onDelete('cascade');
            $table->foreign('verified_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('recommended_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->timestamps();
        });

        Schema::create('course_application_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_application_id');
            $table->unsignedBigInteger('user_id');
            $table->string('action'); // submitted, verified, recommended, approved, rejected
            $table->text('comment')->nullable();
            
            $table->foreign('course_application_id')->references('id')->on('course_applications')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('course_application_logs');
        Schema::dropIfExists('course_applications');
    }
}
