<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\WhatsAppNumberResource\Pages;
use App\Models\WhatsAppNumber;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\App\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;

class WhatsAppNumberResource extends Resource
{
    protected static ?string $model = WhatsAppNumber::class;

    protected static ?string $navigationIcon = 'heroicon-o-phone';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('number')
                    ->required()
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('display_name')
                    ->required(),
                Forms\Components\Toggle::make('is_active')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number'),
                Tables\Columns\TextColumn::make('display_name'),
                Tables\Columns\BooleanColumn::make('is_active'),
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
            'index' => Pages\ListWhatsAppNumbers::route('/'),
            'create' => Pages\CreateWhatsAppNumber::route('/create'),
            'edit' => Pages\EditWhatsAppNumber::route('/{record}/edit'),
        ];
    }
}