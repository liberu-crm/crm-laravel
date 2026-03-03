<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\CampaignResource\Pages\CreateCampaign;
use App\Filament\App\Resources\CampaignResource\Pages\EditCampaign;
use App\Filament\App\Resources\CampaignResource\Pages\ListCampaigns;
use App\Models\Campaign;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CampaignResource extends Resource
{
    protected static ?string $model = Campaign::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-megaphone';

    protected static string | \UnitEnum | null $navigationGroup = 'Advertising';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('advertising_account_id')
                    ->relationship('advertisingAccount', 'name')
                    ->required()
                    ->label('Advertising Account'),
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('advertisingAccount.name')->label('Account')->searchable(),
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
                SelectFilter::make('objective')
                    ->options([
                        'awareness' => 'Awareness',
                        'consideration' => 'Consideration',
                        'conversion' => 'Conversion',
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
            'index' => ListCampaigns::route('/'),
            'create' => CreateCampaign::route('/create'),
            'edit' => EditCampaign::route('/{record}/edit'),
        ];
    }
}
