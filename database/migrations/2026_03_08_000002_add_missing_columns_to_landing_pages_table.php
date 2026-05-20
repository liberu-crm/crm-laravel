<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            if (!Schema::hasColumn('landing_pages', 'name')) {
                $table->string('name')->nullable()->after('id');
            }
            if (!Schema::hasColumn('landing_pages', 'slug')) {
                $table->string('slug')->nullable()->after('name');
            }
            if (!Schema::hasColumn('landing_pages', 'template')) {
                $table->string('template')->default('default')->after('slug');
            }
            if (!Schema::hasColumn('landing_pages', 'settings')) {
                $table->json('settings')->nullable()->after('content');
            }
        });
    }

    public function down(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            foreach (['name', 'slug', 'template', 'settings'] as $column) {
                if (Schema::hasColumn('landing_pages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
