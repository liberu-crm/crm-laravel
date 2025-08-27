<?php

namespace App\Filament\App\Resources;

use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\App\Resources\ActivationResource\Pages\ListActivations;
use App\Filament\App\Resources\ActivationResource\Pages\CreateActivation;
use App\Filament\App\Resources\ActivationResource\Pages\EditActivation;
use Filament\Forms;
use Filament\Tables;
use App\Models\Activation;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Filament\App\Resources\ActivationResource\Pages;
use App\Filament\App\Resources\ActivationResource\RelationManagers;

class ActivationResource extends Resource
{
    protected static ?string $model = Activation::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

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
                    ->label('User')
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
