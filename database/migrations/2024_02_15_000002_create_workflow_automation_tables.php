<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_triggers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained()->onDelete('cascade');
            $table->string('type');
            $table->json('config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['workflow_id', 'type']);
            $table->index('is_active');
        });

        Schema::create('workflow_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained()->onDelete('cascade');
            $table->string('type');
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('config')->nullable();
            $table->integer('order')->default(0);
            $table->integer('delay_amount')->nullable();
            $table->string('delay_unit')->nullable(); // minutes, hours, days
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['workflow_id', 'order']);
        });

        Schema::create('workflow_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_action_id')->constrained()->onDelete('cascade');
            $table->string('field');
            $table->string('operator');
            $table->json('value')->nullable();
            $table->string('logical_operator')->default('and'); // and, or
            $table->timestamps();
        });

        Schema::create('workflow_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained()->onDelete('cascade');
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->string('status')->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['entity_type', 'entity_id']);
            $table->index(['workflow_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_executions');
        Schema::dropIfExists('workflow_conditions');
        Schema::dropIfExists('workflow_actions');
        Schema::dropIfExists('workflow_triggers');
    }
};
