<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCustomFieldsToLeadsTable extends Migration
{
    public function up()
    {
//        Schema::table('leads', function (Blueprint $table) {
//            $table->json('custom_fields')->nullable();
//        });
    }

    public function down()
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('custom_fields');
        });
    }
}
