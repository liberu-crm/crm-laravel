<?php
namespace App\Filament\App\Resources;

use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\App\Resources\OpportunityResource\Pages\ListOpportunities;
use App\Filament\App\Resources\OpportunityResource\Pages\CreateOpportunity;
use App\Filament\App\Resources\OpportunityResource\Pages\EditOpportunity;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Opportunity;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\App\Resources\OpportunityResource\Pages;
use App\Filament\App\Resources\OpportunityResource\RelationManagers;

use Illuminate\Contracts\View\View;
use Filament\Tables\Filters\SelectFilter;

class OpportunityResource extends Resource
{
    protected static ?string $model = Opportunity::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-currency-dollar';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('deal_size')
                    ->numeric()
                    ->label('Deal Size'),
                TextInput::make('stage')
                    ->label('Stage'),
                DatePicker::make('closing_date')
                    ->label('Closing Date'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('deal_size')
                    ->searchable()
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

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
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
