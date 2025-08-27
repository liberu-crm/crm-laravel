<?php

namespace App\Filament\App\Pages;

use Filament\Schemas\Schema;
use App\Services\ReportingService;
use App\Services\MailChimpService;
use Filament\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Actions\Action;
use Illuminate\Contracts\View\View;

class ReportPage extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-chart-bar';
    protected string $view = 'filament.app.pages.report-page';

    public ?array $data = [];
    public ?string $selectedReport = null;
    public ?string $campaignId = null;
    public ?string $startDate = null;
    public ?string $endDate = null;

    protected $reportingService;
    protected $mailChimpService;

    public function __construct(ReportingService $reportingService, MailChimpService $mailChimpService)
    {
        parent::__construct();
        $this->reportingService = $reportingService;
        $this->mailChimpService = $mailChimpService;
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
                    ->visible(fn (callable $get) => in_array($get('selectedReport'), ['ab-test-results', 'email-campaign-performance'])),
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
            ->action(function (array $data) {
                $this->data = match ($this->selectedReport) {
                    'contact-interactions' => $this->reportingService->getContactInteractionsData($data),
                    'sales-pipeline' => $this->reportingService->getSalesPipelineData($data),
                    'customer-engagement' => $this->reportingService->getCustomerEngagementData($data),
                    'ab-test-results' => $this->mailChimpService->getABTestResults($this->campaignId),
                    'email-campaign-performance' => $this->mailChimpService->getCampaignReport($this->campaignId),
                    default => [],
                };
            });
    }

    public function getViewData(): array
    {
        return [
            'data' => $this->data,
        ];
    }
}