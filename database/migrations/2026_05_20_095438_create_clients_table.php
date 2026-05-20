<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientsTable extends Migration
{
    public function up()
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('organization')->nullable();
            $table->string('contact_person');
            $table->string('email');
            $table->string('phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->enum('client_type', ['individual', 'organization', 'government'])->default('individual');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('clients');
    }
}
