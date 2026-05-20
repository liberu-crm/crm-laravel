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
        Schema::table('oauth_configurations', function (Blueprint $table) {
            if (!Schema::hasColumn('oauth_configurations', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('additional_settings');
            }
            if (!Schema::hasColumn('oauth_configurations', 'account_name')) {
                $table->string('account_name')->nullable()->after('service_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('oauth_configurations', function (Blueprint $table) {
            if (Schema::hasColumn('oauth_configurations', 'is_active')) {
                $table->dropColumn('is_active');
            }
            if (Schema::hasColumn('oauth_configurations', 'account_name')) {
                $table->dropColumn('account_name');
            }
        });
    }
};
