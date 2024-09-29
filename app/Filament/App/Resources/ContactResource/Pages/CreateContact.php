<?php

namespace App\Filament\App\Resources\ContactResource\Pages;

use App\Filament\App\Resources\ContactResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Forms;

class CreateContact extends CreateRecord
{
    protected static string $resource = ContactResource::class;

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Card::make()
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('email')
                        ->email()
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('phone')
                        ->tel()
                        ->maxLength(20)
                        ->helperText('Enter the phone number in international format'),
                ])
                ->columns(2),
            Forms\Components\Card::make()
                ->schema([
                    Forms\Components\Textarea::make('notes')
                        ->maxLength(65535)
                        ->helperText('Add any additional information about the contact'),
                    Forms\Components\Select::make('status')
                        ->options([
                            'active' => 'Active',
                            'inactive' => 'Inactive',
                        ])
                        ->required(),
                ]),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
