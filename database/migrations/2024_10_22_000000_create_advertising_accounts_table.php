<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advertising_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('platform', ['Google AdWords', 'LinkedIn Business', 'Facebook Advertising']);
            $table->string('account_id');
            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advertising_accounts');
    }
};
