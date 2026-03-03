<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_media_posts', function (Blueprint $table) {
            $table->string('link')->nullable()->after('status');
            $table->string('image')->nullable()->after('link');
            $table->json('platform_post_ids')->nullable()->after('image');
        });
    }

    public function down(): void
    {
        Schema::table('social_media_posts', function (Blueprint $table) {
            $table->dropColumn(['link', 'image', 'platform_post_ids']);
        });
    }
};
