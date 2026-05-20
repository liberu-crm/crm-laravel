<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPipelineAndStageToDealsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('deals')) {
            Schema::create('deals', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->decimal('value', 15, 2);
                $table->string('stage')->nullable();
                $table->date('close_date')->nullable();
                $table->integer('probability')->nullable();
//                $table->foreignId('contact_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
                $table->timestamps();
            });
        }

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
