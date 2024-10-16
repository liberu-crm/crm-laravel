<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
/**
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('status');
            $table->string('source');
            $table->decimal('potential_value', 10, 2);
            $table->date('expected_close_date');
//            $table->foreignId('contact_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->string('lifecycle_stage');
            $table->json('custom_fields')->nullable();
            $table->integer('score')->default(0);
            $table->timestamps();
        });
**/
    }

    public function down()
    {
        Schema::dropIfExists('leads');
    }
};
