<?php

namespace App\Filament\Pages;

use Filament\Schemas\Schema;
use App\Services\ReportingService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ReportCustomizer extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected string $view = 'filament.pages.report-customizer';

    public ?array $data = [];
    public ?string $reportType = 'contact-interactions';
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
                $raw = $reportingService->getContactInteractionsData($filters);
                $this->data = [
                    'type' => 'pie',
                    'data' => [
                        'labels'   => $raw->pluck('name'),
                        'datasets' => [
                            [
                                'label' => 'Activities count',
                                'data'  => $raw->pluck('activities_count'),
                            ],
                        ],
                    ],
                    'raw' => $raw,
                ];
                break;
            case 'sales-pipeline':
                $raw = $reportingService->getSalesPipelineData($filters);
                $this->data = [
                    'type' => 'bar',
                    'data' => [
                        'labels'   => $raw->pluck('stage'),
                        'datasets' => [
                            [
                                'label' => 'Total value',
                                'data'  => $raw->pluck('total_value'),
                            ],
                            [
                                'label' => 'Count',
                                'data'  => $raw->pluck('count'),
                            ],
                        ],
                    ],
                    'raw' => $raw,
                ];
                break;
            case 'customer-engagement':
                $raw = $reportingService->getCustomerEngagementData($filters);
                $this->data = [
                    'type' => 'line',
                    'data' => [
                        'labels'   => $raw->pluck('date'),
                        'datasets' => [
                            [
                                'label' => 'Count',
                                'data'  => $raw->pluck('count'),
                            ],
                        ],
                    ],
                    'raw' => $raw,
                ];
                break;
        }
    }

    public function exportPdf(): void
    {
        $this->generateReport();

        if (!class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            Log::warning('barryvdh/laravel-dompdf is not installed. PDF export is unavailable.');
            $this->dispatch('report-exported', ['filename' => null]);
            return;
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('filament.pages.report-customizer.pdf', [
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

        $raw = $this->data['raw'] ?? collect();
        $rows = $raw->toArray();

        if (!empty($rows)) {
            $file = fopen($path, 'w');
            fputcsv($file, array_keys($rows[0]));
            foreach ($rows as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        }

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