<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CallManager::saveNotes() writes call_logs.notes, but the column never
 * existed — every Save from the call-notes textarea fataled.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('call_logs', 'notes')) {
            return;
        }

        Schema::table('call_logs', function (Blueprint $table) {
            $table->text('notes')->nullable();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('call_logs', 'notes')) {
            return;
        }

        Schema::table('call_logs', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
};
