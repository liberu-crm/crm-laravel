<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('mailchimp_campaigns')) {
            Schema::create('mailchimp_campaigns', function (Blueprint $table) {
                $table->id();
                $table->string('mailchimp_id')->nullable();
                $table->string('name');
                $table->string('subject_line')->nullable();
                $table->string('subject_line_a')->nullable();
                $table->string('subject_line_b')->nullable();
                $table->string('type')->default('regular');
                $table->string('status')->default('save');
                $table->string('winner_criteria')->nullable();
                $table->integer('test_size')->nullable();
                $table->foreignId('team_id')->nullable()->constrained()->onDelete('cascade');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mailchimp_campaigns');
    }
};
