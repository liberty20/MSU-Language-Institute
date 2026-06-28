<?php
/**
 * Migration Created At: 2026-06-28T13:44:00Z
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCertificateCollectionToCourseEnrollmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('course_enrollments', function (Blueprint $table) {
            $table->timestamp('certificate_collected_at')->nullable();
            $table->unsignedBigInteger('certificate_collected_by')->nullable();

            $table->foreign('certificate_collected_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('course_enrollments', function (Blueprint $table) {
            $table->dropForeign(['certificate_collected_by']);
            $table->dropColumn(['certificate_collected_at', 'certificate_collected_by']);
        });
    }
}
