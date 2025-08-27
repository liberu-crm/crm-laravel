<?php

namespace App\Filament\App\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\App\Resources\NoteResource\Pages\ListNotes;
use App\Filament\App\Resources\NoteResource\Pages\CreateNote;
use App\Filament\App\Resources\NoteResource\Pages\EditNote;
use Filament\Forms;
use App\Models\Note;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\App\Resources\NoteResource\Pages;
use App\Filament\App\Resources\NoteResource\RelationManagers;

class NoteResource extends Resource
{
    protected static ?string $model = Note::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('content')->label('Content'),
                Select::make('contact_id')
                    ->relationship('contact', 'name')
                    ->label('Contact'),
                Select::make('company_id')
                    ->relationship('company', 'name')
                    ->label('Company'),
                Select::make('opportunity_id')
                    ->relationship('opportunity', 'name')
                    ->label('Opportunity'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('content')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('contact_id')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('company_id')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('opportunity_id')
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
            'index' => ListNotes::route('/'),
            'create' => CreateNote::route('/create'),
            'edit' => EditNote::route('/{record}/edit'),
        ];
    }
}
