<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Deal;
use App\Models\Activity;
use App\Models\Workflow;
use App\Models\Lead;
use Illuminate\Support\Facades\DB;

class ReportingService
{
    public function getContactInteractionsData(array $filters = [])
    {
        $query = Activity::select(
            'type',
            DB::raw('COUNT(*) as count'),
            DB::raw('DATE(created_at) as activity_date')
        );

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
        }

        if (!empty($filters['contact_id'])) {
            $query->where('activitable_type', Contact::class)
                  ->where('activitable_id', $filters['contact_id']);
        }

        $data = $query->groupBy('type', 'activity_date')
            ->orderBy('activity_date')
            ->get();

        // Aggregate by type across all dates for chart display
        $byType = $data->groupBy('type')->map(fn ($group) => $group->sum('count'));

        $labels  = $byType->keys();
        $values  = $byType->values();

        return [
            'type' => 'bar',
            'data' => [
                'labels'   => $labels,
                'datasets' => [
                    [
                        'label' => 'Contact Interactions',
                        'data'  => $values,
                    ],
                ],
            ],
            'raw' => $data,
        ];
    }

    public function getSalesPipelineData(array $filters = [])
    {
        $query = Deal::select(
            'stage_id',
            DB::raw('COUNT(*) as deal_count'),
            DB::raw('SUM(value) as total_value'),
            DB::raw('AVG(probability) as avg_probability')
        )->with('stage');

        if (!empty($filters['pipeline_id'])) {
            $query->where('pipeline_id', $filters['pipeline_id']);
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
        }

        $data = $query->whereNotIn('stage', ['lost'])
            ->groupBy('stage_id')
            ->get();

        $labels = $data->map(fn ($d) => optional($d->stage)->name ?? 'Unknown');
        $values = $data->pluck('total_value');
        $counts = $data->pluck('deal_count');

        return [
            'type' => 'bar',
            'data' => [
                'labels'   => $labels,
                'datasets' => [
                    [
                        'label' => 'Total Value ($)',
                        'data'  => $values,
                    ],
                    [
                        'label' => 'Deal Count',
                        'data'  => $counts,
                    ],
                ],
            ],
            'raw' => $data,
        ];
    }

    public function getCustomerEngagementData(array $filters = [])
    {
        $query = Activity::select(
            'type',
            DB::raw('COUNT(*) as count')
        );

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
        }

        $data = $query->groupBy('type')
            ->orderByDesc('count')
            ->get();

        return $this->formatDataForChart($data, 'pie', 'type', 'count');
    }

    public function generateLeadQualityReport(array $filters = [])
    {
        $query = Lead::select('lifecycle_stage', DB::raw('AVG(score) as average_score'), DB::raw('COUNT(*) as count'));

        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
        }

        if (isset($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        $data = $query->groupBy('lifecycle_stage')
            ->orderBy('average_score', 'desc')
            ->get();

        return $this->formatDataForChart($data, 'bar', 'lifecycle_stage', 'average_score');
    }

    public function aggregateLeadScoreData(array $filters = [])
    {
        $query = Lead::select(DB::raw('FLOOR(score / 10) * 10 as score_range'), DB::raw('COUNT(*) as count'));

        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
        }

        if (isset($filters['lifecycle_stage'])) {
            $query->where('lifecycle_stage', $filters['lifecycle_stage']);
        }

        $data = $query->groupBy('score_range')
            ->orderBy('score_range')
            ->get()
            ->map(function ($item) {
                $item->score_range = $item->score_range . ' - ' . ($item->score_range + 9);
                return $item;
            });

        return $this->formatDataForChart($data, 'pie', 'score_range', 'count');
    }

    private function formatDataForChart($data, $chartType, $labelKey, $valueKey)
    {
        $labels = $data->pluck($labelKey);
        $values = $data->pluck($valueKey);

        return [
            'type' => $chartType,
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => ucfirst(str_replace('_', ' ', $valueKey)),
                        'data' => $values,
                    ]
                ]
            ]
        ];
    }
}
