<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOAuthConfigurationsTable extends Migration
{
    public function up(): void
    {
        Schema::create('oauth_configurations', function (Blueprint $table): void {
            $table->id();
            $table->string('service_name');
            $table->string('client_id');
            $table->string('client_secret');
            $table->json('additional_settings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oauth_configurations');
    }
}
