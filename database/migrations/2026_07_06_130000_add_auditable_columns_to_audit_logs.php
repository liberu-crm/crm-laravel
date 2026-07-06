<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Make audit rows structured: a polymorphic target (auditable_type/_id), the
 * owning team, and a JSON diff — instead of cramming everything into the
 * free-text `description`. All nullable so pre-existing AuditLogService writes
 * (which only set action/description/user_id/ip_address) keep working.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('changes')->nullable();
            $table->index(['auditable_type', 'auditable_id']);

            // team_id may already exist (add_team_id_to_tenant_scoped_tables).
            if (! Schema::hasColumn('audit_logs', 'team_id')) {
                $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->dropIndex(['auditable_type', 'auditable_id']);
            $table->dropColumn(['auditable_type', 'auditable_id', 'changes']);
        });
    }
};
