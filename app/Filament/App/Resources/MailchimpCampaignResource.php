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
                Forms\Components\Select::make('type')
                    ->options([
                        'regular' => 'Regular',
                        'abtest' => 'A/B Test',
                    ])
                    ->required()
                    ->reactive(),
                Forms\Components\TextInput::make('subject_line')
                    ->required()
                    ->maxLength(255)
                    ->visible(fn (callable $get) => $get('type') === 'regular'),
                Forms\Components\TextInput::make('subject_line_a')
                    ->required()
                    ->maxLength(255)
                    ->visible(fn (callable $get) => $get('type') === 'abtest'),
                Forms\Components\TextInput::make('subject_line_b')
                    ->required()
                    ->maxLength(255)
                    ->visible(fn (callable $get) => $get('type') === 'abtest'),
                Forms\Components\Select::make('status')
                    ->options([
                        'save' => 'Save',
                        'paused' => 'Paused',
                        'schedule' => 'Schedule',
                        'sending' => 'Sending',
                        'sent' => 'Sent',
                    ])
                    ->required(),
                Forms\Components\Select::make('winner_criteria')
                    ->options([
                        'opens' => 'Opens',
                        'clicks' => 'Clicks',
                        'manual' => 'Manual',
                    ])
                    ->required()
                    ->visible(fn (callable $get) => $get('type') === 'abtest'),
                Forms\Components\TextInput::make('test_size')
                    ->numeric()
                    ->min(1)
                    ->max(100)
                    ->default(50)
                    ->suffix('%')
                    ->required()
                    ->visible(fn (callable $get) => $get('type') === 'abtest'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('subject_line'),
                Tables\Columns\TextColumn::make('type'),
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
                Tables\Actions\Action::make('view_ab_results')
                    ->action(fn (MailChimpService $service, Model $record) => redirect()->route('filament.app.resources.mailchimp-campaigns.ab-test-results', ['record' => $record->id]))
                    ->visible(fn (Model $record) => $record->type === 'abtest' && $record->status === 'sent'),
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
            'ab-test-results' => Pages\ViewABTestResults::route('/{record}/ab-test-results'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->whereIn('id', app(MailChimpService::class)->getCampaigns()->pluck('id'));
    }
}