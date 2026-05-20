<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Services\ReportingService;

class ReportCustomizer extends Component
{
    public $reportType = 'contact-interactions';
    public $dateRange = 'last-30-days';
    public $filters = [];

    protected $reportingService;

    public function boot(ReportingService $reportingService)
    {
        $this->reportingService = $reportingService;
    }

    public function render()
    {
        $data = $this->generateReport();
        return view('livewire.report-customizer', compact('data'));
    }

    public function generateReport()
    {
        switch ($this->reportType) {
            case 'contact-interactions':
                $raw = $this->reportingService->getContactInteractionsData($this->filters);
                return [
                    'type' => 'pie',
                    'data' => [
                        'labels'   => $raw->pluck('name'),
                        'datasets' => [['label' => 'Activities count', 'data' => $raw->pluck('activities_count')]],
                    ],
                    'raw' => $raw,
                ];
            case 'sales-pipeline':
                $raw = $this->reportingService->getSalesPipelineData($this->filters);
                return [
                    'type' => 'bar',
                    'data' => [
                        'labels'   => $raw->pluck('stage'),
                        'datasets' => [['label' => 'Total value', 'data' => $raw->pluck('total_value')]],
                    ],
                    'raw' => $raw,
                ];
            case 'customer-engagement':
                $raw = $this->reportingService->getCustomerEngagementData($this->filters);
                return [
                    'type' => 'line',
                    'data' => [
                        'labels'   => $raw->pluck('date'),
                        'datasets' => [['label' => 'Count', 'data' => $raw->pluck('count')]],
                    ],
                    'raw' => $raw,
                ];
            default:
                return [];
        }
    }

    public function updatedReportType()
    {
        $this->filters = [];
    }

    public function updatedDateRange()
    {
        $this->filters['date_range'] = $this->dateRange;
    }
}