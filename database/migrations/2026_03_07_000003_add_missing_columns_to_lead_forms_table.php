<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lead_forms', function (Blueprint $table) {
            if (!Schema::hasColumn('lead_forms', 'team_id')) {
                $table->foreignId('team_id')->nullable()->constrained()->onDelete('cascade')->after('id');
            }
            if (!Schema::hasColumn('lead_forms', 'description')) {
                $table->text('description')->nullable()->after('name');
            }
            if (!Schema::hasColumn('lead_forms', 'settings')) {
                $table->json('settings')->nullable()->after('fields');
            }
            if (!Schema::hasColumn('lead_forms', 'style')) {
                $table->json('style')->nullable()->after('settings');
            }
            if (!Schema::hasColumn('lead_forms', 'status')) {
                $table->string('status')->default('active')->after('style');
            }
            if (!Schema::hasColumn('lead_forms', 'conversion_rate')) {
                $table->decimal('conversion_rate', 5, 2)->default(0)->after('status');
            }
            if (!Schema::hasColumn('lead_forms', 'views')) {
                $table->unsignedInteger('views')->default(0)->after('conversion_rate');
            }
            if (!Schema::hasColumn('lead_forms', 'submissions')) {
                $table->unsignedInteger('submissions')->default(0)->after('views');
            }
            // Make landing_page_id nullable since it may not always be required
            if (Schema::hasColumn('lead_forms', 'landing_page_id')) {
                $table->foreignId('landing_page_id')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('lead_forms', function (Blueprint $table) {
            $columns = ['team_id', 'description', 'settings', 'style', 'status', 'conversion_rate', 'views', 'submissions'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('lead_forms', $column)) {
                    if ($column === 'team_id') {
                        $table->dropForeign(['team_id']);
                    }
                    $table->dropColumn($column);
                }
            }
        });
    }
};
