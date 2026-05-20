<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\LeadResource\Pages;
use App\Filament\App\Resources\LeadResource\Pages\LeadQualityReport;
use App\Models\Lead;
use App\Services\TwilioService;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-funnel';

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
                Forms\Components\TextInput::make('potential_value')
                    ->numeric()
                    ->prefix('$'),
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
                    ->money('usd')
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
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
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

