<?php

declare(strict_types=1);

namespace App\Filament\App\Resources;

use App\Enums\Role;
use App\Filament\App\Resources\WebhookDeliveryResource\Pages\ListWebhookDeliveries;
use App\Models\User;
use App\Models\WebhookDelivery;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

/**
 * Team-scoped, read-only delivery log for outgoing webhooks. WebhookDelivery is
 * IsTenantModel, so on the team-scoped app panel this shows only the current
 * team's send attempts — letting a team's own admins see webhook success and
 * failure history recorded by WebhookService::send.
 */
class WebhookDeliveryResource extends Resource
{
    protected static ?string $model = WebhookDelivery::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-up-on-square-stack';

    protected static string|\UnitEnum|null $navigationGroup = 'Team';

    protected static ?string $navigationLabel = 'Webhook deliveries';

    protected static ?string $slug = 'webhook-deliveries';

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->hasRole([Role::SuperAdmin, Role::Admin]);
    }

    #[\Override]
    public static function canCreate(): bool
    {
        return false;
    }

    #[\Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->dateTime()->sortable(),
                TextColumn::make('webhook.name')->label('Webhook')->searchable(),
                TextColumn::make('event')->searchable(),
                IconColumn::make('success')->boolean(),
                TextColumn::make('status_code')->label('Status'),
                TextColumn::make('error')->wrap(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    #[\Override]
    public static function getPages(): array
    {
        return [
            'index' => ListWebhookDeliveries::route('/'),
        ];
    }
}
