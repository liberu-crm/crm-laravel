<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Team lifecycle: a nullable timestamp marks a team as archived (frozen,
 * hidden, data preserved) vs the existing hard-delete. Presence = archived.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('teams', 'archived_at')) {
            return;
        }

        Schema::table('teams', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('teams', 'archived_at')) {
            return;
        }

        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('archived_at');
        });
    }
};
