<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\App\Resources\TerritoryResource\Pages\ViewTerritory;
use App\Models\Territory;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TerritoryViewTest extends TestCase
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

    public function test_view_page_mounts_and_shows_territory(): void
    {
        $territory = Territory::factory()->create([
            'team_id' => $this->team->id,
            'name' => 'West Region',
        ]);

        $members = User::factory()->count(2)->create(['email_verified_at' => now()]);
        $this->team->users()->attach($members);
        $territory->users()->attach($members);

        Livewire::test(ViewTerritory::class, ['record' => $territory->getKey()])
            ->assertOk()
            ->assertSee('West Region')
            ->assertSee($members->first()->name);
    }
}
