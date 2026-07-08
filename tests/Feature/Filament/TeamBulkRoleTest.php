<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\Role;
use App\Filament\App\Resources\TeamMemberResource\Pages\ListTeamMembers;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TeamBulkRoleTest extends TestCase
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

    public function test_bulk_sets_the_role_on_selected_members(): void
    {
        $a = $this->member();
        $b = $this->member();

        Livewire::test(ListTeamMembers::class)
            ->callTableBulkAction('setRole', [$a, $b], data: ['role' => Role::Manager->value]);

        setPermissionsTeamId($this->team->id);
        $this->assertTrue($a->fresh()->hasRole(Role::Manager->value));
        $this->assertTrue($b->fresh()->hasRole(Role::Manager->value));
    }

    public function test_bulk_skips_the_acting_admin_and_owner(): void
    {
        $member = $this->member('sales_rep');

        // The admin owns their personal team, so selecting them = self + owner.
        Livewire::test(ListTeamMembers::class)
            ->callTableBulkAction('setRole', [$this->admin, $member], data: ['role' => Role::Free->value]);

        setPermissionsTeamId($this->team->id);
        $this->assertTrue($this->admin->fresh()->hasRole('admin'));
        $this->assertFalse($this->admin->fresh()->hasRole('free'));
        $this->assertTrue($member->fresh()->hasRole('free'));
    }
}
