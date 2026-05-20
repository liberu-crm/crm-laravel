<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('source')->nullable()->after('email_id');
            $table->string('source_id')->nullable()->after('source');
            $table->unsignedBigInteger('account_id')->nullable()->after('source_id');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['source', 'source_id', 'account_id']);
        });
    }
};
