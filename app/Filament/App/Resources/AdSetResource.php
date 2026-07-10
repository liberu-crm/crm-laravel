<?php

declare(strict_types=1);

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\AdSetResource\Pages\CreateAdSet;
use App\Filament\App\Resources\AdSetResource\Pages\EditAdSet;
use App\Filament\App\Resources\AdSetResource\Pages\ListAdSets;
use App\Filament\Exports\AdSetExporter;
use App\Models\AdSet;
use App\Support\AccessContext;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AdSetResource extends Resource
{
    protected static ?string $model = AdSet::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static string|\UnitEnum|null $navigationGroup = 'Advertising';

    protected static ?string $navigationLabel = 'Ad Sets';

    #[\Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('advertising_account_id')
                    ->relationship('advertisingAccount', 'name')
                    ->required()
                    ->label('Advertising Account'),
                Select::make('campaign_id')
                    ->relationship('campaign', 'name')
                    ->required()
                    ->label('Campaign'),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('external_id')
                    ->maxLength(255)
                    ->label('External ID'),
                Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'paused' => 'Paused',
                        'archived' => 'Archived',
                        'deleted' => 'Deleted',
                    ])
                    ->default('active')
                    ->required(),
                // Masked-role viewers get the read-only placeholder on edit; the
                // hidden real field isn't validated or dehydrated, so a save keeps
                // the stored value.
                TextInput::make('budget')
                    ->numeric()
                    ->prefix('$')
                    ->visible(fn (string $operation): bool => $operation === 'create' || ! AccessContext::shouldMaskFields()),
                Placeholder::make('budget_masked')
                    ->label('Budget')
                    ->content('[hidden]')
                    ->visible(fn (string $operation): bool => $operation !== 'create' && AccessContext::shouldMaskFields()),
                Select::make('budget_type')
                    ->options([
                        'daily' => 'Daily',
                        'lifetime' => 'Lifetime',
                    ])
                    ->default('daily'),
            ]);
    }

    #[\Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('advertisingAccount.name')->label('Account')->searchable(),
                TextColumn::make('campaign.name')->label('Campaign')->searchable(),
                BadgeColumn::make('status')->colors([
                    'success' => 'active',
                    'warning' => 'paused',
                    'secondary' => 'archived',
                    'danger' => 'deleted',
                ]),
                TextColumn::make('budget')
                    ->formatStateUsing(fn (?string $state): string => AccessContext::shouldMaskFields()
                        ? '[hidden]'
                        : '$'.number_format((float) $state, 2)),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'paused' => 'Paused',
                        'archived' => 'Archived',
                        'deleted' => 'Deleted',
                    ]),
            ])
            ->headerActions([
                // Hidden for masked (`free`) roles so a CSV can't bypass budget masking.
                ExportAction::make()
                    ->exporter(AdSetExporter::class)
                    ->visible(fn (): bool => ! AccessContext::shouldMaskFields()),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    #[\Override]
    public static function getRelations(): array
    {
        return [];
    }

    #[\Override]
    public static function getPages(): array
    {
        return [
            'index' => ListAdSets::route('/'),
            'create' => CreateAdSet::route('/create'),
            'edit' => EditAdSet::route('/{record}/edit'),
        ];
    }
}
