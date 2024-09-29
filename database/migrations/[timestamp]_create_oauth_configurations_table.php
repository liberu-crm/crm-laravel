<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOAuthConfigurationsTable extends Migration
{
    public function up()
    {
        Schema::create('oauth_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('service_name');
            $table->string('client_id');
            $table->string('client_secret');
            $table->text('additional_settings')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('oauth_configurations');
    }
}