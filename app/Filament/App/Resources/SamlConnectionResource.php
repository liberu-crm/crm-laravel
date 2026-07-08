<?php

declare(strict_types=1);

namespace App\Filament\App\Resources;

use App\Enums\Role;
use App\Filament\App\Resources\SamlConnectionResource\Pages\CreateSamlConnection;
use App\Filament\App\Resources\SamlConnectionResource\Pages\EditSamlConnection;
use App\Filament\App\Resources\SamlConnectionResource\Pages\ListSamlConnections;
use App\Models\SamlConnection;
use App\Models\User;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

/**
 * A team admin manages their team's SAML (SP<->IdP) connection (G2 SAML slice 1).
 * Team-scoped (IsTenantModel); one connection per team. Hand the SP metadata at
 * /saml/{team}/metadata to the IdP.
 */
class SamlConnectionResource extends Resource
{
    protected static ?string $model = SamlConnection::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-identification';

    protected static string|\UnitEnum|null $navigationGroup = 'Team';

    protected static ?string $navigationLabel = 'SAML';

    protected static ?string $slug = 'saml';

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->hasRole([Role::SuperAdmin, Role::Admin]);
    }

    #[\Override]
    public static function canCreate(): bool
    {
        return static::getEloquentQuery()->count() === 0;
    }

    #[\Override]
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('idp_entity_id')->label('IdP entity ID')->required()->maxLength(255),
            TextInput::make('idp_sso_url')->label('IdP SSO URL')->url()->required()->maxLength(255),
            Textarea::make('idp_x509_cert')->label('IdP x509 certificate')->required()->rows(6),
            Toggle::make('enabled'),
        ]);
    }

    #[\Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('idp_entity_id')->label('IdP entity ID')->searchable(),
                IconColumn::make('enabled')->boolean(),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ]);
    }

    #[\Override]
    public static function getPages(): array
    {
        return [
            'index' => ListSamlConnections::route('/'),
            'create' => CreateSamlConnection::route('/create'),
            'edit' => EditSamlConnection::route('/{record}/edit'),
        ];
    }
}
