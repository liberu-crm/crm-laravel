<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use App\Filament\App\Resources\PortalBrandingResource\Pages\EditPortalBranding;
use App\Models\Team;
use App\Models\User;
use App\Support\PortalBranding;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PortalLogoUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        config(['portal.logo' => 'https://cdn/default.png']);
    }

    private function actAsCustomer(Team $team): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->forceFill(['current_team_id' => $team->id])->save();
        setPermissionsTeamId(null);
        $user->assignRole('customer');
        $this->actingAs($user);
    }

    public function test_admin_uploads_a_logo(): void
    {
        Storage::fake('public');
        $admin = User::factory()->withPersonalTeam()->create(['email_verified_at' => now()]);
        $team = $admin->currentTeam;
        setPermissionsTeamId($team->id);
        $admin->assignRole('admin');
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('app'));
        Filament::setTenant($team);

        Livewire::test(EditPortalBranding::class, ['record' => $team->getKey()])
            ->fillForm(['portal_logo_path' => UploadedFile::fake()->image('logo.png')])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNotNull($team->fresh()->getAttribute('portal_logo_path'));
    }

    public function test_logo_prefers_the_uploaded_file(): void
    {
        Storage::fake('public');
        $team = Team::factory()->create([
            'portal_logo_path' => 'portal-logos/acme.png',
            'portal_logo_url' => 'https://cdn/acme-url.png',
        ]);
        $this->actAsCustomer($team);

        $this->assertSame(Storage::disk('public')->url('portal-logos/acme.png'), PortalBranding::logo());
    }

    public function test_logo_falls_back_to_url_then_config(): void
    {
        $withUrl = Team::factory()->create(['portal_logo_path' => null, 'portal_logo_url' => 'https://cdn/url.png']);
        $this->actAsCustomer($withUrl);
        $this->assertSame('https://cdn/url.png', PortalBranding::logo());

        $bare = Team::factory()->create(['portal_logo_path' => null, 'portal_logo_url' => null]);
        $this->actAsCustomer($bare);
        $this->assertSame('https://cdn/default.png', PortalBranding::logo());
    }
}
