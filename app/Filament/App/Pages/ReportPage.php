<?php

namespace App\Filament\App\Pages;

use App\Services\MailChimpService;
use App\Services\ReportingService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
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

    public function __construct(protected \App\Services\ReportingService $reportingService, protected \App\Services\MailChimpService $mailChimpService)
    {
        parent::__construct();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('selectedReport')
                    ->label('Select Report')
                    ->options([
                        'contact-interactions' => 'Contact Interactions',
                        'sales-pipeline' => 'Sales Pipeline',
                        'customer-engagement' => 'Customer Engagement',
                        'ab-test-results' => 'A/B Test Results',
                        'email-campaign-performance' => 'Email Campaign Performance',
                    ])
                    ->required(),
                Select::make('campaignId')
                    ->label('Campaign ID')
                    ->options([
                        // Add your campaign options here
                    ])
                    ->visible(fn (callable $get): bool => in_array($get('selectedReport'), ['ab-test-results', 'email-campaign-performance'])),
                DatePicker::make('startDate')
                    ->label('Start Date'),
                DatePicker::make('endDate')
                    ->label('End Date'),
            ]);
    }

    public function generateReport(): Action
    {
        return Action::make('generateReport')
            ->label('Generate Report')
            ->action(function (array $data): void {
                $filters = [
                    'start_date' => $this->startDate,
                    'end_date' => $this->endDate,
                ];
                switch ($this->selectedReport) {
                    case 'contact-interactions':
                        $raw = $this->reportingService->getContactInteractionsData($filters);
                        $this->data = [
                            'type' => 'pie',
                            'data' => [
                                'labels' => $raw->pluck('name'),
                                'datasets' => [['label' => 'Activities count', 'data' => $raw->pluck('activities_count')]],
                            ],
                            'raw' => $raw,
                        ];
                        break;
                    case 'sales-pipeline':
                        $raw = $this->reportingService->getSalesPipelineData($filters);
                        $this->data = [
                            'type' => 'bar',
                            'data' => [
                                'labels' => $raw->pluck('stage'),
                                'datasets' => [['label' => 'Total value', 'data' => $raw->pluck('total_value')]],
                            ],
                            'raw' => $raw,
                        ];
                        break;
                    case 'customer-engagement':
                        $raw = $this->reportingService->getCustomerEngagementData($filters);
                        $this->data = [
                            'type' => 'line',
                            'data' => [
                                'labels' => $raw->pluck('date'),
                                'datasets' => [['label' => 'Count', 'data' => $raw->pluck('count')]],
                            ],
                            'raw' => $raw,
                        ];
                        break;
                    case 'ab-test-results':
                        $this->data = $this->mailChimpService->getABTestResults($this->campaignId);
                        break;
                    case 'email-campaign-performance':
                        $this->data = $this->mailChimpService->getCampaignReport($this->campaignId);
                        break;
                    default:
                        $this->data = [];
                }
            });
    }

    #[\Override]
    public function getViewData(): array
    {
        return [
            'data' => $this->data,
        ];
    }
}
