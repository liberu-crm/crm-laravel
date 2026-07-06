<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * F1: backfill the team_id column on every table whose model uses
 * IsTenantModel but was missing it, so the tenant global scope can actually
 * filter them (previously these leaked / fataled under an active context).
 *
 * team_id is nullable and auto-stamped on create by IsTenantModel.
 * ponytail: nullable now; tighten to NOT NULL once historical rows are
 * backfilled from their parent records — no prod data exists yet, so there is
 * nothing to backfill today.
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $tables = [
        'activities',
        'audit_logs',
        'campaign_recipients',
        'chatbots',
        'chatbot_interactions',
        'connected_accounts',
        'email_link_clicks',
        'email_templates',
        'email_trackings',
        'knowledge_base_articles',
        'live_chats',
        'pipelines',
        'quote_requests',
        'report_builders',
        'site_settings',
        'stages',
        'workflow_actions',
        'workflow_conditions',
        'workflow_executions',
        'workflow_triggers',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || Schema::hasColumn($table, 'team_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) {
                $t->foreignId('team_id')->nullable()->constrained()->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'team_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) {
                $t->dropForeign(['team_id']);
                $t->dropColumn('team_id');
            });
        }
    }
};
