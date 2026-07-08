<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\App\Resources\SsoConnectionResource;
use App\Filament\App\Resources\SsoConnectionResource\Pages\CreateSsoConnection;
use App\Filament\App\Resources\SsoConnectionResource\Pages\EditSsoConnection;
use App\Filament\App\Resources\SsoConnectionResource\Pages\ListSsoConnections;
use App\Models\SsoConnection;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class SsoConnectionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private $team;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        $this->admin = User::factory()->withPersonalTeam()->create(['email_verified_at' => now()]);
        $this->team = $this->admin->currentTeam;
        setPermissionsTeamId($this->team->id);
        $this->admin->assignRole('admin');
        $this->actingAs($this->admin);
        Filament::setCurrentPanel(Filament::getPanel('app'));
        Filament::setTenant($this->team);
    }

    public function test_admin_creates_a_connection_with_encrypted_secret(): void
    {
        Livewire::test(CreateSsoConnection::class)
            ->fillForm([
                'provider' => 'oidc',
                'client_id' => 'client-abc',
                'client_secret' => 'super-secret-value',
                'issuer_url' => 'https://idp.example.com',
                'enabled' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $connection = SsoConnection::withoutGlobalScope('tenant')->where('team_id', $this->team->id)->first();
        $this->assertNotNull($connection);
        $this->assertSame('super-secret-value', $connection->client_secret);

        // Ciphertext at rest — the raw DB value is not the plaintext.
        $raw = DB::table('sso_connections')->where('id', $connection->id)->value('client_secret');
        $this->assertNotSame('super-secret-value', $raw);
    }

    public function test_connection_is_team_scoped(): void
    {
        $mine = SsoConnection::factory()->create(['team_id' => $this->team->id]);
        $other = SsoConnection::factory()->create(['team_id' => Team::factory()->create()->id]);

        Livewire::test(ListSsoConnections::class)
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$other]);
    }

    public function test_access_is_admin_only(): void
    {
        $this->assertTrue(SsoConnectionResource::canAccess());

        $rep = User::factory()->withPersonalTeam()->create(['email_verified_at' => now()]);
        setPermissionsTeamId($rep->currentTeam->id);
        $rep->assignRole('sales_rep');
        $this->actingAs($rep);

        $this->assertFalse(SsoConnectionResource::canAccess());
    }

    public function test_editing_without_a_new_secret_preserves_the_stored_one(): void
    {
        $connection = SsoConnection::factory()->create([
            'team_id' => $this->team->id,
            'client_secret' => 'original-secret',
        ]);

        Livewire::test(EditSsoConnection::class, ['record' => $connection->getKey()])
            ->fillForm(['client_id' => 'rotated-client', 'client_secret' => ''])
            ->call('save')
            ->assertHasNoFormErrors();

        $fresh = $connection->fresh();
        $this->assertSame('rotated-client', $fresh->client_id);
        $this->assertSame('original-secret', $fresh->client_secret);
    }

    public function test_only_one_connection_per_team(): void
    {
        SsoConnection::factory()->create(['team_id' => $this->team->id]);

        $this->assertFalse(SsoConnectionResource::canCreate());
    }
}
