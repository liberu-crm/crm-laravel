<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sso_connections', function (Blueprint $table): void {
            if (! Schema::hasColumn('sso_connections', 'allow_jit')) {
                $table->boolean('allow_jit')->default(false)->after('enabled');
            }
            if (! Schema::hasColumn('sso_connections', 'allowed_domain')) {
                $table->string('allowed_domain')->nullable()->after('allow_jit');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sso_connections', function (Blueprint $table): void {
            $table->dropColumn(['allow_jit', 'allowed_domain']);
        });
    }
};
