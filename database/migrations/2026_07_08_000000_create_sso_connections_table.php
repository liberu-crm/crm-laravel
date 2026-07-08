<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sso_connections')) {
            return;
        }

        Schema::create('sso_connections', function (Blueprint $table): void {
            $table->id();
            // One connection per team.
            $table->foreignId('team_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('provider')->default('oidc');
            $table->string('client_id');
            // Encrypted at rest -> ciphertext is longer than the plaintext.
            $table->text('client_secret');
            $table->string('issuer_url');
            $table->boolean('enabled')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sso_connections');
    }
};
