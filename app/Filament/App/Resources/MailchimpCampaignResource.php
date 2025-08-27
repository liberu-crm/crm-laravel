<?php

namespace App\Filament\App\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\App\Resources\MailchimpCampaignResource\Pages\ListMailchimpCampaigns;
use App\Filament\App\Resources\MailchimpCampaignResource\Pages\CreateMailchimpCampaign;
use App\Filament\App\Resources\MailchimpCampaignResource\Pages\ViewMailchimpCampaign;
use App\Filament\App\Resources\MailchimpCampaignResource\Pages\ViewABTestResults;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\App\Resources\MailchimpCampaignResource\Pages;
use App\Services\MailChimpService;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class MailchimpCampaignResource extends Resource
{
    protected static ?string $model = Model::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Select::make('type')
                    ->options([
                        'regular' => 'Regular',
                        'abtest' => 'A/B Test',
                    ])
                    ->required()
                    ->reactive(),
                TextInput::make('subject_line')
                    ->required()
                    ->maxLength(255)
                    ->visible(fn (callable $get) => $get('type') === 'regular'),
                TextInput::make('subject_line_a')
                    ->required()
                    ->maxLength(255)
                    ->visible(fn (callable $get) => $get('type') === 'abtest'),
                TextInput::make('subject_line_b')
                    ->required()
                    ->maxLength(255)
                    ->visible(fn (callable $get) => $get('type') === 'abtest'),
                Select::make('status')
                    ->options([
                        'save' => 'Save',
                        'paused' => 'Paused',
                        'schedule' => 'Schedule',
                        'sending' => 'Sending',
                        'sent' => 'Sent',
                    ])
                    ->required(),
                Select::make('winner_criteria')
                    ->options([
                        'opens' => 'Opens',
                        'clicks' => 'Clicks',
                        'manual' => 'Manual',
                    ])
                    ->required()
                    ->visible(fn (callable $get) => $get('type') === 'abtest'),
                TextInput::make('test_size')
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
                TextColumn::make('name'),
                TextColumn::make('subject_line'),
                TextColumn::make('type'),
                TextColumn::make('status'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('send')
                    ->action(fn (MailChimpService $service, Model $record) => $service->sendCampaign($record->id))
                    ->requiresConfirmation(),
                Action::make('view_ab_results')
                    ->action(fn (MailChimpService $service, Model $record) => redirect()->route('filament.app.resources.mailchimp-campaigns.ab-test-results', ['record' => $record->id]))
                    ->visible(fn (Model $record) => $record->type === 'abtest' && $record->status === 'sent'),
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
            'index' => ListMailchimpCampaigns::route('/'),
            'create' => CreateMailchimpCampaign::route('/create'),
            'view' => ViewMailchimpCampaign::route('/{record}'),
            'ab-test-results' => ViewABTestResults::route('/{record}/ab-test-results'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereIn('id', app(MailChimpService::class)->getCampaigns()->pluck('id'));
    }
}