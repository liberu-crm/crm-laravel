<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateContactsTable extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table): void {
            $table->string('lifecycle_stage')->nullable();
            //            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table): void {
            $table->dropColumn('lifecycle_stage');
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
    }
}
