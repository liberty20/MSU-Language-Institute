<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('assignments', function (Blueprint $table) {
            if (!Schema::hasColumn('assignments', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable();
            }
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE assignments MODIFY COLUMN status VARCHAR(50) DEFAULT 'assigned'");
        }
    }

    public function down()
    {
        Schema::table('assignments', function (Blueprint $table) {
            if (Schema::hasColumn('assignments', 'rejection_reason')) {
                $table->dropColumn('rejection_reason');
            }
        });
    }
};
