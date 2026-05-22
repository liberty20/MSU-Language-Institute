<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRatingAndReviewToServiceRequestsTable extends Migration
{
    public function up()
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->unsignedTinyInteger('rating')->nullable()->after('completed_at');
            $table->text('review_comments')->nullable()->after('rating');
        });
    }

    public function down()
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropColumn(['rating', 'review_comments']);
        });
    }
}
