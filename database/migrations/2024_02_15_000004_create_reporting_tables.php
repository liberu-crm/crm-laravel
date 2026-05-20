<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_builders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type')->default('table');
            $table->string('entity_type');
            $table->json('filters')->nullable();
            $table->json('columns')->nullable();
            $table->json('aggregations')->nullable();
            $table->json('group_by')->nullable();
            $table->json('sort_by')->nullable();
            $table->string('chart_type')->nullable();
            $table->boolean('is_public')->default(false);
            $table->boolean('is_scheduled')->default(false);
            $table->string('schedule_frequency')->nullable();
            $table->json('schedule_recipients')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['entity_type', 'type']);
            $table->index('created_by');
        });

        Schema::create('sales_forecasts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('period_start');
            $table->date('period_end');
            $table->string('forecast_type');
            $table->decimal('predicted_revenue', 15, 2)->default(0);
            $table->decimal('actual_revenue', 15, 2)->nullable();
            $table->decimal('confidence_level', 5, 2)->default(0);
            $table->integer('deal_count')->default(0);
            $table->foreignId('pipeline_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('team_id')->nullable()->constrained()->onDelete('set null');
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['period_start', 'period_end']);
            $table->index('forecast_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_forecasts');
        Schema::dropIfExists('report_builders');
    }
};
