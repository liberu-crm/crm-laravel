<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sso_connections', function (Blueprint $table): void {
            if (! Schema::hasColumn('sso_connections', 'require_sso')) {
                $table->boolean('require_sso')->default(false)->after('allowed_domain');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sso_connections', function (Blueprint $table): void {
            $table->dropColumn('require_sso');
        });
    }
};
