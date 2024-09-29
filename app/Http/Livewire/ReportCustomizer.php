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
                return $this->reportingService->getContactInteractionsData($this->filters);
            case 'sales-pipeline':
                return $this->reportingService->getSalesPipelineData($this->filters);
            case 'customer-engagement':
                return $this->reportingService->getCustomerEngagementData($this->filters);
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