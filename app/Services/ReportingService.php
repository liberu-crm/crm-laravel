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
        // ... (existing code remains unchanged)
    }

    public function getSalesPipelineData(array $filters = [])
    {
        // ... (existing code remains unchanged)
    }

    public function getCustomerEngagementData(array $filters = [])
    {
        // ... (existing code remains unchanged)
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