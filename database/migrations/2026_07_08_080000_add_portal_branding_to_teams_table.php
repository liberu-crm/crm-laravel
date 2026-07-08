<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table): void {
            if (! Schema::hasColumn('teams', 'portal_brand_name')) {
                $table->string('portal_brand_name')->nullable();
            }
            if (! Schema::hasColumn('teams', 'portal_logo_url')) {
                $table->string('portal_logo_url')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table): void {
            $table->dropColumn(['portal_brand_name', 'portal_logo_url']);
        });
    }
};
