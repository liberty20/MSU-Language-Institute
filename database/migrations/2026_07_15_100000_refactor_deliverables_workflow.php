<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class RefactorDeliverablesWorkflow extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // SQLite does not support MODIFY COLUMN or varchar change, and does not enforce enum constraints anyway.
        // For MySQL, we run the raw query to avoid doctrine/dbal requirement in Laravel 8.
        if (Schema::connection(null)->getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE service_requests MODIFY COLUMN status VARCHAR(50) DEFAULT 'pending'");
        }

        Schema::create('cc_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_request_id')->constrained('service_requests')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->text('comments')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cc_reviews');
    }
}
