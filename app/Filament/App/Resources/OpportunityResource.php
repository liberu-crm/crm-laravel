<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\OpportunityResource\Pages\CreateOpportunity;
use App\Filament\App\Resources\OpportunityResource\Pages\EditOpportunity;
use App\Filament\App\Resources\OpportunityResource\Pages\ListOpportunities;
use App\Models\Opportunity;
use App\Support\AccessContext;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;

class OpportunityResource extends Resource
{
    protected static ?string $model = Opportunity::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-currency-dollar';

    #[\Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Masked-role viewers get the read-only placeholder on edit; the
                // hidden real field isn't validated or dehydrated, so a save keeps
                // the stored value.
                TextInput::make('deal_size')
                    ->numeric()
                    ->label('Deal Size')
                    ->visible(fn (string $operation): bool => $operation === 'create' || ! AccessContext::shouldMaskFields()),
                Placeholder::make('deal_size_masked')
                    ->label('Deal Size')
                    ->content('[hidden]')
                    ->visible(fn (string $operation): bool => $operation !== 'create' && AccessContext::shouldMaskFields()),
                TextInput::make('stage')
                    ->label('Stage'),
                DatePicker::make('closing_date')
                    ->label('Closing Date'),
            ]);
    }

    #[\Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('deal_size')
                    ->formatStateUsing(fn (?string $state): string => AccessContext::shouldMaskFields()
                        ? '[hidden]'
                        : '$'.number_format((float) $state, 2))
                    // Searchable would let a masked viewer probe the hidden value.
                    ->searchable(! AccessContext::shouldMaskFields())
                    ->sortable(),
                TextColumn::make('stage')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('closing_date')
                    ->searchable()
                    ->sortable(),
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
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    #[\Override]
    public static function getPages(): array
    {
        return [
            'index' => ListOpportunities::route('/'),
            'create' => CreateOpportunity::route('/create'),
            'edit' => EditOpportunity::route('/{record}/edit'),
        ];
    }

    public static function getPipelineView(): View
    {
        return view('livewire.opportunity-pipeline');
    }

    public static function getPipelineTable(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('stage')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('deal_size')
                    ->money('usd')
                    ->sortable(),
                TextColumn::make('closing_date')
                    ->date()
                    ->sortable(),
            ])
            ->defaultSort('stage', 'asc')
            ->groupedBy('stage')
            ->filters([
                SelectFilter::make('stage')
                    ->options(Opportunity::distinct()->pluck('stage', 'stage')->toArray()),
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
}
