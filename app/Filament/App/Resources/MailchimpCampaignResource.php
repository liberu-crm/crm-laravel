<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\MailchimpCampaignResource\Pages;
use App\Services\MailChimpService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class MailchimpCampaignResource extends Resource
{
    protected static ?string $model = Model::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('subject_line')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('status')
                    ->options([
                        'save' => 'Save',
                        'paused' => 'Paused',
                        'schedule' => 'Schedule',
                        'sending' => 'Sending',
                        'sent' => 'Sent',
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('subject_line'),
                Tables\Columns\TextColumn::make('status'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('send')
                    ->action(fn (MailChimpService $service, Model $record) => $service->sendCampaign($record->id))
                    ->requiresConfirmation(),
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
            'index' => Pages\ListMailchimpCampaigns::route('/'),
            'create' => Pages\CreateMailchimpCampaign::route('/create'),
            'view' => Pages\ViewMailchimpCampaign::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->whereIn('id', app(MailChimpService::class)->getCampaigns()->pluck('id'));
    }
}