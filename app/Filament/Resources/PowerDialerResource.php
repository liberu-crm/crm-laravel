<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PowerDialerResource\Pages;
use App\Models\PowerDialerSetting;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;

class PowerDialerResource extends Resource
{
    protected static ?string $model = PowerDialerSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-phone';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('default_message')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('call_timeout')
                    ->options([
                        15 => '15 seconds',
                        30 => '30 seconds',
                        45 => '45 seconds',
                        60 => '60 seconds',
                    ])
                    ->required(),
                Forms\Components\Toggle::make('record_calls')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('default_message'),
                Tables\Columns\TextColumn::make('call_timeout'),
                Tables\Columns\BooleanColumn::make('record_calls'),
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
            'index' => Pages\ListPowerDialerSettings::route('/'),
            'create' => Pages\CreatePowerDialerSetting::route('/create'),
            'edit' => Pages\EditPowerDialerSetting::route('/{record}/edit'),
        ];
    }
}