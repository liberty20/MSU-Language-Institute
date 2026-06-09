<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOriginalFilenamesToCourseApplicationsTable extends Migration
{
    public function up()
    {
        Schema::table('course_applications', function (Blueprint $table) {
            $table->string('national_id_copy_name')->nullable()->after('national_id_copy_path');
            $table->string('payment_proof_name')->nullable()->after('payment_proof_path');
        });
    }

    public function down()
    {
        Schema::table('course_applications', function (Blueprint $table) {
            $table->dropColumn(['national_id_copy_name', 'payment_proof_name']);
        });
    }
}
