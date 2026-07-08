<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Widen client_secret string(255) -> text so the encrypted-cast ciphertext
     * fits, and make it nullable: a configuration is created before the OAuth
     * flow supplies a secret, so the column must accept null at that point.
     * Laravel 13 ->change() needs no doctrine/dbal.
     */
    public function up(): void
    {
        Schema::table('oauth_configurations', function (Blueprint $table): void {
            $table->text('client_secret')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('oauth_configurations', function (Blueprint $table): void {
            $table->string('client_secret')->change();
        });
    }
};
