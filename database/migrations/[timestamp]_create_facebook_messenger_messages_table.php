<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFacebookMessengerMessagesTable extends Migration
{
    public function up()
    {
        Schema::create('facebook_messenger_messages', function (Blueprint $table) {
            $table->id();
            $table->string('message_id')->unique();
            $table->string('sender_id');
            $table->string('sender_name');
            $table->text('message');
            $table->timestamp('sent_at');

            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('facebook_messenger_messages');
    }
}