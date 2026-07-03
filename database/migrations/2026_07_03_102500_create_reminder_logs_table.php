<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReminderLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reminder_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('remindable_type');
            $table->unsignedBigInteger('remindable_id');
            $table->string('module');
            $table->string('trigger_type');
            $table->string('priority');
            $table->text('channels');
            $table->timestamp('sent_at')->useCurrent();
            $table->uuid('notification_id')->nullable();
            $table->foreign('notification_id')->references('id')->on('notifications')->onDelete('cascade');
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('dismissed_at')->nullable();
            $table->timestamp('task_completed_at')->nullable();
            $table->timestamps();

            $table->index(['remindable_type', 'remindable_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reminder_logs');
    }
}
