<?php

namespace App\Filament\App\Resources\LeadResource\Pages;

use App\Filament\App\Resources\LeadResource;
use App\Models\Lead;
use App\Services\ReportingService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Schema;

class LeadQualityReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = LeadResource::class;

    protected string $view = 'filament.app.resources.lead-resource.pages.lead-quality-report';

    public ?array $data = [];

    public ?array $leadQualityReport = null;

    public ?array $leadScoreDistribution = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                DatePicker::make('start_date'),
                DatePicker::make('end_date'),
                Select::make('lifecycle_stage')
                    ->options(array_combine(Lead::LIFECYCLE_STAGES, Lead::LIFECYCLE_STAGES)),
            ]);
    }

    public function submit(): void
    {
        $reportingService = app(ReportingService::class);
        $state = $this->form->getState();
        $this->leadQualityReport = $reportingService->generateLeadQualityReport($state);
        $this->leadScoreDistribution = $reportingService->aggregateLeadScoreData($state);
    }
}
