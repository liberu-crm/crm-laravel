<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\WhatsAppNumberResource\Pages\CreateWhatsAppNumber;
use App\Filament\App\Resources\WhatsAppNumberResource\Pages\EditWhatsAppNumber;
use App\Filament\App\Resources\WhatsAppNumberResource\Pages\ListWhatsAppNumbers;
use App\Models\WhatsAppNumber;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WhatsAppNumberResource extends Resource
{
    protected static ?string $model = WhatsAppNumber::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-phone';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('number')
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('display_name')
                    ->required(),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number'),
                TextColumn::make('display_name'),
                BooleanColumn::make('is_active'),
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
            'index' => ListWhatsAppNumbers::route('/'),
            'create' => CreateWhatsAppNumber::route('/create'),
            'edit' => EditWhatsAppNumber::route('/{record}/edit'),
        ];
    }
}
