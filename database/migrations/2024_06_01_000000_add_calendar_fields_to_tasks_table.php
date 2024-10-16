<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCalendarFieldsToTasksTable extends Migration
{
    public function up()
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('google_event_id')->nullable();
            $table->string('outlook_event_id')->nullable();
            $table->enum('calendar_type', ['none', 'google', 'outlook'])->default('none');
        });
    }

    public function down()
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['google_event_id', 'outlook_event_id', 'calendar_type']);
        });
    }
}