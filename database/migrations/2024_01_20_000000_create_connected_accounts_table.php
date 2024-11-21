

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('connected_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider');
            $table->string('provider_id');
            $table->string('name')->nullable();
            $table->string('nickname')->nullable();
            $table->string('email')->nullable();
            $table->string('telephone')->nullable();
            $table->string('avatar_path')->nullable();
            $table->string('token');
            $table->string('refresh_token')->nullable();
            $table->string('token_secret')->nullable(); // For OAuth 1.0
            $table->timestamp('expires_at')->nullable();
            $table->json('token_scopes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'provider', 'provider_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('connected_accounts');
    }
};