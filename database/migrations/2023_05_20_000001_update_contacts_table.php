<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateContactsTable extends Migration
{
    public function up()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('lifecycle_stage')->nullable();
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('lifecycle_stage');
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
    }
}