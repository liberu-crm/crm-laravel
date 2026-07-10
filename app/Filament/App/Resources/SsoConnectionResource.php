<?php

declare(strict_types=1);

namespace App\Filament\App\Resources;

use App\Exceptions\SsoException;
use App\Filament\App\Resources\SsoConnectionResource\Pages\CreateSsoConnection;
use App\Filament\App\Resources\SsoConnectionResource\Pages\EditSsoConnection;
use App\Filament\App\Resources\SsoConnectionResource\Pages\ListSsoConnections;
use App\Filament\Concerns\EnforcesResourcePermissions;
use App\Models\SsoConnection;
use App\Services\Sso\OidcClient;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * A team admin manages their team's SSO (OIDC) connection (G2 slice 1). Team is
 * the tenant, so SsoConnection (IsTenantModel) auto-scopes and stamps team_id.
 * One connection per team. The client secret is encrypted at rest and never
 * rendered back into the form after it is saved.
 */
class SsoConnectionResource extends Resource
{
    use EnforcesResourcePermissions;

    protected static ?string $model = SsoConnection::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-finger-print';

    protected static string|\UnitEnum|null $navigationGroup = 'Team';

    protected static ?string $navigationLabel = 'Single sign-on';

    protected static ?string $slug = 'sso';

    #[\Override]
    public static function canCreate(): bool
    {
        // One connection per team.
        return static::getEloquentQuery()->count() === 0;
    }

    #[\Override]
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('provider')
                ->options([
                    'oidc' => 'OpenID Connect',
                    'okta' => 'Okta',
                    'azure' => 'Azure AD',
                    'auth0' => 'Auth0',
                ])
                ->default('oidc')
                ->required(),
            TextInput::make('client_id')->required()->maxLength(255),
            TextInput::make('client_secret')
                ->password()
                ->revealable()
                ->maxLength(1000)
                // Never render the stored secret back into the form...
                ->afterStateHydrated(fn (TextInput $component) => $component->state(''))
                // ...require it on create, and on edit save it only when a new
                // value is entered (blank = keep the stored secret).
                ->required(fn (string $operation): bool => $operation === 'create')
                ->dehydrated(fn (?string $state): bool => filled($state)),
            TextInput::make('issuer_url')->url()->required()->maxLength(255),
            Select::make('token_auth_method')
                ->label('Token endpoint auth')
                ->options([
                    'client_secret_post' => 'Client secret (POST body)',
                    'client_secret_basic' => 'Client secret (Basic auth)',
                ])
                ->default('client_secret_post')
                ->required(),
            Toggle::make('enabled'),
            Toggle::make('allow_jit')
                ->label('Auto-provision new users (JIT)')
                ->helperText('Create an account on first SSO login for users not yet on the team.'),
            TextInput::make('allowed_domain')
                ->label('Restrict JIT to email domain')
                ->placeholder('example.com')
                ->helperText('Optional. Only auto-provision emails at this domain.')
                ->maxLength(255),
            Toggle::make('require_sso')
                ->label('Require SSO for team members')
                ->helperText('Members must sign in via SSO; password login is blocked.'),
            KeyValue::make('role_mappings')
                ->label('IdP group → role')
                ->keyLabel('IdP group')
                ->valueLabel('Team role')
                ->helperText('Map an IdP group to a team role (admin / manager / sales_rep / free).'),
        ]);
    }

    #[\Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('provider')->badge(),
                TextColumn::make('issuer_url')->searchable(),
                IconColumn::make('enabled')->boolean(),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('testConnection')
                    ->label('Test')
                    ->icon('heroicon-o-signal')
                    // Validates the config by fetching the IdP discovery document,
                    // so an admin can verify a connection before enabling it.
                    ->action(function (SsoConnection $record): void {
                        try {
                            app(OidcClient::class)->discover($record);
                            Notification::make()
                                ->title('Connection OK')
                                ->body('Reached the identity provider and found its endpoints.')
                                ->success()
                                ->send();
                        } catch (SsoException $e) {
                            Notification::make()
                                ->title('Connection failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ]);
    }

    #[\Override]
    public static function getPages(): array
    {
        return [
            'index' => ListSsoConnections::route('/'),
            'create' => CreateSsoConnection::route('/create'),
            'edit' => EditSsoConnection::route('/{record}/edit'),
        ];
    }
}
