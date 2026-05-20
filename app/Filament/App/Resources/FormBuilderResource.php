<?php

namespace App\Filament\App\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use App\Filament\App\Resources\FormBuilderResource\Pages\ListFormBuilders;
use App\Filament\App\Resources\FormBuilderResource\Pages\CreateFormBuilder;
use App\Filament\App\Resources\FormBuilderResource\Pages\EditFormBuilder;
use App\Filament\App\Resources\FormBuilderResource\Pages;
use App\Models\FormBuilder;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;

class FormBuilderResource extends Resource
{
    protected static ?string $model = FormBuilder::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chat-bubble-bottom-center';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->maxLength(65535),
                Repeater::make('fields')
                    ->schema([
                        Select::make('type')
                            ->options([
                                'text' => 'Text',
                                'textarea' => 'Textarea',
                                'number' => 'Number',
                                'email' => 'Email',
                                'select' => 'Select',
                                'checkbox' => 'Checkbox',
                            ])
                            ->required(),
                        TextInput::make('label')
                            ->required(),
                        TextInput::make('name')
                            ->required(),
                        Toggle::make('required'),
                    ])
                    ->columns(2)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('description'),
                TextColumn::make('created_at')
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
            'index' => ListFormBuilders::route('/'),
            'create' => CreateFormBuilder::route('/create'),
            'edit' => EditFormBuilder::route('/{record}/edit'),
        ];
    }
}