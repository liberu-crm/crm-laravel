<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('advertising_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('advertising_accounts', 'team_id')) {
                $table->foreignId('team_id')->nullable()->constrained()->cascadeOnDelete()->after('id');
            }
            $table->timestamp('last_sync')->nullable()->after('status');
            $table->json('metadata')->nullable()->after('last_sync');
        });
    }

    public function down(): void
    {
        Schema::table('advertising_accounts', function (Blueprint $table) {
            $table->dropColumn(['last_sync', 'metadata']);
            if (Schema::hasColumn('advertising_accounts', 'team_id')) {
                $table->dropConstrainedForeignId('team_id');
            }
        });
    }
};
