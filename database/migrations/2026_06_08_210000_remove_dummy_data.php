<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Client;
use App\Models\ProcurementRequest;

class RemoveDummyData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $dummyEmails = [
            'moyo@health.gov.zw',
            'jsmith@unicef.org',
            'tinashe@gmail.com',
            'peter@nssa.org.zw',
            'grace@yahoo.com',
            'client@example.com'
        ];

        // Delete clients by email. Cascade constraints will automatically delete
        // their service requests, quotations, assignments, and tasks.
        Client::whereIn('email', $dummyEmails)->delete();

        // Delete dummy procurement requests
        ProcurementRequest::whereIn('reference_number', ['PR-20260520-001', 'PR-20260520-002'])->delete();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // No rollback needed for removing dummy data
    }
}
