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
        Schema::table('social_media_posts', function (Blueprint $table) {
            if (!Schema::hasColumn('social_media_posts', 'link')) {
                $table->string('link')->nullable()->after('content');
            }
            if (!Schema::hasColumn('social_media_posts', 'image_path')) {
                $table->string('image_path')->nullable()->after('link');
            }
            if (!Schema::hasColumn('social_media_posts', 'video_url')) {
                $table->string('video_url')->nullable()->after('image_path');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('social_media_posts', function (Blueprint $table) {
            $cols = ['link', 'image_path', 'video_url'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('social_media_posts', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
