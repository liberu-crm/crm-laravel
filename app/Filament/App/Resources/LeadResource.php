<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\LeadResource\Pages;
use App\Filament\App\Resources\LeadResource\Pages\LeadQualityReport;
use App\Filament\App\Resources\LeadResource\Pages\ViewLead;
use App\Filament\Exports\LeadExporter;
use App\Models\Lead;
use App\Support\AccessContext;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-funnel';

    #[\Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('status')
                    ->options([
                        'new' => 'New',
                        'contacted' => 'Contacted',
                        'qualified' => 'Qualified',
                        'lost' => 'Lost',
                    ])
                    ->required(),
                Forms\Components\Select::make('source')
                    ->options([
                        'website' => 'Website',
                        'referral' => 'Referral',
                        'social_media' => 'Social Media',
                        'direct' => 'Direct',
                        'other' => 'Other',
                    ]),
                // Masked-role viewers get the read-only placeholder on edit; the
                // hidden real field isn't validated or dehydrated, so a save keeps
                // the stored value.
                Forms\Components\TextInput::make('potential_value')
                    ->numeric()
                    ->prefix('$')
                    ->visible(fn (string $operation): bool => $operation === 'create' || ! AccessContext::shouldMaskFields()),
                Forms\Components\Placeholder::make('potential_value_masked')
                    ->label('Potential Value')
                    ->content('[hidden]')
                    ->visible(fn (string $operation): bool => $operation !== 'create' && AccessContext::shouldMaskFields()),
                Forms\Components\DatePicker::make('expected_close_date'),
                Forms\Components\TextInput::make('score')
                    ->numeric()
                    ->label('Lead Score'),
                Forms\Components\Select::make('lifecycle_stage')
                    ->options([
                        'subscriber' => 'Subscriber',
                        'lead' => 'Lead',
                        'marketing_qualified_lead' => 'Marketing Qualified Lead',
                        'sales_qualified_lead' => 'Sales Qualified Lead',
                        'opportunity' => 'Opportunity',
                    ]),
            ]);
    }

    #[\Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('status')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('source')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('potential_value')
                    ->formatStateUsing(fn (?string $state): string => AccessContext::shouldMaskFields()
                        ? '[hidden]'
                        : '$'.number_format((float) $state, 2))
                    ->sortable(),
                Tables\Columns\TextColumn::make('score')
                    ->sortable()
                    ->label('Lead Score'),
                Tables\Columns\TextColumn::make('lifecycle_stage')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                    ->schema([
                        Forms\Components\TextInput::make('score_from')->numeric(),
                        Forms\Components\TextInput::make('score_to')->numeric(),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['score_from'],
                            fn (Builder $query, $score): Builder => $query->where('score', '>=', $score),
                        )
                        ->when(
                            $data['score_to'],
                            fn (Builder $query, $score): Builder => $query->where('score', '<=', $score),
                        )),
            ])
            ->headerActions([
                // Gated off for masked (`free`) roles: a CSV would otherwise
                // bypass the potential_value masking applied in the UI.
                ExportAction::make()
                    ->exporter(LeadExporter::class)
                    ->visible(fn (): bool => ! AccessContext::shouldMaskFields()),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    #[\Override]
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeads::route('/'),
            'create' => Pages\CreateLead::route('/create'),
            'view' => ViewLead::route('/{record}'),
            'edit' => Pages\EditLead::route('/{record}/edit'),
            'quality-report' => LeadQualityReport::route('/quality-report'),
        ];
    }
}
