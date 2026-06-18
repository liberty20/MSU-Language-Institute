<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Services\UserBackupService;

class PurgeNonStudentEnrollments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Turn off backup during operation to prevent intermediate backup writes
        $temp = UserBackupService::$shouldBackup;
        UserBackupService::$shouldBackup = false;

        // Delete enrollments of non-student users
        DB::table('course_enrollments')
            ->whereIn('user_id', function ($query) {
                $query->select('id')
                    ->from('users')
                    ->where('primary_category', '!=', 'Student');
            })
            ->delete();

        // Restore backup flag
        UserBackupService::$shouldBackup = $temp;

        // Save database state to backup JSON
        UserBackupService::backup(true);
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
