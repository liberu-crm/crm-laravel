<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\OAuthConfigurationResource\Pages;
use App\Models\OAuthConfiguration;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;

class OAuthConfigurationResource extends Resource
{
    protected static ?string $model = OAuthConfiguration::class;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('service_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('client_id')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('client_secret')
                    ->required()
                    ->maxLength(255),
                Forms\Components\KeyValue::make('additional_settings'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('service_name'),
                Tables\Columns\TextColumn::make('client_id'),
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
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListOAuthConfigurations::route('/'),
            'create' => Pages\CreateOAuthConfiguration::route('/create'),
            'edit' => Pages\EditOAuthConfiguration::route('/{record}/edit'),
        ];
    }
}