<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Enhance workflows table
        if (Schema::hasTable('workflows')) {
            Schema::table('workflows', function (Blueprint $table) {
                if (!Schema::hasColumn('workflows', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('actions');
                }
                if (!Schema::hasColumn('workflows', 'metadata')) {
                    $table->json('metadata')->nullable()->after('is_active');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('workflows')) {
            Schema::table('workflows', function (Blueprint $table) {
                if (Schema::hasColumn('workflows', 'is_active')) {
                    $table->dropColumn('is_active');
                }
                if (Schema::hasColumn('workflows', 'metadata')) {
                    $table->dropColumn('metadata');
                }
            });
        }
    }
};
