<?php

declare(strict_types=1);

namespace Tests\Feature\Sso;

use App\Filament\App\Resources\SamlConnectionResource\Pages\ListSamlConnections;
use App\Models\SamlConnection;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SamlValidateConnectionTest extends TestCase
{
    use RefreshDatabase;

    private $team;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        $admin = User::factory()->withPersonalTeam()->create(['email_verified_at' => now()]);
        $this->team = $admin->currentTeam;
        setPermissionsTeamId($this->team->id);
        $admin->assignRole('admin');
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('app'));
        Filament::setTenant($this->team);
    }

    private function validCertPem(): string
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        $csr = openssl_csr_new(['commonName' => 'idp.example.com'], $key);
        $x509 = openssl_csr_sign($csr, null, $key, 365);
        openssl_x509_export($x509, $pem);

        return (string) $pem;
    }

    private function connection(array $overrides): SamlConnection
    {
        return SamlConnection::factory()->create(array_merge([
            'team_id' => $this->team->id,
            'idp_entity_id' => 'https://idp.example.com/entity',
            'idp_sso_url' => 'https://idp.example.com/sso',
        ], $overrides));
    }

    public function test_valid_connection_reports_success(): void
    {
        $connection = $this->connection(['idp_x509_cert' => $this->validCertPem()]);

        Livewire::test(ListSamlConnections::class)
            ->callTableAction('validate', $connection)
            ->assertNotified('SAML configuration looks valid');
    }

    public function test_bad_cert_reports_failure(): void
    {
        $connection = $this->connection(['idp_x509_cert' => 'not-a-certificate']);

        Livewire::test(ListSamlConnections::class)
            ->callTableAction('validate', $connection)
            ->assertNotified('SAML configuration has problems');
    }

    public function test_bad_sso_url_reports_failure(): void
    {
        $connection = $this->connection([
            'idp_x509_cert' => $this->validCertPem(),
            'idp_sso_url' => 'http://insecure.example.com/sso',
        ]);

        Livewire::test(ListSamlConnections::class)
            ->callTableAction('validate', $connection)
            ->assertNotified('SAML configuration has problems');
    }
}
