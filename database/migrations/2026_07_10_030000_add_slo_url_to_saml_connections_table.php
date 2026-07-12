<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the IdP Single Logout Service URL to SAML connections, for SP-initiated
 * single-logout (the LogoutRequest target).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saml_connections', function (Blueprint $table): void {
            $table->string('idp_slo_url')->nullable()->after('idp_sso_url');
        });
    }

    public function down(): void
    {
        Schema::table('saml_connections', function (Blueprint $table): void {
            $table->dropColumn('idp_slo_url');
        });
    }
};
