<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStaffSchedulesTable extends Migration
{
    public function up()
    {
        Schema::create('staff_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('schedule_type', ['regular', 'part_time', 'appointment'])->default('regular');
            $table->enum('status', ['available', 'busy', 'unavailable'])->default('available');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('staff_schedules');
    }
}
