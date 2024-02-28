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
            $table->integer('task_id')->primary();
            $table->string('description')->unique();
            $table->date('due_date');
            $table->string('status');
            $table->integer('priority');
            $table->integer('contact_id')->nullable();
            $table->integer('company_id')->nullable();
            $table->integer('opportunity_id')->nullable();
            $table->timestamps();

            $table->foreign('contact_id')->references('contact_id')->on('contacts');
            $table->foreign('company_id')->references('company_id')->on('companies');
            $table->foreign('opportunity_id')->references('opportunity_id')->on('opportunities');
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
