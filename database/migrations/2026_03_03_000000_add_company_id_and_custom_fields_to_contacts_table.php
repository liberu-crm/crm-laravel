<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            if (!Schema::hasColumn('contacts', 'company_id')) {
                $table->foreignId('company_id')->nullable()->constrained('companies', 'company_id')->onDelete('set null');
            }
            if (!Schema::hasColumn('contacts', 'custom_fields')) {
                $table->json('custom_fields')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            if (Schema::hasColumn('contacts', 'company_id')) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            }
            if (Schema::hasColumn('contacts', 'custom_fields')) {
                $table->dropColumn('custom_fields');
            }
        });
    }
};
