<?php

declare(strict_types=1);

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\MarketingCampaignResource\Pages\CreateMarketingCampaign;
use App\Filament\App\Resources\MarketingCampaignResource\Pages\EditMarketingCampaign;
use App\Filament\App\Resources\MarketingCampaignResource\Pages\ListMarketingCampaigns;
use App\Filament\App\Resources\MarketingCampaignResource\Pages\ViewMarketingCampaign;
use App\Filament\Concerns\EnforcesResourcePermissions;
use App\Models\MarketingCampaign;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MarketingCampaignResource extends Resource
{
    use EnforcesResourcePermissions;

    protected static ?string $model = MarketingCampaign::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-bottom-center';

    #[\Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Select::make('type')
                    ->required()
                    ->options([
                        'email' => 'Email',
                        'sms' => 'SMS',
                        'whatsapp' => 'WhatsApp',
                    ]),
                Select::make('status')
                    ->required()
                    ->options([
                        'draft' => 'Draft',
                        'scheduled' => 'Scheduled',
                        'sent' => 'Sent',
                        'cancelled' => 'Cancelled',
                    ]),
                TextInput::make('subject')
                    ->maxLength(255),
                Textarea::make('content')
                    ->required(),
                DateTimePicker::make('scheduled_at'),
            ]);
    }

    #[\Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name'),
                BadgeColumn::make('type'),
                BadgeColumn::make('status'),
                TextColumn::make('scheduled_at')
                    ->dateTime(),
                TextColumn::make('created_at')
                    ->dateTime(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
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
            'index' => ListMarketingCampaigns::route('/'),
            'view' => ViewMarketingCampaign::route('/{record}'),
            'create' => CreateMarketingCampaign::route('/create'),
            'edit' => EditMarketingCampaign::route('/{record}/edit'),
        ];
    }
}
