<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('emails', function (Blueprint $table) {
            if (!Schema::hasColumn('emails', 'team_id')) {
                $table->foreignId('team_id')->nullable()->constrained()->onDelete('cascade')->after('id');
            }
            if (!Schema::hasColumn('emails', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade')->after('team_id');
            }
            if (!Schema::hasColumn('emails', 'body')) {
                $table->text('body')->nullable()->after('subject');
            }
            if (!Schema::hasColumn('emails', 'from')) {
                $table->string('from')->nullable()->after('body');
            }
            if (!Schema::hasColumn('emails', 'to')) {
                $table->json('to')->nullable()->after('from');
            }
            if (!Schema::hasColumn('emails', 'cc')) {
                $table->json('cc')->nullable()->after('to');
            }
            if (!Schema::hasColumn('emails', 'bcc')) {
                $table->json('bcc')->nullable()->after('cc');
            }
            if (!Schema::hasColumn('emails', 'status')) {
                $table->string('status')->default('draft')->after('bcc');
            }
            if (!Schema::hasColumn('emails', 'scheduled_at')) {
                $table->timestamp('scheduled_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('emails', 'sent_at')) {
                $table->timestamp('sent_at')->nullable()->after('scheduled_at');
            }
            if (!Schema::hasColumn('emails', 'opened_at')) {
                $table->timestamp('opened_at')->nullable()->after('sent_at');
            }
            if (!Schema::hasColumn('emails', 'clicked_at')) {
                $table->timestamp('clicked_at')->nullable()->after('opened_at');
            }
            if (!Schema::hasColumn('emails', 'email_template_id')) {
                $table->string('email_template_id')->nullable()->after('clicked_at');
            }
            if (!Schema::hasColumn('emails', 'campaign_id')) {
                $table->string('campaign_id')->nullable()->after('email_template_id');
            }
            if (!Schema::hasColumn('emails', 'metadata')) {
                $table->json('metadata')->nullable()->after('campaign_id');
            }
            // Make old columns nullable for backward compatibility
            if (Schema::hasColumn('emails', 'message_id')) {
                $table->string('message_id')->nullable()->change();
            }
            if (Schema::hasColumn('emails', 'sender')) {
                $table->string('sender')->nullable()->change();
            }
            if (Schema::hasColumn('emails', 'recipient')) {
                $table->string('recipient')->nullable()->change();
            }
            if (Schema::hasColumn('emails', 'content')) {
                $table->text('content')->nullable()->change();
            }
            if (Schema::hasColumn('emails', 'timestamp')) {
                $table->timestamp('timestamp')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('emails', function (Blueprint $table) {
            $columns = [
                'team_id', 'user_id', 'body', 'from', 'to', 'cc', 'bcc',
                'status', 'scheduled_at', 'sent_at', 'opened_at', 'clicked_at',
                'email_template_id', 'campaign_id', 'metadata',
            ];
            foreach ($columns as $column) {
                if (Schema::hasColumn('emails', $column)) {
                    if ($column === 'team_id' || $column === 'user_id') {
                        $table->dropForeign([$column]);
                    }
                    $table->dropColumn($column);
                }
            }
        });
    }
};
