<?php

declare(strict_types=1);

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\SamlConnectionResource\Pages\CreateSamlConnection;
use App\Filament\App\Resources\SamlConnectionResource\Pages\EditSamlConnection;
use App\Filament\App\Resources\SamlConnectionResource\Pages\ListSamlConnections;
use App\Filament\Concerns\EnforcesResourcePermissions;
use App\Models\SamlConnection;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * A team admin manages their team's SAML (SP<->IdP) connection (G2 SAML slice 1).
 * Team-scoped (IsTenantModel); one connection per team. Hand the SP metadata at
 * /saml/{team}/metadata to the IdP.
 */
class SamlConnectionResource extends Resource
{
    use EnforcesResourcePermissions;

    protected static ?string $model = SamlConnection::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-identification';

    protected static string|\UnitEnum|null $navigationGroup = 'Team';

    protected static ?string $navigationLabel = 'SAML';

    protected static ?string $slug = 'saml';

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
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('validate')
                    ->label('Validate')
                    ->icon('heroicon-o-check-badge')
                    // Local well-formedness check (no network) so an admin can catch
                    // a bad cert / URL before enabling the connection.
                    ->action(function (SamlConnection $record): void {
                        $problems = self::validationProblems($record);

                        if ($problems === []) {
                            Notification::make()->title('SAML configuration looks valid')->success()->send();

                            return;
                        }

                        Notification::make()
                            ->title('SAML configuration has problems')
                            ->body(implode(' ', $problems))
                            ->danger()
                            ->send();
                    }),
            ]);
    }

    /**
     * @return array<int, string>
     */
    private static function validationProblems(SamlConnection $connection): array
    {
        $problems = [];

        $cert = trim((string) $connection->getAttribute('idp_x509_cert'));
        $pem = str_contains($cert, 'BEGIN CERTIFICATE')
            ? $cert
            : "-----BEGIN CERTIFICATE-----\n".chunk_split($cert, 64, "\n").'-----END CERTIFICATE-----';
        if (@openssl_x509_parse($pem) === false) {
            $problems[] = 'The x509 certificate is not valid.';
        }

        $url = (string) $connection->getAttribute('idp_sso_url');
        if (filter_var($url, FILTER_VALIDATE_URL) === false || ! str_starts_with($url, 'https://')) {
            $problems[] = 'The SSO URL must be a valid https URL.';
        }

        if (blank($connection->getAttribute('idp_entity_id'))) {
            $problems[] = 'The IdP entity ID is required.';
        }

        return $problems;
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
