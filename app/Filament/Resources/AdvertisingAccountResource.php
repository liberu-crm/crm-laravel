<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdvertisingAccountResource\Pages;
use App\Models\AdvertisingAccount;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;

class AdvertisingAccountResource extends Resource
{
    protected static ?string $model = AdvertisingAccount::class;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('platform')
                    ->options([
                        'Google AdWords' => 'Google AdWords',
                        'LinkedIn Business' => 'LinkedIn Business',
                        'Facebook Advertising' => 'Facebook Advertising',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('account_id')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('access_token')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('refresh_token')
                    ->maxLength(255),
                Forms\Components\Toggle::make('status')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('platform'),
                Tables\Columns\TextColumn::make('account_id'),
                Tables\Columns\BooleanColumn::make('status'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
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