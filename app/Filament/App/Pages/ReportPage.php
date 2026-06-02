<?php

namespace App\Filament\App\Pages;

use App\Services\MailChimpService;
use App\Services\ReportingService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Schemas\Schema;

class ReportPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected string $view = 'filament.app.pages.report-page';

    public ?array $data = [];

    public ?string $selectedReport = null;

    public ?string $campaignId = null;

    public ?string $startDate = null;

    public ?string $endDate = null;

    public bool $mailchimpConfigured = false;

    protected ReportingService $reportingService;

    protected MailChimpService $mailChimpService;

    public function mount(ReportingService $reportingService, MailChimpService $mailChimpService): void
    {
        $this->reportingService = $reportingService;
        $this->mailChimpService = $mailChimpService;
        $this->mailchimpConfigured = $mailChimpService->isConfigured();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('selectedReport')
                    ->label('Report Type')
                    ->options([
                        'contact-interactions' => 'Contact Interactions',
                        'sales-pipeline' => 'Sales Pipeline',
                        'customer-engagement' => 'Customer Engagement',
                        'ab-test-results' => 'A/B Test Results',
                        'email-campaign-performance' => 'Email Campaign Performance',
                    ])
                    ->required()
                    ->live(),
                TextInput::make('campaignId')
                    ->label('Campaign ID')
                    ->visible(fn (callable $get): bool => in_array(
                        $get('selectedReport'), ['ab-test-results', 'email-campaign-performance']
                    )),
                DatePicker::make('startDate')
                    ->label('Start Date'),
                DatePicker::make('endDate')
                    ->label('End Date'),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [$this->generateReportAction()];
    }

    public function generateReportAction(): Action
    {
        return Action::make('generateReport')
            ->label('Generate Report')
            ->action(function (): void {
                $filters = [
                    'start_date' => $this->startDate,
                    'end_date' => $this->endDate,
                ];

                $this->data = match ($this->selectedReport) {
                    'contact-interactions' => $this->buildContactInteractionsData($filters),
                    'sales-pipeline' => $this->buildSalesPipelineData($filters),
                    'customer-engagement' => $this->buildCustomerEngagementData($filters),
                    'ab-test-results' => $this->mailChimpService->getABTestResults($this->campaignId),
                    'email-campaign-performance' => $this->mailChimpService->getCampaignReport($this->campaignId),
                    default => [],
                };
            });
    }

    private function buildContactInteractionsData(array $filters): array
    {
        $raw = $this->reportingService->getContactInteractionsData($filters);

        return [
            'type' => 'pie',
            'data' => [
                'labels' => $raw->pluck('name'),
                'datasets' => [['label' => 'Activities count', 'data' => $raw->pluck('activities_count')]],
            ],
            'raw' => $raw,
        ];
    }

    private function buildSalesPipelineData(array $filters): array
    {
        $raw = $this->reportingService->getSalesPipelineData($filters);

        return [
            'type' => 'bar',
            'data' => [
                'labels' => $raw->pluck('stage'),
                'datasets' => [['label' => 'Total value', 'data' => $raw->pluck('total_value')]],
            ],
            'raw' => $raw,
        ];
    }

    private function buildCustomerEngagementData(array $filters): array
    {
        $raw = $this->reportingService->getCustomerEngagementData($filters);

        return [
            'type' => 'line',
            'data' => [
                'labels' => $raw->pluck('date'),
                'datasets' => [['label' => 'Count', 'data' => $raw->pluck('count')]],
            ],
            'raw' => $raw,
        ];
    }
}
