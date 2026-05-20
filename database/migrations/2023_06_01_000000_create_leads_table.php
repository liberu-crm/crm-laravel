<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure contact_id column exists (it may have been created without it)
        if (Schema::hasTable('leads') && !Schema::hasColumn('leads', 'contact_id')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('leads') && Schema::hasColumn('leads', 'contact_id')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->dropForeign(['contact_id']);
                $table->dropColumn('contact_id');
            });
        }
    }
};
