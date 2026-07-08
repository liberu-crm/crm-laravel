<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\Role;
use App\Filament\App\Resources\TeamMemberResource\Pages\ListTeamMembers;
use App\Models\User;
use App\Services\TeamManagementService;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TestCase;

class TeamOwnerRoleGuardTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $admin;

    private $team;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);

        // A team owned by $owner; $admin is a (non-owner) admin member acting.
        $this->owner = User::factory()->withPersonalTeam()->create(['email_verified_at' => now()]);
        $this->team = $this->owner->currentTeam;
        $this->admin = User::factory()->create(['email_verified_at' => now()]);
        $this->team->users()->attach($this->admin);

        setPermissionsTeamId($this->team->id);
        $this->owner->assignRole('admin');
        $this->admin->assignRole('admin');

        $this->actingAs($this->admin);
        Filament::setCurrentPanel(Filament::getPanel('app'));
        Filament::setTenant($this->team);
    }

    public function test_change_team_role_rejects_changing_the_owner(): void
    {
        $this->expectException(InvalidArgumentException::class);
        app(TeamManagementService::class)->changeTeamRole($this->owner, $this->team, Role::Manager);
    }

    public function test_change_action_hidden_on_owner_row_visible_on_member(): void
    {
        $member = User::factory()->create(['email_verified_at' => now()]);
        $this->team->users()->attach($member);

        Livewire::test(ListTeamMembers::class)
            ->assertTableActionHidden('changeRole', $this->owner)
            ->assertTableActionVisible('changeRole', $member);
    }
}
