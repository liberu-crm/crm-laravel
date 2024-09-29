<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('social_media_posts', function (Blueprint $table) {
            $table->id();
            $table->text('content');
            $table->dateTime('scheduled_at')->nullable();
            $table->json('platforms');
            $table->string('status');
            $table->unsignedInteger('likes')->default(0);
            $table->unsignedInteger('shares')->default(0);
            $table->unsignedInteger('comments')->default(0);
            $table->unsignedInteger('clicks')->default(0);
            $table->unsignedInteger('reach')->default(0);
            $table->unsignedInteger('impressions')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('social_media_posts');
    }
};