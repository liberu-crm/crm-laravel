<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * companies had no annual_revenue column, so DataEnrichmentService's revenue
 * enrichment was silently dropped by mass-assignment. Add it.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('companies', 'annual_revenue')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            $table->decimal('annual_revenue', 15, 2)->nullable();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('companies', 'annual_revenue')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('annual_revenue');
        });
    }
};
