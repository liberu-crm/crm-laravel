<?php

namespace App\Filament\App\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use App\Filament\App\Resources\LandingPageResource\Pages\ListLandingPages;
use App\Filament\App\Resources\LandingPageResource\Pages\CreateLandingPage;
use App\Filament\App\Resources\LandingPageResource\Pages\EditLandingPage;
use App\Filament\App\Resources\LandingPageResource\Pages;
use App\Models\LandingPage;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;

class LandingPageResource extends Resource
{
    protected static ?string $model = LandingPage::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                RichEditor::make('content')
                    ->required(),
                Select::make('campaign_id')
                    ->relationship('campaign', 'name')
                    ->required(),
                Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                    ])
                    ->required(),
                DateTimePicker::make('published_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title'),
                TextColumn::make('campaign.name'),
                BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'draft',
                        'success' => 'published',
                    ]),
                TextColumn::make('published_at')
                    ->dateTime(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
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
            'index' => ListLandingPages::route('/'),
            'create' => CreateLandingPage::route('/create'),
            'edit' => EditLandingPage::route('/{record}/edit'),
        ];
    }
}
