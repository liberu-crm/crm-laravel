<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('kb_article_votes')) {
            return;
        }

        Schema::create('kb_article_votes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('knowledge_base_article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('vote');
            $table->timestamps();
            // One vote per customer per article.
            $table->unique(['knowledge_base_article_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kb_article_votes');
    }
};
