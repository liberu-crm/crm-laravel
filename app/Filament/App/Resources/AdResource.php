<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\AdResource\Pages\CreateAd;
use App\Filament\App\Resources\AdResource\Pages\EditAd;
use App\Filament\App\Resources\AdResource\Pages\ListAds;
use App\Models\Ad;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AdResource extends Resource
{
    protected static ?string $model = Ad::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string | \UnitEnum | null $navigationGroup = 'Advertising';

    protected static ?string $navigationLabel = 'Ads';

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
                Select::make('ad_set_id')
                    ->relationship('adSet', 'name')
                    ->required()
                    ->label('Ad Set'),
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
                TextInput::make('headline')
                    ->maxLength(255),
                Textarea::make('description')
                    ->maxLength(65535),
                TextInput::make('destination_url')
                    ->url()
                    ->maxLength(255)
                    ->label('Destination URL'),
                TextInput::make('creative_url')
                    ->url()
                    ->maxLength(255)
                    ->label('Creative URL'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('advertisingAccount.name')->label('Account')->searchable(),
                TextColumn::make('campaign.name')->label('Campaign')->searchable(),
                TextColumn::make('adSet.name')->label('Ad Set')->searchable(),
                BadgeColumn::make('status')->colors([
                    'success' => 'active',
                    'warning' => 'paused',
                    'secondary' => 'archived',
                    'danger' => 'deleted',
                ]),
                TextColumn::make('headline')->limit(50),
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
            'index' => ListAds::route('/'),
            'create' => CreateAd::route('/create'),
            'edit' => EditAd::route('/{record}/edit'),
        ];
    }
}
