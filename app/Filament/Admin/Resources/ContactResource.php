<?php

/**
 * ContactResource class.
 *
 * Represents the Filament resource for managing contacts in the admin panel. This class
 * handles the definition of forms, tables, and actions related to contacts.
 */

/**
 * Defines the Filament resource for Contacts.
 *
 * This resource configures the forms, tables, and actions for managing Contact entities
 * within the Filament admin panel. It leverages the Filament package to provide a
 * rich and interactive user interface.
 */
namespace App\Filament\Admin\Resources;

use App\Models\Contact;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Resources\Columns\TextColumn;
use Filament\Resources\Actions;
use Filament\Resources\Widgets\StatsOverviewWidget;
use Filament\Resources\Widgets\Widget;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Admin\Resources\ContactResource\Pages;
use App\Filament\Admin\Resources\ContactResource\RelationManagers;

class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;


    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Name'),
                TextInput::make('last_name')
                    ->label('Last Name'),
                TextInput::make('email')
                    ->label('Email'),
                TextInput::make('phone_number')
                    ->numeric()
                    ->label('Phone Number'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('last_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone_number')
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
            'index' => Pages\ListContacts::route('/'),
            'create' => Pages\CreateContact::route('/create'),
            'edit' => Pages\EditContact::route('/{record}/edit'),
        ];
    }
}
