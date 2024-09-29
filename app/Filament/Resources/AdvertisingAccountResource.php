<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdvertisingAccountResource\Pages;
use App\Models\AdvertisingAccount;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AdvertisingAccountResource extends Resource
{
    protected static ?string $model = AdvertisingAccount::class;

    protected static ?string $navigationIcon = 'heroicon-o-speakerphone';

    protected static ?string $navigationGroup = 'Advertising';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('platform')
                            ->options([
                                'Google Ads' => 'Google Ads',
                                'Facebook Ads' => 'Facebook Ads',
                                'LinkedIn Ads' => 'LinkedIn Ads',
                                'Instagram Ads' => 'Instagram Ads',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('account_id')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('access_token')
                            ->required()
                            ->maxLength(255)
                            ->password(),
                        Forms\Components\TextInput::make('refresh_token')
                            ->maxLength(255)
                            ->password(),
                        Forms\Components\Toggle::make('status')
                            ->required(),
                        Forms\Components\DateTimePicker::make('last_sync'),
                        Forms\Components\KeyValue::make('metadata'),
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
                    'danger' => 'Instagram Ads',
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
                        'Instagram Ads' => 'Instagram Ads',
                    ]),
                Tables\Filters\BooleanFilter::make('status'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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