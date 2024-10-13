<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('status')->nullable();
            $table->string('source')->nullable();

            $table->string('industry')->nullable();
            $table->integer('company_size')->nullable();
            $table->decimal('annual_revenue', 15, 2)->nullable();
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('status');
            $table->string('source')->nullable();
            $table->decimal('potential_value', 15, 2)->nullable();
            $table->date('expected_close_date')->nullable();
//            $table->foreignId('contact_id')->constrained('contacts');
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
        });

        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->dateTime('date');
            $table->text('description');
            $table->text('outcome')->nullable();
            $table->morphs('activitable');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
        Schema::dropIfExists('deals');
        Schema::dropIfExists('leads');
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn(['status', 'source', 'industry', 'company_size', 'annual_revenue']);
        });
    }
};
