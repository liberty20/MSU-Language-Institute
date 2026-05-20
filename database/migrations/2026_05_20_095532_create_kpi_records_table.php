<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKpiRecordsTable extends Migration
{
    public function up()
    {
        Schema::create('kpi_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('service_request_id')->nullable()->constrained('service_requests')->cascadeOnDelete();
            $table->string('metric_type', 100);
            $table->decimal('metric_value', 12, 4);
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->text('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('kpi_records');
    }
}
