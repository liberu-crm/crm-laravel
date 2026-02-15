<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_trackings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('contact_id')->nullable()->constrained()->onDelete('cascade');
            $table->uuid('tracking_id')->unique();
            $table->string('subject')->nullable();
            
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('first_opened_at')->nullable();
            $table->timestamp('last_opened_at')->nullable();
            $table->integer('open_count')->default(0);
            
            $table->timestamp('clicked_at')->nullable();
            $table->timestamp('first_clicked_at')->nullable();
            $table->timestamp('last_clicked_at')->nullable();
            $table->integer('click_count')->default(0);
            
            $table->timestamp('bounced_at')->nullable();
            $table->string('bounce_type')->nullable();
            $table->text('bounce_reason')->nullable();
            
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamp('spam_reported_at')->nullable();
            
            $table->string('user_agent')->nullable();
            $table->string('ip_address')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            $table->index(['contact_id', 'sent_at']);
            $table->index('tracking_id');
        });

        Schema::create('email_link_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_tracking_id')->constrained()->onDelete('cascade');
            $table->text('url');
            $table->timestamp('clicked_at');
            $table->string('user_agent')->nullable();
            $table->string('ip_address')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['email_tracking_id', 'clicked_at']);
        });

        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('subject');
            $table->text('body')->nullable();
            $table->longText('html_body')->nullable();
            $table->string('category')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->json('metadata')->nullable();
            $table->json('variables')->nullable();
            $table->timestamps();
            
            $table->index('category');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_link_clicks');
        Schema::dropIfExists('email_trackings');
        Schema::dropIfExists('email_templates');
    }
};
