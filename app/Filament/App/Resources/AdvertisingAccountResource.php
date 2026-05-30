<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\AdvertisingAccountResource\Pages\CreateAdvertisingAccount;
use App\Filament\App\Resources\AdvertisingAccountResource\Pages\EditAdvertisingAccount;
use App\Filament\App\Resources\AdvertisingAccountResource\Pages\ListAdvertisingAccounts;
use App\Filament\App\Resources\AdvertisingAccountResource\RelationManagers\AdSetsRelationManager;
use App\Filament\App\Resources\AdvertisingAccountResource\RelationManagers\AdsRelationManager;
use App\Filament\App\Resources\AdvertisingAccountResource\RelationManagers\CampaignsRelationManager;
use App\Models\AdvertisingAccount;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AdvertisingAccountResource extends Resource
{
    protected static ?string $model = AdvertisingAccount::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-bottom-center';

    protected static string|\UnitEnum|null $navigationGroup = 'Advertising';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Account Name')
                            ->helperText('Give this account a memorable name'),
                        Grid::make()
                            ->schema([
                                Actions::make([
                                    Action::make('connect_google')
                                        ->label('Connect Google Ads')
                                        ->icon('heroicon-o-arrow-right-circle')
                                        ->url(route('oauth.redirect', ['provider' => 'google']))
                                        ->openUrlInNewTab(),
                                    Action::make('connect_facebook')
                                        ->label('Connect Facebook Ads')
                                        ->icon('heroicon-o-arrow-right-circle')
                                        ->url(route('oauth.redirect', ['provider' => 'facebook']))
                                        ->openUrlInNewTab(),
                                    Action::make('connect_linkedin')
                                        ->label('Connect LinkedIn Ads')
                                        ->icon('heroicon-o-arrow-right-circle')
                                        ->url(route('oauth.redirect', ['provider' => 'linkedin']))
                                        ->openUrlInNewTab(),
                                    Action::make('connect_microsoft')
                                        ->label('Connect Microsoft Ads')
                                        ->icon('heroicon-o-arrow-right-circle')
                                        ->url(route('oauth.redirect', ['provider' => 'microsoft']))
                                        ->openUrlInNewTab(),
                                ]),
                            ])
                            ->visible(fn ($record) => ! $record),
                        Toggle::make('status')
                            ->label('Active')
                            ->default(true)
                            ->visible(fn ($record) => $record),
                        DateTimePicker::make('last_sync')
                            ->disabled()
                            ->visible(fn ($record) => $record),
                        KeyValue::make('metadata')
                            ->disabled()
                            ->visible(fn ($record) => $record),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                BadgeColumn::make('platform')->colors([
                    'primary' => 'Google Ads',
                    'success' => 'Facebook Ads',
                    'warning' => 'LinkedIn Ads',
                    'danger' => 'Microsoft Ads',
                ]),
                TextColumn::make('account_id')->searchable(),
                BooleanColumn::make('status'),
                TextColumn::make('last_sync')
                    ->dateTime(),
                TextColumn::make('created_at')
                    ->dateTime(),
            ])
            ->filters([
                SelectFilter::make('platform')
                    ->options([
                        'Google Ads' => 'Google Ads',
                        'Facebook Ads' => 'Facebook Ads',
                        'LinkedIn Ads' => 'LinkedIn Ads',
                        'Microsoft Ads' => 'Microsoft Ads',
                    ]),
                // BooleanFilter::make('status'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                Action::make('refresh_token')
                    ->label('Refresh Token')
                    ->icon('heroicon-o-arrow-path')
                    ->action(fn ($record) => $record->refreshToken())
                    ->requiresConfirmation(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            CampaignsRelationManager::class,
            AdSetsRelationManager::class,
            AdsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAdvertisingAccounts::route('/'),
            'create' => CreateAdvertisingAccount::route('/create'),
            'edit' => EditAdvertisingAccount::route('/{record}/edit'),
        ];
    }
}
