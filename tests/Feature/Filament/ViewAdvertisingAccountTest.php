<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\App\Resources\AdvertisingAccountResource\Pages\ViewAdvertisingAccount;
use App\Models\AdvertisingAccount;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ViewAdvertisingAccountTest extends TestCase
{
    use RefreshDatabase;

    private function actAs(string $role): User
    {
        $this->seed(RolesSeeder::class);
        $user = User::factory()->withPersonalTeam()->create(['email_verified_at' => now()]);
        $team = $user->currentTeam;
        setPermissionsTeamId($team->id);
        $user->assignRole($role);
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('app'));
        Filament::setTenant($team);

        return $user;
    }

    public function test_admin_can_mount_the_view_page(): void
    {
        $user = $this->actAs('admin');

        $account = AdvertisingAccount::factory()->create([
            'team_id' => $user->currentTeam->id,
        ]);

        Livewire::test(ViewAdvertisingAccount::class, ['record' => $account->getKey()])
            ->assertOk();
    }

    public function test_view_page_never_renders_the_encrypted_access_token(): void
    {
        $user = $this->actAs('admin');

        $account = AdvertisingAccount::factory()->create([
            'team_id' => $user->currentTeam->id,
            'access_token' => 'super-secret-token-abc',
        ]);

        Livewire::test(ViewAdvertisingAccount::class, ['record' => $account->getKey()])
            ->assertOk()
            ->assertDontSee('super-secret-token-abc');
    }
}
