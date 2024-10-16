<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\LeadResource\Pages;
use App\Models\Lead;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

use App\Services\TwilioService;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;

class LeadResource extends Resource
{
    // ... (existing code remains unchanged)

use App\Filament\App\Resources\LeadResource\Pages\LeadQualityReport;
use Filament\Resources\Pages\Page;

class LeadResource extends Resource
{
    // ... (existing code remains unchanged)

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // ... (existing columns remain unchanged)
                Tables\Columns\TextColumn::make('score')
                    ->sortable()
                    ->label('Lead Score'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'new' => 'New',
                        'contacted' => 'Contacted',
                        'qualified' => 'Qualified',
                        'lost' => 'Lost',
                    ]),
                Tables\Filters\Filter::make('score')
                    ->form([
                        Forms\Components\TextInput::make('score_from')->numeric(),
                        Forms\Components\TextInput::make('score_to')->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['score_from'],
                                fn (Builder $query, $score): Builder => $query->where('score', '>=', $score),
                            )
                            ->when(
                                $data['score_to'],
                                fn (Builder $query, $score): Builder => $query->where('score', '<=', $score),
                            );
                    }),
            ])
            ->actions([
                // ... (existing actions remain unchanged)
            ])
            ->bulkActions([
                // ... (existing bulk actions remain unchanged)
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeads::route('/'),
            'create' => Pages\CreateLead::route('/create'),
            'edit' => Pages\EditLead::route('/{record}/edit'),
            'quality-report' => LeadQualityReport::route('/quality-report'),
        ];
    }
}

// Create a new file: app/Filament/App/Resources/LeadResource/Pages/LeadQualityReport.php
namespace App\Filament\App\Resources\LeadResource\Pages;

use App\Filament\App\Resources\LeadResource;
use App\Services\ReportingService;
use Filament\Resources\Pages\Page;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;

class LeadQualityReport extends Page implements HasForms
{
    protected static string $resource = LeadResource::class;

    protected static string $view = 'filament.app.resources.lead-resource.pages.lead-quality-report';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('start_date'),
                DatePicker::make('end_date'),
                Select::make('lifecycle_stage')
                    ->options(Lead::LIFECYCLE_STAGES),
            ]);
    }

    public function submit(): void
    {
        $reportingService = app(ReportingService::class);
        $this->leadQualityReport = $reportingService->generateLeadQualityReport($this->form->getState());
        $this->leadScoreDistribution = $reportingService->aggregateLeadScoreData($this->form->getState());
    }
}

    // ... (rest of the code remains unchanged)
}