<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\App\Resources\AuditLogResource\Pages\ViewAuditLog;
use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AppAuditLogViewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    private function actAsAdmin(): User
    {
        $admin = User::factory()->withPersonalTeam()->create(['email_verified_at' => now()]);
        $team = $admin->currentTeam;
        setPermissionsTeamId($team->id);
        $admin->assignRole('admin');
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('app'));
        Filament::setTenant($team);

        return $admin;
    }

    public function test_view_page_mounts_for_own_team_entry(): void
    {
        $admin = $this->actAsAdmin();
        $entry = AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'updated',
            'description' => 'x updated',
            'ip_address' => '127.0.0.1',
            'team_id' => $admin->currentTeam->id,
            'changes' => ['name' => ['old' => 'A', 'new' => 'B']],
        ]);

        Livewire::test(ViewAuditLog::class, ['record' => $entry->getKey()])
            ->assertOk();
    }

    public function test_view_page_shows_action_and_description(): void
    {
        $admin = $this->actAsAdmin();
        $entry = AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'updated',
            'description' => 'x updated',
            'ip_address' => '127.0.0.1',
            'team_id' => $admin->currentTeam->id,
            'changes' => ['name' => ['old' => 'A', 'new' => 'B']],
        ]);

        Livewire::test(ViewAuditLog::class, ['record' => $entry->getKey()])
            ->assertSee('updated')
            ->assertSee('x updated');
    }
}
