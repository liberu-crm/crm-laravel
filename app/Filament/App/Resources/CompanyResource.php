<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\CompanyResource\Pages\CreateCompany;
use App\Filament\App\Resources\CompanyResource\Pages\EditCompany;
use App\Filament\App\Resources\CompanyResource\Pages\ListCompanies;
use App\Models\Company;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Name'),
                TextInput::make('address')
                    ->label('Address'),
                TextInput::make('city')
                    ->label('City'),
                TextInput::make('state')
                    ->label('State'),
                TextInput::make('zip')
                    ->label('ZIP')
                    ->numeric(),
                TextInput::make('phone_number')
                    ->label('Phone Number')
                    ->numeric(),
                TextInput::make('website')
                    ->label('Website'),
                TextInput::make('industry')
                    ->label('Industry'),
                Textarea::make('description')
                    ->label('Description'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('address')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('city')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('state')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('zip')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('website'),
                TextColumn::make('industry')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('description'),
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
            'index' => ListCompanies::route('/'),
            'create' => CreateCompany::route('/create'),
            'edit' => EditCompany::route('/{record}/edit'),
        ];
    }
}
