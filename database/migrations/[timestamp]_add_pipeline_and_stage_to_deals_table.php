<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPipelineAndStageToDealsTable extends Migration
{
    public function up()
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->foreignId('pipeline_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('stage_id')->nullable()->constrained()->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->dropForeign(['pipeline_id']);
            $table->dropForeign(['stage_id']);
            $table->dropColumn(['pipeline_id', 'stage_id']);
        });
    }
}