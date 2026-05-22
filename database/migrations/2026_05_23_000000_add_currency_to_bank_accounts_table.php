<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCurrencyToBankAccountsTable extends Migration
{
    public function up()
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->string('currency', 10)->default('USD')->after('account_number');
        });
    }

    public function down()
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropColumn('currency');
        });
    }
}
