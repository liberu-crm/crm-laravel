<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\DealResource\Pages\CreateDeal;
use App\Filament\App\Resources\DealResource\Pages\EditDeal;
use App\Filament\App\Resources\DealResource\Pages\ListDeals;
use App\Filament\App\Resources\DealResource\Pages\ViewDeal;
use App\Filament\Exports\DealExporter;
use App\Models\Deal;
use App\Models\Stage;
use App\Support\AccessContext;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
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
                // Masked-role viewers get the read-only placeholder on edit; the
                // hidden real field isn't validated or dehydrated, so a save keeps
                // the stored value.
                TextInput::make('value')
                    ->numeric()
                    ->prefix('$')
                    ->visible(fn (string $operation): bool => $operation === 'create' || ! AccessContext::shouldMaskFields()),
                Placeholder::make('value_masked')
                    ->label('Value')
                    ->content('[hidden]')
                    ->visible(fn (string $operation): bool => $operation !== 'create' && AccessContext::shouldMaskFields()),
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
                    ->formatStateUsing(fn (?string $state): string => AccessContext::shouldMaskFields()
                        ? '[hidden]'
                        : '$'.number_format((float) $state, 2))
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
            ->headerActions([
                // Gated off for masked (`free`) roles: a CSV would otherwise
                // bypass the `value` masking applied in the UI.
                ExportAction::make()
                    ->exporter(DealExporter::class)
                    ->visible(fn (): bool => ! AccessContext::shouldMaskFields()),
            ])
            ->recordActions([
                ViewAction::make(),
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
            'view' => ViewDeal::route('/{record}'),
            'edit' => EditDeal::route('/{record}/edit'),
        ];
    }
}
