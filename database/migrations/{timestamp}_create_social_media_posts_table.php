<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSocialMediaPostsTable extends Migration
{
    public function up()
    {
        Schema::create('social_media_posts', function (Blueprint $table) {
            $table->id();
            $table->text('content');
            $table->json('platforms');
            $table->dateTime('scheduled_at')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('social_media_posts');
    }
}