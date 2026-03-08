<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Make contacts columns nullable that may not always be provided
        Schema::table('contacts', function (Blueprint $table) {
            if (Schema::hasColumn('contacts', 'last_name')) {
                $table->string('last_name')->nullable()->change();
            }
            if (Schema::hasColumn('contacts', 'phone_number')) {
                $table->string('phone_number')->nullable()->change();
            }
        });

        // Add user_id and make client credentials nullable for oauth_configurations
        Schema::table('oauth_configurations', function (Blueprint $table) {
            if (!Schema::hasColumn('oauth_configurations', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade')->after('id');
            }
            if (Schema::hasColumn('oauth_configurations', 'client_id')) {
                $table->string('client_id')->nullable()->change();
            }
            if (Schema::hasColumn('oauth_configurations', 'client_secret')) {
                $table->string('client_secret')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            if (Schema::hasColumn('contacts', 'last_name')) {
                $table->string('last_name')->nullable(false)->change();
            }
            if (Schema::hasColumn('contacts', 'phone_number')) {
                $table->string('phone_number')->nullable(false)->change();
            }
        });

        Schema::table('oauth_configurations', function (Blueprint $table) {
            if (Schema::hasColumn('oauth_configurations', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
            if (Schema::hasColumn('oauth_configurations', 'client_id')) {
                $table->string('client_id')->nullable(false)->change();
            }
            if (Schema::hasColumn('oauth_configurations', 'client_secret')) {
                $table->string('client_secret')->nullable(false)->change();
            }
        });
    }
};
