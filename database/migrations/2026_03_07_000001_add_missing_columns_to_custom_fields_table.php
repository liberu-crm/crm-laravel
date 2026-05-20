<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('custom_fields', function (Blueprint $table) {
            if (!Schema::hasColumn('custom_fields', 'label')) {
                $table->string('label')->nullable()->after('name');
            }
            if (!Schema::hasColumn('custom_fields', 'options')) {
                $table->json('options')->nullable()->after('model_type');
            }
            if (!Schema::hasColumn('custom_fields', 'required')) {
                $table->boolean('required')->default(false)->after('options');
            }
            if (!Schema::hasColumn('custom_fields', 'validation_rules')) {
                $table->json('validation_rules')->nullable()->after('required');
            }
            if (!Schema::hasColumn('custom_fields', 'order')) {
                $table->integer('order')->default(0)->after('validation_rules');
            }
        });
    }

    public function down(): void
    {
        Schema::table('custom_fields', function (Blueprint $table) {
            $columns = ['label', 'options', 'required', 'validation_rules', 'order'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('custom_fields', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
