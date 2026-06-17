<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CleanNonStudentEnrollments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Delete any course enrollments where the user is not in the 'Student' category
        DB::table('course_enrollments')
            ->whereIn('user_id', function ($query) {
                $query->select('id')
                    ->from('users')
                    ->where('primary_category', '!=', 'Student');
            })
            ->delete();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // No reverse operation possible as deleted data cannot be recovered
    }
}
