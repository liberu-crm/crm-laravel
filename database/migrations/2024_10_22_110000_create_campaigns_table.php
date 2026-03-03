<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('advertising_account_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('external_id')->nullable();
            $table->enum('status', ['active', 'paused', 'archived', 'deleted'])->default('active');
            $table->enum('objective', ['awareness', 'consideration', 'conversion'])->nullable();
            $table->decimal('budget', 10, 2)->nullable();
            $table->enum('budget_type', ['daily', 'lifetime'])->default('daily');
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
