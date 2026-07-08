<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\App\Resources\TerritoryResource;
use App\Filament\App\Resources\TerritoryResource\Pages\CreateTerritory;
use App\Filament\App\Resources\TerritoryResource\Pages\ListTerritories;
use App\Models\Team;
use App\Models\Territory;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TerritoryResourceTest extends TestCase
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

    public function test_admin_creates_a_territory_scoped_to_team(): void
    {
        Livewire::test(CreateTerritory::class)
            ->fillForm(['name' => 'West Region'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('territories', [
            'name' => 'West Region',
            'team_id' => $this->team->id,
        ]);
    }

    public function test_creating_with_members_writes_the_pivot(): void
    {
        $member = User::factory()->create(['email_verified_at' => now()]);
        $this->team->users()->attach($member);

        Livewire::test(CreateTerritory::class)
            ->fillForm(['name' => 'East Region', 'users' => [$member->id]])
            ->call('create')
            ->assertHasNoFormErrors();

        $territory = Territory::withoutGlobalScope('tenant')->where('name', 'East Region')->first();
        $this->assertNotNull($territory);
        $this->assertTrue($territory->users()->where('users.id', $member->id)->exists());
    }

    public function test_list_is_team_scoped(): void
    {
        $mine = Territory::factory()->create(['team_id' => $this->team->id]);
        $other = Territory::factory()->create(['team_id' => Team::factory()->create()->id]);

        Livewire::test(ListTerritories::class)
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$other]);
    }

    public function test_manager_can_access_sales_rep_cannot(): void
    {
        $this->assertTrue(TerritoryResource::canAccess());

        $rep = User::factory()->withPersonalTeam()->create(['email_verified_at' => now()]);
        setPermissionsTeamId($rep->currentTeam->id);
        $rep->assignRole('sales_rep');
        $this->actingAs($rep);

        $this->assertFalse(TerritoryResource::canAccess());
    }
}
