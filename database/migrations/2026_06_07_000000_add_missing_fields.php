<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMissingFields extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('users', 'employee_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('employee_id')->nullable()->unique()->after('id');
            });
        }

        if (!Schema::hasColumn('course_timetables', 'session_type')) {
            Schema::table('course_timetables', function (Blueprint $table) {
                $table->string('session_type')->nullable()->after('venue');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('users', 'employee_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('employee_id');
            });
        }

        if (Schema::hasColumn('course_timetables', 'session_type')) {
            Schema::table('course_timetables', function (Blueprint $table) {
                $table->dropColumn('session_type');
            });
        }
    }
}
