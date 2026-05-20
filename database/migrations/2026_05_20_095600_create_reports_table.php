<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReportsTable extends Migration
{
    public function up()
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generated_by')->constrained('users')->cascadeOnDelete();
            $table->string('report_type', 100);
            $table->string('title');
            $table->text('parameters')->nullable();
            $table->string('file_path', 500)->nullable();
            $table->enum('format', ['pdf', 'excel', 'csv'])->default('pdf');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('reports');
    }
}
