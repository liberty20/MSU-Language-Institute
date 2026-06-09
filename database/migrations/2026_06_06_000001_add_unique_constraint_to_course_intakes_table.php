<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUniqueConstraintToCourseIntakesTable extends Migration
{
    public function up()
    {
        Schema::table('course_intakes', function (Blueprint $table) {
            $table->unique(['course_id', 'name', 'start_date', 'end_date'], 'course_intake_unique_index');
        });
    }

    public function down()
    {
        Schema::table('course_intakes', function (Blueprint $table) {
            $table->dropUnique('course_intake_unique_index');
        });
    }
}
