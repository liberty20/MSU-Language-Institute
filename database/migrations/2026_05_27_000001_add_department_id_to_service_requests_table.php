<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDepartmentIdToServiceRequestsTable extends Migration
{
    public function up()
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('department_id')->nullable()->after('client_id');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
        });

        // Map existing service categories to the new departments
        // LTRDU (Language Technology and Resource Development Unit)
        // LCSU (Language Consultancy and Services Unit)
        // SNSU (Special Needs Services Unit)
        // ILASU (International Languages and Area Studies Unit)
        
        $departments = \DB::table('departments')->get()->pluck('id', 'code');
        
        if ($departments->isNotEmpty()) {
            // Map:
            // translation, editing, consultancy -> LCSU
            // brailling, sign_language -> SNSU
            // short_courses -> ILASU or SNSU depending on course type. We'll map short_courses category to ILASU as default.
            // LTRDU handles software/technology categories
            
            if (isset($departments['LCSU'])) {
                \DB::table('service_requests')
                    ->whereIn('service_category', ['translation', 'editing', 'consultancy'])
                    ->update(['department_id' => $departments['LCSU']]);
            }
            if (isset($departments['SNSU'])) {
                \DB::table('service_requests')
                    ->whereIn('service_category', ['brailling', 'sign_language'])
                    ->update(['department_id' => $departments['SNSU']]);
            }
            if (isset($departments['ILASU'])) {
                \DB::table('service_requests')
                    ->whereIn('service_category', ['short_courses'])
                    ->update(['department_id' => $departments['ILASU']]);
            }
        }
    }

    public function down()
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn('department_id');
        });
    }
}
