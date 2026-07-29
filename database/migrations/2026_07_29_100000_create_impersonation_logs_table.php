<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateImpersonationLogsTable extends Migration
{
    public function up()
    {
        Schema::create('impersonation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('impersonator_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('impersonated_id')->constrained('users')->cascadeOnDelete();
            $table->text('reason');
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('session_id')->nullable();
            $table->timestamps();

            $table->index('impersonator_id');
            $table->index('impersonated_id');
            $table->index('started_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('impersonation_logs');
    }
}
