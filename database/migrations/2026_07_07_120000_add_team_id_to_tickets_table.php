<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ticket uses IsTenantModel but the table never had a team_id column (a
     * long-standing model<->schema drift). Add it so a ticket carries a real
     * tenant — the customer portal (G_5) needs it so a customer's reply inherits
     * the ticket's team and stays visible to staff on the team-scoped app panel.
     */
    public function up(): void
    {
        if (Schema::hasColumn('tickets', 'team_id')) {
            return;
        }

        Schema::table('tickets', function (Blueprint $table): void {
            $table->foreignId('team_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
        });

        // Best-effort backfill: a ticket's tenant is its requester's current team.
        DB::table('tickets')->whereNull('team_id')->update([
            'team_id' => DB::raw('(select current_team_id from users where users.id = tickets.user_id)'),
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasColumn('tickets', 'team_id')) {
            return;
        }

        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropForeign(['team_id']);
            $table->dropColumn('team_id');
        });
    }
};
