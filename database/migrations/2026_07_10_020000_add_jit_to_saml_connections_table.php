<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds JIT provisioning, SSO enforcement, and group→role mapping to SAML
 * connections — mirroring the OIDC sso_connections columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saml_connections', function (Blueprint $table): void {
            $table->boolean('allow_jit')->default(false)->after('enabled');
            $table->string('allowed_domain')->nullable()->after('allow_jit');
            $table->boolean('require_sso')->default(false)->after('allowed_domain');
            $table->json('role_mappings')->nullable()->after('require_sso');
        });
    }

    public function down(): void
    {
        Schema::table('saml_connections', function (Blueprint $table): void {
            $table->dropColumn(['allow_jit', 'allowed_domain', 'require_sso', 'role_mappings']);
        });
    }
};
