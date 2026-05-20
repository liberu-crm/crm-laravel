<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if (!Schema::hasColumn('documents', 'team_id')) {
                $table->foreignId('team_id')->nullable()->constrained()->onDelete('cascade')->after('id');
            }
            if (!Schema::hasColumn('documents', 'name')) {
                $table->string('name')->nullable()->after('team_id');
            }
            if (!Schema::hasColumn('documents', 'file_name')) {
                $table->string('file_name')->nullable()->after('file_path');
            }
            if (!Schema::hasColumn('documents', 'file_size')) {
                $table->unsignedBigInteger('file_size')->nullable()->after('file_name');
            }
            if (!Schema::hasColumn('documents', 'type')) {
                $table->string('type')->nullable()->after('file_size');
            }
            if (!Schema::hasColumn('documents', 'status')) {
                $table->string('status')->nullable()->after('type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $columns = ['team_id', 'name', 'file_name', 'file_size', 'type', 'status'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('documents', $column)) {
                    if ($column === 'team_id') {
                        $table->dropForeign(['team_id']);
                    }
                    $table->dropColumn($column);
                }
            }
        });
    }
};
