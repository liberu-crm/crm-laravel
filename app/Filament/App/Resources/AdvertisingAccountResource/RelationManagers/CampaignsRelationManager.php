<?php

namespace App\Filament\App\Resources\AdvertisingAccountResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CampaignsRelationManager extends RelationManager
{
    protected static string $relationship = 'campaigns';

    protected static ?string $title = 'Campaigns';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('external_id')
                    ->maxLength(255)
                    ->label('External ID'),
                Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'paused' => 'Paused',
                        'archived' => 'Archived',
                        'deleted' => 'Deleted',
                    ])
                    ->default('active')
                    ->required(),
                Select::make('objective')
                    ->options([
                        'awareness' => 'Awareness',
                        'consideration' => 'Consideration',
                        'conversion' => 'Conversion',
                    ]),
                TextInput::make('budget')
                    ->numeric()
                    ->prefix('$'),
                Select::make('budget_type')
                    ->options([
                        'daily' => 'Daily',
                        'lifetime' => 'Lifetime',
                    ])
                    ->default('daily'),
                DateTimePicker::make('start_date'),
                DateTimePicker::make('end_date'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                BadgeColumn::make('status')->colors([
                    'success' => 'active',
                    'warning' => 'paused',
                    'secondary' => 'archived',
                    'danger' => 'deleted',
                ]),
                BadgeColumn::make('objective'),
                TextColumn::make('budget')->money('USD'),
                TextColumn::make('start_date')->dateTime(),
                TextColumn::make('end_date')->dateTime(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'paused' => 'Paused',
                        'archived' => 'Archived',
                        'deleted' => 'Deleted',
                    ]),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }
}
