<?php

declare(strict_types=1);

namespace App\Filament\App\Resources;

use App\Enums\Role;
use App\Filament\App\Resources\PortalBrandingResource\Pages\EditPortalBranding;
use App\Filament\App\Resources\PortalBrandingResource\Pages\ListPortalBranding;
use App\Models\Team;
use App\Models\User;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * A team admin edits their own team's customer-portal branding (name + logo).
 * Model is Team (which has no `team` ownership relationship), so it opts out of
 * the app panel's automatic tenancy and scopes to the current tenant manually.
 */
class PortalBrandingResource extends Resource
{
    protected static ?string $model = Team::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-swatch';

    protected static string|\UnitEnum|null $navigationGroup = 'Team';

    protected static ?string $navigationLabel = 'Portal branding';

    protected static ?string $slug = 'portal-branding';

    protected static bool $isScopedToTenant = false;

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
    public static function getEloquentQuery(): Builder
    {
        $tenant = Filament::getTenant();

        return parent::getEloquentQuery()->whereKey($tenant instanceof Team ? $tenant->getKey() : 0);
    }

    #[\Override]
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('portal_brand_name')
                ->label('Portal brand name')
                ->placeholder(config('portal.brand_name'))
                ->maxLength(255),
            TextInput::make('portal_logo_url')
                ->label('Portal logo URL')
                ->url()
                ->maxLength(2048),
        ]);
    }

    #[\Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Team'),
                TextColumn::make('portal_brand_name')->label('Brand')->placeholder('(default)'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    #[\Override]
    public static function getPages(): array
    {
        return [
            'index' => ListPortalBranding::route('/'),
            'edit' => EditPortalBranding::route('/{record}/edit'),
        ];
    }
}
