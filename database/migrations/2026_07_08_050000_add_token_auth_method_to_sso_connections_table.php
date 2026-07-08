<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sso_connections', function (Blueprint $table): void {
            if (! Schema::hasColumn('sso_connections', 'token_auth_method')) {
                $table->string('token_auth_method')->default('client_secret_post')->after('require_sso');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sso_connections', function (Blueprint $table): void {
            $table->dropColumn('token_auth_method');
        });
    }
};
