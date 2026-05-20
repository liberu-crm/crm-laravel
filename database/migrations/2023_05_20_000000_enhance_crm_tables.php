<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Add other table enhancements here

        if (!Schema::hasTable('leads')) {
            Schema::create('leads', function (Blueprint $table) {
                $table->id();
                $table->string('status')->default('new');
                $table->string('source')->nullable();
                $table->decimal('potential_value', 10, 2)->nullable();
                $table->date('expected_close_date')->nullable();
                $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('lifecycle_stage')->nullable();
                $table->json('custom_fields')->nullable();
                $table->integer('score')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        // Add rollback logic for other table enhancements here

        Schema::dropIfExists('leads');
    }
};
