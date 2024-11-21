<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\AdvertisingAccountResource\Pages;
use App\Models\AdvertisingAccount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Filters\BooleanFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Actions\Action;

class AdvertisingAccountResource extends Resource
{
    protected static ?string $model = AdvertisingAccount::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-bottom-center';

    protected static ?string $navigationGroup = 'Advertising';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Account Name')
                            ->helperText('Give this account a memorable name'),
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\Actions::make([
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
                                ])
                            ])
                            ->visible(fn ($record) => ! $record),
                        Forms\Components\Toggle::make('status')
                            ->label('Active')
                            ->default(true)
                            ->visible(fn ($record) => $record),
                        Forms\Components\DateTimePicker::make('last_sync')
                            ->disabled()
                            ->visible(fn ($record) => $record),
                        Forms\Components\KeyValue::make('metadata')
                            ->disabled()
                            ->visible(fn ($record) => $record),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\BadgeColumn::make('platform')->colors([
                    'primary' => 'Google Ads',
                    'success' => 'Facebook Ads',
                    'warning' => 'LinkedIn Ads',
                    'danger' => 'Microsoft Ads',
                ]),
                Tables\Columns\TextColumn::make('account_id')->searchable(),
                Tables\Columns\BooleanColumn::make('status'),
                Tables\Columns\TextColumn::make('last_sync')
                    ->dateTime(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('platform')
                    ->options([
                        'Google Ads' => 'Google Ads',
                        'Facebook Ads' => 'Facebook Ads',
                        'LinkedIn Ads' => 'LinkedIn Ads',
                        'Microsoft Ads' => 'Microsoft Ads',
                    ]),
                BooleanFilter::make('status'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('refresh_token')
                    ->label('Refresh Token')
                    ->icon('heroicon-o-arrow-path')
                    ->action(fn ($record) => $record->refreshToken())
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Add relations here when you create Campaign, AdSet, and Ad resources
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdvertisingAccounts::route('/'),
            'create' => Pages\CreateAdvertisingAccount::route('/create'),
            'edit' => Pages\EditAdvertisingAccount::route('/{record}/edit'),
        ];
    }
}