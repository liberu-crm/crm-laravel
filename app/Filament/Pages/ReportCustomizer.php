<?php

namespace App\Filament\Pages;

use Filament\Schemas\Schema;
use App\Services\ReportingService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportCustomizer extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected string $view = 'filament.pages.report-customizer';

    public ?array $data = [];
    public string $reportType = 'contact-interactions';
    public ?string $startDate = null;
    public ?string $endDate = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('reportType')
                    ->label('Report Type')
                    ->options([
                        'contact-interactions' => 'Contact Interactions',
                        'sales-pipeline' => 'Sales Pipeline',
                        'customer-engagement' => 'Customer Engagement',
                    ])
                    ->required()
                    ->live(),
                DatePicker::make('startDate')
                    ->label('Start Date'),
                DatePicker::make('endDate')
                    ->label('End Date'),
            ]);
    }

    public function generateReport(): void
    {
        $reportingService = app(ReportingService::class);

        $filters = [
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
        ];

        switch ($this->reportType) {
            case 'contact-interactions':
                $this->data = $reportingService->getContactInteractionsData($filters);
                break;
            case 'sales-pipeline':
                $this->data = $reportingService->getSalesPipelineData($filters);
                break;
            case 'customer-engagement':
                $this->data = $reportingService->getCustomerEngagementData($filters);
                break;
        }
    }

    public function exportPdf(): void
    {
        $this->generateReport();

        $pdf = Pdf::loadView('filament.pages.report-customizer.pdf', [
            'data' => $this->data,
            'reportType' => $this->reportType,
        ]);

        $filename = $this->reportType . '_' . now()->format('Y-m-d') . '.pdf';
        Storage::put('public/reports/' . $filename, $pdf->output());

        $this->dispatch('report-exported', ['filename' => $filename]);
    }

    public function exportCsv(): void
    {
        $this->generateReport();

        $filename = $this->reportType . '_' . now()->format('Y-m-d') . '.csv';
        $path = storage_path('app/public/reports/' . $filename);

        $file = fopen($path, 'w');
        fputcsv($file, array_keys($this->data[0]));

        foreach ($this->data as $row) {
            fputcsv($file, $row);
        }

        fclose($file);

        $this->dispatch('report-exported', ['filename' => $filename]);
    }

    protected function getActions(): array
    {
        return [
            Action::make('generate')
                ->label('Generate Report')
                ->action('generateReport'),
            Action::make('exportPdf')
                ->label('Export PDF')
                ->action('exportPdf'),
            Action::make('exportCsv')
                ->label('Export CSV')
                ->action('exportCsv'),
        ];
    }
}