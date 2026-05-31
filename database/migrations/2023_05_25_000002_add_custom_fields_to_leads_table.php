<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCustomFieldsToLeadsTable extends Migration
{
    public function up(): void
    {
        //        Schema::table('leads', function (Blueprint $table) {
        //            $table->json('custom_fields')->nullable();
        //        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropColumn('custom_fields');
        });
    }
}
