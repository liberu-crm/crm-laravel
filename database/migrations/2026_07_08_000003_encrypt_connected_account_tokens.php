<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Widen the OAuth credential columns so encrypted ciphertext fits.
     * string(1000)/string(255) is too small for an encrypted payload.
     */
    public function up(): void
    {
        Schema::table('connected_accounts', function (Blueprint $table): void {
            $table->text('token')->change();                  // OAuth1/2 access token (NOT NULL)
            $table->text('secret')->nullable()->change();     // OAuth1
            $table->text('refresh_token')->nullable()->change(); // OAuth2
        });
    }

    /**
     * Revert to the original string column sizes.
     */
    public function down(): void
    {
        Schema::table('connected_accounts', function (Blueprint $table): void {
            $table->string('token', 1000)->change();
            $table->string('secret')->nullable()->change();
            $table->string('refresh_token', 1000)->nullable()->change();
        });
    }
};
