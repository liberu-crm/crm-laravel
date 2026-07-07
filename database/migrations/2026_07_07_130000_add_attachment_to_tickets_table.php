<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('tickets', 'attachment')) {
            return;
        }

        Schema::table('tickets', function (Blueprint $table): void {
            // Path on the private `local` disk of a single customer-supplied file.
            $table->string('attachment')->nullable()->after('account_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('tickets', 'attachment')) {
            return;
        }

        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropColumn('attachment');
        });
    }
};
