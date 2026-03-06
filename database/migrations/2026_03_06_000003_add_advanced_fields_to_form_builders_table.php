<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_builders', function (Blueprint $table) {
            $table->json('validation_rules')->nullable()->after('fields');
            $table->json('conditional_logic')->nullable()->after('validation_rules');
            $table->json('steps')->nullable()->after('conditional_logic');
            $table->boolean('is_multi_step')->default(false)->after('steps');
        });
    }

    public function down(): void
    {
        Schema::table('form_builders', function (Blueprint $table) {
            $table->dropColumn(['validation_rules', 'conditional_logic', 'steps', 'is_multi_step']);
        });
    }
};
