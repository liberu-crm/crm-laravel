<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('knowledge_base_articles', function (Blueprint $table): void {
            if (! Schema::hasColumn('knowledge_base_articles', 'is_published')) {
                // Default true so existing content stays visible; staff can hide
                // an article from the portal without deleting it.
                $table->boolean('is_published')->default(true)->after('category');
            }
            if (! Schema::hasColumn('knowledge_base_articles', 'helpful_count')) {
                $table->unsignedInteger('helpful_count')->default(0)->after('is_published');
            }
            if (! Schema::hasColumn('knowledge_base_articles', 'not_helpful_count')) {
                $table->unsignedInteger('not_helpful_count')->default(0)->after('helpful_count');
            }
        });
    }

    public function down(): void
    {
        Schema::table('knowledge_base_articles', function (Blueprint $table): void {
            foreach (['is_published', 'helpful_count', 'not_helpful_count'] as $column) {
                if (Schema::hasColumn('knowledge_base_articles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
