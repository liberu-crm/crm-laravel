<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dedicated store for live-chat messages. The general `messages` table is a
 * different domain (channel/thread/ticket/unified-inbox), so live chat gets its
 * own table keyed on chat_id — matching LiveChat::messages().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_id')->constrained('live_chats')->cascadeOnDelete();
            $table->string('sender'); // 'agent' | 'visitor'
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('content');
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
