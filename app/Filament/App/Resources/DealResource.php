<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\DealResource\Pages\CreateDeal;
use App\Filament\App\Resources\DealResource\Pages\EditDeal;
use App\Filament\App\Resources\DealResource\Pages\ListDeals;
use App\Models\Deal;
use App\Models\Stage;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DealResource extends Resource
{
    protected static ?string $model = Deal::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Deals';

    #[\Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('value')
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('stage')
                    ->maxLength(255),
                DatePicker::make('close_date')
                    ->label('Close Date'),
                TextInput::make('probability')
                    ->numeric()
                    ->suffix('%'),
                Select::make('contact_id')
                    ->relationship('contact', 'name')
                    ->searchable()
                    ->nullable(),
                Select::make('user_id')
                    ->label('Owner')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->nullable(),
                Select::make('pipeline_id')
                    ->relationship('pipeline', 'name')
                    ->searchable()
                    ->nullable(),
                // `stage` is also a plain string column, so the `stage()` relation
                // name collides — use options() instead of ->relationship() here.
                Select::make('stage_id')
                    ->label('Pipeline Stage')
                    ->options(Stage::pluck('name', 'id'))
                    ->searchable()
                    ->nullable(),
            ]);
    }

    #[\Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('value')
                    ->money('usd')
                    ->sortable(),
                TextColumn::make('stage')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('close_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('probability')
                    ->suffix('%')
                    ->sortable(),
                TextColumn::make('contact.name')
                    ->label('Contact')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Owner')
                    ->sortable(),
                TextColumn::make('pipeline.name')
                    ->label('Pipeline')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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

    #[\Override]
    public static function getPages(): array
    {
        return [
            'index' => ListDeals::route('/'),
            'create' => CreateDeal::route('/create'),
            'edit' => EditDeal::route('/{record}/edit'),
        ];
    }
}
