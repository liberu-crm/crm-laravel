<?php

namespace App\Filament\App\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use App\Filament\App\Resources\CallSettingResource\Pages\ListCallSettings;
use App\Filament\App\Resources\CallSettingResource\Pages\CreateCallSetting;
use App\Filament\App\Resources\CallSettingResource\Pages\EditCallSetting;
use App\Filament\App\Resources\CallSettingResource\Pages;
use App\Models\CallSetting;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;

class CallSettingResource extends Resource
{
    protected static ?string $model = CallSetting::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chat-bubble-bottom-center';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('value')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->maxLength(65535),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('value'),
                TextColumn::make('description'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
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
            'index' => ListCallSettings::route('/'),
            'create' => CreateCallSetting::route('/create'),
            'edit' => EditCallSetting::route('/{record}/edit'),
        ];
    }
}