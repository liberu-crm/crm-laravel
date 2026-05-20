<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_chats', function (Blueprint $table) {
            $table->id();
            $table->string('visitor_id');
            $table->foreignId('contact_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('status')->default('waiting');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->string('visitor_name')->nullable();
            $table->string('visitor_email')->nullable();
            $table->string('visitor_ip')->nullable();
            $table->text('visitor_user_agent')->nullable();
            $table->string('visitor_location')->nullable();
            $table->text('page_url')->nullable();
            $table->text('referrer')->nullable();
            $table->integer('rating')->nullable();
            $table->text('feedback')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'started_at']);
            $table->index('visitor_id');
            $table->index('contact_id');
        });

        Schema::create('chatbots', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('welcome_message')->nullable();
            $table->text('fallback_message')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('trigger_rules')->nullable();
            $table->json('flow')->nullable();
            $table->json('integrations')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index('is_active');
        });

        Schema::create('chatbot_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chatbot_id')->constrained()->onDelete('cascade');
            $table->string('visitor_id');
            $table->foreignId('contact_id')->nullable()->constrained()->onDelete('set null');
            $table->json('conversation')->nullable();
            $table->string('current_step')->nullable();
            $table->boolean('completed')->default(false);
            $table->boolean('converted_to_lead')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['chatbot_id', 'visitor_id']);
            $table->index('contact_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_interactions');
        Schema::dropIfExists('chatbots');
        Schema::dropIfExists('live_chats');
    }
};
