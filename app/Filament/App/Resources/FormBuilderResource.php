<?php

namespace App\Filament\App\Resources;

use App\Filament\Forms\FormBuilderResource\Pages;
use App\Models\FormBuilder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\App\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;

class FormBuilderResource extends Resource
{
    protected static ?string $model = FormBuilder::class;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->maxLength(65535),
                Forms\Components\Repeater::make('fields')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->options([
                                'text' => 'Text',
                                'textarea' => 'Textarea',
                                'number' => 'Number',
                                'email' => 'Email',
                                'select' => 'Select',
                                'checkbox' => 'Checkbox',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('label')
                            ->required(),
                        Forms\Components\TextInput::make('name')
                            ->required(),
                        Forms\Components\Toggle::make('required'),
                    ])
                    ->columns(2)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('description'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListFormBuilders::route('/'),
            'create' => Pages\CreateFormBuilder::route('/create'),
            'edit' => Pages\EditFormBuilder::route('/{record}/edit'),
        ];
    }
}