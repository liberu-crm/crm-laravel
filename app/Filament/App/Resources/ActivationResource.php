<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\ActivationResource\Pages\CreateActivation;
use App\Filament\App\Resources\ActivationResource\Pages\EditActivation;
use App\Filament\App\Resources\ActivationResource\Pages\ListActivations;
use App\Models\Activation;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ActivationResource extends Resource
{
    protected static ?string $model = Activation::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'email')
                    ->preload(),
                TextInput::make('token'),
                TextInput::make('ip_address'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('token')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('ip_address')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('User'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
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
            'index' => ListActivations::route('/'),
            'create' => CreateActivation::route('/create'),
            'edit' => EditActivation::route('/{record}/edit'),
        ];
    }
}
