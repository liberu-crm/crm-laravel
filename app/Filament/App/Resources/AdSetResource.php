<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\AdSetResource\Pages\CreateAdSet;
use App\Filament\App\Resources\AdSetResource\Pages\EditAdSet;
use App\Filament\App\Resources\AdSetResource\Pages\ListAdSets;
use App\Models\AdSet;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AdSetResource extends Resource
{
    protected static ?string $model = AdSet::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static string | \UnitEnum | null $navigationGroup = 'Advertising';

    protected static ?string $navigationLabel = 'Ad Sets';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('advertising_account_id')
                    ->relationship('advertisingAccount', 'name')
                    ->required()
                    ->label('Advertising Account'),
                Select::make('campaign_id')
                    ->relationship('campaign', 'name')
                    ->required()
                    ->label('Campaign'),
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
                TextInput::make('budget')
                    ->numeric()
                    ->prefix('$'),
                Select::make('budget_type')
                    ->options([
                        'daily' => 'Daily',
                        'lifetime' => 'Lifetime',
                    ])
                    ->default('daily'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('advertisingAccount.name')->label('Account')->searchable(),
                TextColumn::make('campaign.name')->label('Campaign')->searchable(),
                BadgeColumn::make('status')->colors([
                    'success' => 'active',
                    'warning' => 'paused',
                    'secondary' => 'archived',
                    'danger' => 'deleted',
                ]),
                TextColumn::make('budget')->money('USD'),
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
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAdSets::route('/'),
            'create' => CreateAdSet::route('/create'),
            'edit' => EditAdSet::route('/{record}/edit'),
        ];
    }
}
