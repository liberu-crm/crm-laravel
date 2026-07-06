<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $column = collect(Schema::getColumns('tickets'))->firstWhere('name', 'email_id');

        // Only alter when the column is still NOT NULL (idempotent / re-runs safe).
        // The unique index is left untouched: both sqlite and MySQL permit
        // multiple NULLs under a unique index, so id-less tickets can coexist.
        if ($column && $column['nullable'] === false) {
            Schema::table('tickets', function (Blueprint $table): void {
                $table->string('email_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        // ponytail: no-op — reverting to NOT NULL would fail on existing NULL rows.
    }
};
