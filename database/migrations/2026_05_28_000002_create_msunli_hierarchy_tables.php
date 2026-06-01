<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMsunliHierarchyTables extends Migration
{
    public function up()
    {
        Schema::create('msunli_sections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('unit_id'); // maps to departments.id (Units)
            $table->string('name');
            $table->string('code')->nullable();
            
            $table->foreign('unit_id')->references('id')->on('departments')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('msunli_roles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('section_id');
            $table->unsignedBigInteger('role_id'); // maps to Spatie roles.id
            $table->string('name');
            
            $table->foreign('section_id')->references('id')->on('msunli_sections')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('section_id')->nullable();
            $table->unsignedBigInteger('msunli_role_id')->nullable();
            
            $table->foreign('section_id')->references('id')->on('msunli_sections')->onDelete('set null');
            $table->foreign('msunli_role_id')->references('id')->on('msunli_roles')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['users_section_id_foreign']);
            $table->dropForeign(['users_msunli_role_id_foreign']);
            $table->dropColumn(['section_id', 'msunli_role_id']);
        });

        Schema::dropIfExists('msunli_roles');
        Schema::dropIfExists('msunli_sections');
    }
}
