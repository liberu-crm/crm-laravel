<?php

declare(strict_types=1);

namespace Tests\Feature\Sso;

use App\Filament\App\Resources\SamlConnectionResource;
use App\Filament\App\Resources\SamlConnectionResource\Pages\CreateSamlConnection;
use App\Filament\App\Resources\SamlConnectionResource\Pages\ListSamlConnections;
use App\Models\SamlConnection;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SamlConfigTest extends TestCase
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

    public function test_admin_creates_a_saml_connection(): void
    {
        Livewire::test(CreateSamlConnection::class)
            ->fillForm([
                'idp_entity_id' => 'https://idp.example.com/entity',
                'idp_sso_url' => 'https://idp.example.com/sso',
                'idp_x509_cert' => 'MIICertData',
                'enabled' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('saml_connections', [
            'team_id' => $this->team->id,
            'idp_entity_id' => 'https://idp.example.com/entity',
        ]);
    }

    public function test_connection_is_team_scoped(): void
    {
        $mine = SamlConnection::factory()->create(['team_id' => $this->team->id]);
        $other = SamlConnection::factory()->create(['team_id' => Team::factory()->create()->id]);

        Livewire::test(ListSamlConnections::class)
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$other]);
    }

    public function test_access_admin_only_and_one_per_team(): void
    {
        $this->assertTrue(SamlConnectionResource::canAccess());

        SamlConnection::factory()->create(['team_id' => $this->team->id]);
        $this->assertFalse(SamlConnectionResource::canCreate());

        $rep = User::factory()->withPersonalTeam()->create(['email_verified_at' => now()]);
        setPermissionsTeamId($rep->currentTeam->id);
        $rep->assignRole('sales_rep');
        $this->actingAs($rep);
        $this->assertFalse(SamlConnectionResource::canAccess());
    }

    public function test_sp_metadata_endpoint_returns_xml(): void
    {
        $team = Team::factory()->create();
        SamlConnection::factory()->create(['team_id' => $team->id]);

        $response = $this->get(route('saml.metadata', $team));

        $response->assertOk();
        $this->assertStringContainsString('EntityDescriptor', $response->getContent());
        $this->assertStringContainsString('/saml/'.$team->id.'/acs', $response->getContent());
        $this->assertStringContainsString('/saml/'.$team->id.'/metadata', $response->getContent());
    }

    public function test_metadata_404_without_a_connection(): void
    {
        $team = Team::factory()->create();

        $this->get(route('saml.metadata', $team))->assertNotFound();
    }
}
