<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table): void {
            if (! Schema::hasColumn('teams', 'portal_logo_path')) {
                $table->string('portal_logo_path')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table): void {
            $table->dropColumn('portal_logo_path');
        });
    }
};
