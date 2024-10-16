<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Deal;
use App\Models\Activity;
use App\Models\Workflow;
use Illuminate\Support\Facades\DB;

class ReportingService
{
    public function getContactInteractionsData(array $filters = [])
    {
        $query = Contact::with('activities')->withCount('activities');

        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
        }

        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        if (isset($filters['status'])) {
            $query->filterByStatus($filters['status']);
        }

        if (isset($filters['source'])) {
            $query->filterBySource($filters['source']);
        }

        if (isset($filters['lifecycle_stage'])) {
            $query->filterByLifecycleStage($filters['lifecycle_stage']);
        }

        $data = $query->orderByDesc('activities_count')
            ->take(10)
            ->get();

        return $this->formatDataForChart($data, 'pie', 'name', 'activities_count');
    }

    public function getSalesPipelineData(array $filters = [])
    {
        $query = Deal::select('stage', DB::raw('count(*) as count'), DB::raw('sum(value) as total_value'));

        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
        }

        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        if (isset($filters['status'])) {
            $query->filterByStatus($filters['status']);
        }

        if (isset($filters['potential_value_min']) && isset($filters['potential_value_max'])) {
            $query->filterByPotentialValue($filters['potential_value_min'], $filters['potential_value_max']);
        }

        $data = $query->groupBy('stage')->get();

        return $this->formatDataForChart($data, 'bar', 'stage', 'total_value');
    }

    public function getCustomerEngagementData(array $filters = [])
    {
        $query = Activity::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'));

        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
        }

        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        if (isset($filters['type'])) {
            $query->filterByType($filters['type']);
        }

        $data = $query->groupBy('date')
            ->orderBy('date')
            ->get();

        return $this->formatDataForChart($data, 'line', 'date', 'count');
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