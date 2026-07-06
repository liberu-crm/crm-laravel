<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tracks each team data-export: its lifecycle (pending -> processing ->
 * completed | failed) and where the resulting zip lives on the storage disk.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('team_backups')) {
            return;
        }

        Schema::create('team_backups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('disk')->default('local');
            $table->string('path')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('status')->default('pending')->index();
            $table->text('error')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_backups');
    }
};
