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
        $query = Contact::select('contacts.*', DB::raw('COUNT(activities.id) as activities_count'))
            ->leftJoin('activities', function ($join) {
                $join->on('activities.activitable_id', '=', 'contacts.id')
                     ->where('activities.activitable_type', '=', Contact::class);
            });

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('activities.created_at', [$filters['start_date'], $filters['end_date']]);
        }

        if (!empty($filters['contact_id'])) {
            $query->where('contacts.id', $filters['contact_id']);
        }

        return $query->groupBy('contacts.id')
            ->orderByDesc('activities_count')
            ->get();
    }

    public function getSalesPipelineData(array $filters = [])
    {
        $query = Deal::select(
            'stage',
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(value) as total_value'),
            DB::raw('AVG(probability) as avg_probability')
        );

        if (!empty($filters['pipeline_id'])) {
            $query->where('pipeline_id', $filters['pipeline_id']);
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
        }

        return $query->where('stage', '!=', 'lost')
            ->groupBy('stage')
            ->orderBy('stage')
            ->get();
    }

    public function getCustomerEngagementData(array $filters = [])
    {
        $dateExpression = DB::raw('DATE(created_at)');

        $query = Activity::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count')
        );

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
        }

        return $query->groupBy($dateExpression)
            ->orderBy($dateExpression)
            ->get();
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
