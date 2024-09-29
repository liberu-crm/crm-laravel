<?php

/**
 * ListContacts class.
 *
 * Provides the implementation for listing contacts in the Filament admin panel. This page
 * allows users to view, search, filter, and manage contacts with improved UI/UX and performance.
 */

namespace App\Filament\App\Resources\ContactResource\Pages;

use App\Filament\App\Resources\ContactResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Filament\Forms\Components\TextInput;

class ListContacts extends ListRecords
{
    protected static string $resource = ContactResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone_number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('company_size')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('industry')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->globalSearchable(true)
            ->globalSearchableAttributes([
                'name',
                'last_name',
                'email',
                'phone_number',
                'company_size',
                'industry',
            ])
            ->searchable(true)
            ->searchableAttributes([
                'name',
                'last_name',
                'email',
                'phone_number',
                'company_size',
                'industry',
            ])
            ->searchDebounce(300);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getFooter(): View
    {
        return view('components.user-feedback', ['action' => route('contact.feedback')]);
    }

    public function getRecords()
    {
        $cacheKey = 'contacts_list_' . $this->getTableRecordsPerPageSelectOptions()[0];
        $cacheDuration = now()->addMinutes(5);

        return Cache::remember($cacheKey, $cacheDuration, function () {
            return parent::getRecords()->search(request('search'));
        });
    }

    protected function getTableFilters(): array
    {
        return [
            Tables\Filters\Filter::make('search')
                ->form([
                    TextInput::make('search')
                        ->label('Search')
                        ->placeholder('Search contacts...')
                        ->autocomplete()
                        ->debounce(300)
                ])
                ->query(function ($query, array $data) {
                    if (isset($data['search'])) {
                        $query->search($data['search']);
                    }
                }),
        ];
    }
}