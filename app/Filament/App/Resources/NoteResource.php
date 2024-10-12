<?php

namespace App\Filament\App\Resources;

use Filament\Forms;
use App\Models\Note;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\App\Resources\Resource;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\BelongsToSelect;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\App\Resources\NoteResource\Pages;
use App\Filament\App\Resources\NoteResource\RelationManagers;

class NoteResource extends Resource
{
    protected static ?string $model = Note::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Textarea::make('content')->label('Content'),
                BelongsToSelect::make('contact_id')
                    ->relationship('contact', 'name')
                    ->label('Contact'),
                BelongsToSelect::make('company_id')
                    ->relationship('company', 'name')
                    ->label('Company'),
                BelongsToSelect::make('opportunity_id')
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
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListNotes::route('/'),
            'create' => Pages\CreateNote::route('/create'),
            'edit' => Pages\EditNote::route('/{record}/edit'),
        ];
    }
}
