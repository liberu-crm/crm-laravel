<?php

declare(strict_types=1);

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\CompanyResource\Pages\CreateCompany;
use App\Filament\App\Resources\CompanyResource\Pages\EditCompany;
use App\Filament\App\Resources\CompanyResource\Pages\ListCompanies;
use App\Filament\App\Resources\CompanyResource\Pages\ViewCompany;
use App\Filament\Concerns\EnforcesResourcePermissions;
use App\Filament\Exports\CompanyExporter;
use App\Models\Company;
use App\Support\AccessContext;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CompanyResource extends Resource
{
    use EnforcesResourcePermissions;

    protected static ?string $model = Company::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    #[\Override]
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
                // On edit, a masked-role viewer gets the read-only placeholder below
                // instead of the real input. The hidden real field is neither
                // validated nor dehydrated, so a save preserves the stored value.
                TextInput::make('phone_number')
                    ->label('Phone Number')
                    ->numeric()
                    ->visible(fn (string $operation): bool => $operation === 'create' || ! AccessContext::shouldMaskFields()),
                Placeholder::make('phone_masked')
                    ->label('Phone Number')
                    ->content('[hidden]')
                    ->visible(fn (string $operation): bool => $operation !== 'create' && AccessContext::shouldMaskFields()),
                TextInput::make('website')
                    ->label('Website'),
                TextInput::make('industry')
                    ->label('Industry'),
                Textarea::make('description')
                    ->label('Description'),
            ]);
    }

    #[\Override]
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
                    ->formatStateUsing(fn (?string $state, Company $record): mixed => $record->maskFor('phone_number', $state))
                    // Not searchable for masked-role viewers, else search would
                    // query the real column and confirm a value the UI masks.
                    ->searchable(! AccessContext::shouldMaskFields())
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
            ->headerActions([
                // Gated off for masked (`free`) roles: a CSV would otherwise
                // bypass the phone_number/annual_revenue masking applied in the UI.
                ExportAction::make()
                    ->exporter(CompanyExporter::class)
                    ->visible(fn (): bool => ! AccessContext::shouldMaskFields()),
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
            'index' => ListCompanies::route('/'),
            'create' => CreateCompany::route('/create'),
            'view' => ViewCompany::route('/{record}'),
            'edit' => EditCompany::route('/{record}/edit'),
        ];
    }
}
