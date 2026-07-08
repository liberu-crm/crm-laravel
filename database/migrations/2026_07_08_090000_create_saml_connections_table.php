<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('saml_connections')) {
            return;
        }

        Schema::create('saml_connections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('idp_entity_id');
            $table->string('idp_sso_url');
            // The IdP's public signing certificate (not a secret).
            $table->text('idp_x509_cert');
            $table->boolean('enabled')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saml_connections');
    }
};
