<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\MarketingCampaignResource\Pages;
use App\Models\MarketingCampaign;
use Filament\Forms;
use Filament\App\Resources\Form;
use Filament\App\Resources\Resource;
use Filament\App\Resources\Table;
use Filament\Tables;

class MarketingCampaignResource extends Resource
{
    protected static ?string $model = MarketingCampaign::class;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                    ->required()
                    ->options([
                        'email' => 'Email',
                        'sms' => 'SMS',
                        'whatsapp' => 'WhatsApp',
                    ]),
                Forms\Components\Select::make('status')
                    ->required()
                    ->options([
                        'draft' => 'Draft',
                        'scheduled' => 'Scheduled',
                        'sent' => 'Sent',
                        'cancelled' => 'Cancelled',
                    ]),
                Forms\Components\TextInput::make('subject')
                    ->maxLength(255),
                Forms\Components\Textarea::make('content')
                    ->required(),
                Forms\Components\DateTimePicker::make('scheduled_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\BadgeColumn::make('type'),
                Tables\Columns\BadgeColumn::make('status'),
                Tables\Columns\TextColumn::make('scheduled_at')
                    ->dateTime(),
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
            'index' => Pages\ListMarketingCampaigns::route('/'),
            'create' => Pages\CreateMarketingCampaign::route('/create'),
            'edit' => Pages\EditMarketingCampaign::route('/{record}/edit'),
        ];
    }
}