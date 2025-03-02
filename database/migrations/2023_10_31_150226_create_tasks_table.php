<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->dateTime('due_date');
            $table->string('status')->default('pending');
            $table->integer('contact_id')->nullable();
            $table->integer('lead_id')->nullable();
            $table->integer('company_id')->nullable();
            $table->integer('opportunity_id')->nullable();
            $table->dateTime('reminder_date')->nullable();
            $table->boolean('reminder_sent')->default(false);
            $table->string('google_event_id')->nullable();
            $table->string('outlook_event_id')->nullable();
            $table->string('calendar_type')->nullable();
            $table->integer('assigned_to')->nullable();
            $table->integer('team_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
