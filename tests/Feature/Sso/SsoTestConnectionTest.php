<?php

declare(strict_types=1);

namespace Tests\Feature\Sso;

use App\Filament\App\Resources\SsoConnectionResource\Pages\ListSsoConnections;
use App\Models\SsoConnection;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class SsoTestConnectionTest extends TestCase
{
    use RefreshDatabase;

    private $team;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        $admin = User::factory()->withPersonalTeam()->create(['email_verified_at' => now()]);
        $this->team = $admin->currentTeam;
        setPermissionsTeamId($this->team->id);
        $admin->assignRole('admin');
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('app'));
        Filament::setTenant($this->team);
    }

    public function test_test_action_reports_success_for_a_valid_issuer(): void
    {
        Http::fake([
            'good.example.com/.well-known/openid-configuration' => Http::response([
                'authorization_endpoint' => 'https://good.example.com/authorize',
                'token_endpoint' => 'https://good.example.com/token',
                'userinfo_endpoint' => 'https://good.example.com/userinfo',
            ]),
        ]);
        $connection = SsoConnection::factory()->create([
            'team_id' => $this->team->id,
            'issuer_url' => 'https://good.example.com',
        ]);

        Livewire::test(ListSsoConnections::class)
            ->callTableAction('testConnection', $connection)
            ->assertNotified('Connection OK');
    }

    public function test_test_action_reports_failure_for_an_unreachable_issuer(): void
    {
        Http::fake([
            'bad.example.com/.well-known/openid-configuration' => Http::response('nope', 500),
        ]);
        $connection = SsoConnection::factory()->create([
            'team_id' => $this->team->id,
            'issuer_url' => 'https://bad.example.com',
        ]);

        Livewire::test(ListSsoConnections::class)
            ->callTableAction('testConnection', $connection)
            ->assertNotified('Connection failed');
    }
}
