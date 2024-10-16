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

        return $query->orderByDesc('activities_count')
            ->take(10)
            ->get();
    }

    public function getSalesPipelineData(array $filters = [])
    {
        $query = Deal::select('stage', DB::raw('count(*) as count'), DB::raw('sum(value) as total_value'));

        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        if (isset($filters['status'])) {
            $query->filterByStatus($filters['status']);
        }

        if (isset($filters['potential_value_min']) && isset($filters['potential_value_max'])) {
            $query->filterByPotentialValue($filters['potential_value_min'], $filters['potential_value_max']);
        }

        return $query->groupBy('stage')->get();
    }

    public function getCustomerEngagementData(array $filters = [])
    {
        $query = Activity::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'));

        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        if (isset($filters['type'])) {
            $query->filterByType($filters['type']);
        }

        if (isset($filters['date_start']) && isset($filters['date_end'])) {
            $query->filterByDateRange($filters['date_start'], $filters['date_end']);
        }

        return $query->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    public function getWorkflowPerformanceData(array $filters = [])
    {
        $query = Workflow::withCount(['leads', 'contacts', 'deals'])
            ->select('id', 'name', 'created_at')
            ->selectRaw('(leads_count + contacts_count + deals_count) as total_executions');

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('id', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->orderByDesc('total_executions')
            ->get();
    }
}