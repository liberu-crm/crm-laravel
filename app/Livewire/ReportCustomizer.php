<?php

namespace App\Livewire;

use App\Services\ReportingService;
use Livewire\Component;

class ReportCustomizer extends Component
{
    public string $reportType = 'contact-interactions';

    public string $dateRange = 'last-30-days';

    public array $filters = [];

    private ReportingService $reportingService;

    public function boot(ReportingService $reportingService): void
    {
        $this->reportingService = $reportingService;
    }

    public function updatedReportType(): void
    {
        $this->filters = [];
    }

    public function updatedDateRange(): void
    {
        $this->filters['date_range'] = $this->dateRange;
    }

    public function generateReport(): array
    {
        return match ($this->reportType) {
            'contact-interactions' => $this->buildContactInteractionsReport(),
            'sales-pipeline' => $this->buildSalesPipelineReport(),
            'customer-engagement' => $this->buildCustomerEngagementReport(),
            default => [],
        };
    }

    private function buildContactInteractionsReport(): array
    {
        $raw = $this->reportingService->getContactInteractionsData($this->filters);

        return [
            'type' => 'pie',
            'data' => [
                'labels' => $raw->pluck('name'),
                'datasets' => [['label' => 'Activities count', 'data' => $raw->pluck('activities_count')]],
            ],
            'raw' => $raw,
        ];
    }

    private function buildSalesPipelineReport(): array
    {
        $raw = $this->reportingService->getSalesPipelineData($this->filters);

        return [
            'type' => 'bar',
            'data' => [
                'labels' => $raw->pluck('stage'),
                'datasets' => [['label' => 'Total value', 'data' => $raw->pluck('total_value')]],
            ],
            'raw' => $raw,
        ];
    }

    private function buildCustomerEngagementReport(): array
    {
        $raw = $this->reportingService->getCustomerEngagementData($this->filters);

        return [
            'type' => 'line',
            'data' => [
                'labels' => $raw->pluck('date'),
                'datasets' => [['label' => 'Count', 'data' => $raw->pluck('count')]],
            ],
            'raw' => $raw,
        ];
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        $data = $this->generateReport();

        return view('livewire.report-customizer', ['data' => $data]);
    }
}
