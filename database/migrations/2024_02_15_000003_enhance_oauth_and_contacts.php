<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Enhance connected_accounts table if it exists
        if (Schema::hasTable('connected_accounts')) {
            Schema::table('connected_accounts', function (Blueprint $table) {
                if (!Schema::hasColumn('connected_accounts', 'metadata')) {
                    $table->json('metadata')->nullable()->after('expires_at');
                }
                if (!Schema::hasColumn('connected_accounts', 'account_type')) {
                    $table->string('account_type')->nullable()->after('provider');
                }
                if (!Schema::hasColumn('connected_accounts', 'is_primary')) {
                    $table->boolean('is_primary')->default(false)->after('account_type');
                }
            });
        }

        // Add metadata to contacts for engagement tracking
        if (Schema::hasTable('contacts') && !Schema::hasColumn('contacts', 'metadata')) {
            Schema::table('contacts', function (Blueprint $table) {
                $table->json('metadata')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('connected_accounts')) {
            Schema::table('connected_accounts', function (Blueprint $table) {
                if (Schema::hasColumn('connected_accounts', 'metadata')) {
                    $table->dropColumn('metadata');
                }
                if (Schema::hasColumn('connected_accounts', 'account_type')) {
                    $table->dropColumn('account_type');
                }
                if (Schema::hasColumn('connected_accounts', 'is_primary')) {
                    $table->dropColumn('is_primary');
                }
            });
        }

        if (Schema::hasTable('contacts') && Schema::hasColumn('contacts', 'metadata')) {
            Schema::table('contacts', function (Blueprint $table) {
                $table->dropColumn('metadata');
            });
        }
    }
};
