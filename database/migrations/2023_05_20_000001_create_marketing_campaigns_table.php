<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('marketing_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['email', 'sms', 'whatsapp']);
            $table->enum('status', ['draft', 'scheduled', 'sent', 'cancelled']);
            $table->string('subject')->nullable();
            $table->text('content');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('marketing_campaigns');
    }
};