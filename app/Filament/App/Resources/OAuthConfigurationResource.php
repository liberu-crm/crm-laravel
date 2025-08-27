<?php

namespace App\Filament\App\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\KeyValue;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use App\Filament\App\Resources\OAuthConfigurationResource\Pages\ListOAuthConfigurations;
use App\Filament\App\Resources\OAuthConfigurationResource\Pages\CreateOAuthConfiguration;
use App\Filament\App\Resources\OAuthConfigurationResource\Pages\EditOAuthConfiguration;
use App\Filament\App\Resources\OAuthConfigurationResource\Pages;
use App\Models\OAuthConfiguration;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;

class OAuthConfigurationResource extends Resource
{
    protected static ?string $model = OAuthConfiguration::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chat-bubble-bottom-center';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('service_name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('client_id')
                    ->required()
                    ->maxLength(255),
                TextInput::make('client_secret')
                    ->required()
                    ->maxLength(255),
                KeyValue::make('additional_settings'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('service_name'),
                TextColumn::make('client_id'),
                TextColumn::make('created_at')
                    ->dateTime(),
                TextColumn::make('updated_at')
                    ->dateTime(),
            ])
            ->filters([
                //
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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOAuthConfigurations::route('/'),
            'create' => CreateOAuthConfiguration::route('/create'),
            'edit' => EditOAuthConfiguration::route('/{record}/edit'),
        ];
    }
}