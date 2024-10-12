<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTeamIdToStaffResourceTables extends Migration
{
    public function up()
    {
        $tables = [
	'call_settings', 'contacts', 'dashboard_widgets', 'landing_pages', 'leads', 'marketing_campaigns',
	'social_media_posts', 'tickets'
        ];

        foreach ($tables as $table) {
            if (!Schema::hasColumn($table, 'team_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->foreignId('team_id')->nullable()->constrained()->onDelete('cascade')->default(1);
                });
            }
        }
    }

    public function down()
    {

        $tables = [
	'call_settings', 'contacts', 'dashboard_widgets', 'landing_pages', 'leads', 'marketing_campaigns',
	'social_media_posts', 'tickets'
        ];




        foreach ($tables as $table) {
            if (Schema::hasColumn($table, 'team_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropForeign(['team_id']);
                    $table->dropColumn('team_id');
                });
            }
        }
    }
}
