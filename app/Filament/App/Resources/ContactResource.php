<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\ContactResource\Pages;
use App\Models\Contact;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\App\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;

class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('last_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone_number')
                    ->tel()
                    ->maxLength(255),
                Forms\Components\Select::make('status')

                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ]),
                Forms\Components\TextInput::make('source')
                    ->maxLength(255),
                Forms\Components\TextInput::make('industry')
                    ->maxLength(255),
                Forms\Components\TextInput::make('company_size')
                    ->maxLength(255),
                Forms\Components\TextInput::make('annual_revenue')
                    ->numeric(),
                Forms\Components\Select::make('lifecycle_stage')
                    ->options([
                        'subscriber' => 'Subscriber',
                        'lead' => 'Lead',
                        'marketing_qualified_lead' => 'Marketing Qualified Lead',
                        'sales_qualified_lead' => 'Sales Qualified Lead',
                        'opportunity' => 'Opportunity',
                    ]),
                Forms\Components\BelongsToSelect::make('company_id')
                    ->relationship('company', 'name'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('last_name'),
                Tables\Columns\TextColumn::make('email'),
                Tables\Columns\TextColumn::make('phone_number'),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\TextColumn::make('lifecycle_stage'),
                Tables\Columns\TextColumn::make('company.name')->label('Company'),
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
            'index' => Pages\ListContacts::route('/'),
            'create' => Pages\CreateContact::route('/create'),
            'edit' => Pages\EditContact::route('/{record}/edit'),
        ];
    }
}