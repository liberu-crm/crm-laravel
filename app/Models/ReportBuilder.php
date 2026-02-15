<?php

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportBuilder extends Model
{
    use HasFactory, IsTenantModel;

    protected $fillable = [
        'name',
        'description',
        'type',
        'entity_type',
        'filters',
        'columns',
        'aggregations',
        'group_by',
        'sort_by',
        'chart_type',
        'is_public',
        'is_scheduled',
        'schedule_frequency',
        'schedule_recipients',
        'created_by',
        'metadata',
    ];

    protected $casts = [
        'filters' => 'array',
        'columns' => 'array',
        'aggregations' => 'array',
        'group_by' => 'array',
        'sort_by' => 'array',
        'schedule_recipients' => 'array',
        'is_public' => 'boolean',
        'is_scheduled' => 'boolean',
        'metadata' => 'array',
    ];

    const TYPE_TABLE = 'table';
    const TYPE_CHART = 'chart';
    const TYPE_DASHBOARD = 'dashboard';

    const CHART_LINE = 'line';
    const CHART_BAR = 'bar';
    const CHART_PIE = 'pie';
    const CHART_FUNNEL = 'funnel';
    const CHART_AREA = 'area';

    const FREQUENCY_DAILY = 'daily';
    const FREQUENCY_WEEKLY = 'weekly';
    const FREQUENCY_MONTHLY = 'monthly';

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
