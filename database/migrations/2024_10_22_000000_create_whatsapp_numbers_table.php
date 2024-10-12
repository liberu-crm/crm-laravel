<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('whatsapp_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->string('display_name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('whatsapp_numbers');
    }
};
