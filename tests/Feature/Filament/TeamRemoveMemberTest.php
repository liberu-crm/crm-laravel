<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\App\Resources\TeamMemberResource\Pages\ListTeamMembers;
use App\Models\User;
use App\Services\TeamManagementService;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TestCase;

class TeamRemoveMemberTest extends TestCase
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

    private function member(string $role = 'sales_rep'): User
    {
        $member = User::factory()->create(['email_verified_at' => now()]);
        $this->team->users()->attach($member);
        setPermissionsTeamId($this->team->id);
        $member->assignRole($role);

        return $member;
    }

    public function test_service_removes_membership_and_roles(): void
    {
        $member = $this->member('manager');

        app(TeamManagementService::class)->removeTeamMember($member, $this->team);

        $this->assertFalse($this->team->fresh()->users->contains($member));
        setPermissionsTeamId($this->team->id);
        $this->assertFalse($member->fresh()->hasRole('manager'));
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'team.member_removed',
            'auditable_id' => $member->id,
            'team_id' => $this->team->id,
        ]);
    }

    public function test_service_rejects_removing_the_owner(): void
    {
        $this->expectException(InvalidArgumentException::class);
        app(TeamManagementService::class)->removeTeamMember($this->admin, $this->team);
    }

    public function test_remove_action_hidden_on_own_and_owner_rows(): void
    {
        // The admin owns their personal team, so their own row is also the owner row.
        $member = $this->member();

        Livewire::test(ListTeamMembers::class)
            ->assertTableActionHidden('removeMember', $this->admin)
            ->assertTableActionVisible('removeMember', $member);
    }

    public function test_action_removes_the_member(): void
    {
        $member = $this->member();

        Livewire::test(ListTeamMembers::class)
            ->callTableAction('removeMember', $member);

        $this->assertFalse($this->team->fresh()->users->contains($member));
    }
}
