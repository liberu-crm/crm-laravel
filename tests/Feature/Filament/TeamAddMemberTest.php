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
use Livewire\Livewire;
use Tests\TestCase;

class TeamAddMemberTest extends TestCase
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

    public function test_admin_adds_an_existing_user_with_a_role(): void
    {
        $target = User::factory()->create(['email' => 'newbie@example.com', 'email_verified_at' => now()]);

        Livewire::test(ListTeamMembers::class)
            ->callAction('addMember', data: ['email' => 'newbie@example.com', 'role' => Role::Manager->value]);

        $this->assertTrue($this->team->fresh()->users->contains($target));

        setPermissionsTeamId($this->team->id);
        $this->assertTrue($target->fresh()->hasRole(Role::Manager->value));
    }

    public function test_adding_an_unknown_email_adds_no_one(): void
    {
        $before = $this->team->users()->count();

        Livewire::test(ListTeamMembers::class)
            ->callAction('addMember', data: ['email' => 'nobody@example.com', 'role' => Role::Free->value]);

        $this->assertSame($before, $this->team->fresh()->users()->count());
    }

    public function test_service_adds_member_and_role(): void
    {
        $target = User::factory()->create();

        app(TeamManagementService::class)->addTeamMember($target, $this->team, Role::SalesRep);

        $this->assertTrue($this->team->fresh()->users->contains($target));
        setPermissionsTeamId($this->team->id);
        $this->assertTrue($target->fresh()->hasRole(Role::SalesRep->value));
    }
}
