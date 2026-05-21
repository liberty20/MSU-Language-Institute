<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProcurementRequestsTable extends Migration
{
    public function up()
    {
        Schema::create('procurement_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number', 50)->unique();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->cascadeOnDelete();
            $table->string('title');
            $table->text('description');
            $table->decimal('estimated_cost', 12, 2)->nullable();
            $table->text('justification')->nullable();
            $table->enum('status', ['draft', 'submitted', 'pending_approval', 'approved', 'rejected', 'fulfilled'])->default('draft');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('procurement_requests');
    }
}
