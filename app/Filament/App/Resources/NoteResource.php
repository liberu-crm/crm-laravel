<?php

declare(strict_types=1);

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\NoteResource\Pages\CreateNote;
use App\Filament\App\Resources\NoteResource\Pages\EditNote;
use App\Filament\App\Resources\NoteResource\Pages\ListNotes;
use App\Filament\App\Resources\NoteResource\Pages\ViewNote;
use App\Filament\Concerns\EnforcesResourcePermissions;
use App\Models\Note;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class NoteResource extends Resource
{
    use EnforcesResourcePermissions;

    protected static ?string $model = Note::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    #[\Override]
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
                    ->relationship('opportunity', 'opportunity_id')
                    ->label('Opportunity'),
            ]);
    }

    #[\Override]
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
            'index' => ListNotes::route('/'),
            'view' => ViewNote::route('/{record}'),
            'create' => CreateNote::route('/create'),
            'edit' => EditNote::route('/{record}/edit'),
        ];
    }
}
