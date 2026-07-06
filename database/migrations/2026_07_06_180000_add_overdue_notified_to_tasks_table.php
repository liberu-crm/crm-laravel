<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dedup flag so tasks:notify-overdue fires the TaskOverdue event once per task
 * rather than every scheduled run.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('tasks', 'overdue_notified')) {
            return;
        }

        Schema::table('tasks', function (Blueprint $table) {
            $table->boolean('overdue_notified')->default(false);
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('tasks', 'overdue_notified')) {
            return;
        }

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('overdue_notified');
        });
    }
};
