<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\App\Pages\EditProfile;
use App\Filament\App\Pages\MailchimpIntegration;
use App\Filament\App\Pages\PersonalAccessTokensPage;
use App\Filament\App\Pages\ReportPage;
use App\Filament\App\Pages\TwilioIntegration;
use App\Filament\App\Pages\TwilioSettings;
use App\Filament\App\Pages\UpdateProfileInformationPage;
use App\Filament\App\Pages\VisualPipeline;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PageMountTest extends TestCase
{
    use RefreshDatabase;

    private function actingManagerWithTeam(): Team
    {
        Role::findOrCreate('manager', 'web');
        $user = User::factory()->withPersonalTeam()->create(['email_verified_at' => now()]);
        $team = $user->ownedTeams->first();
        $user->current_team_id = $team->id;
        $user->save();
        $user->assignRole('manager');
        $this->actingAs($user);

        return $team;
    }

    /** Standard team-scoped page: /app/{tenant}/{slug} */
    private function assertPageMounts(string $page): void
    {
        $team = $this->actingManagerWithTeam();
        $url = '/app/'.$team->id.'/'.$page::getSlug();
        $this->get($url)->assertStatus(200);
    }

    public function test_edit_profile_page_mounts(): void
    {
        $this->assertPageMounts(EditProfile::class);
    }

    public function test_mailchimp_integration_page_mounts(): void
    {
        $this->assertPageMounts(MailchimpIntegration::class);
    }

    public function test_personal_access_tokens_page_mounts(): void
    {
        $this->assertPageMounts(PersonalAccessTokensPage::class);
    }

    public function test_report_page_mounts(): void
    {
        $this->assertPageMounts(ReportPage::class);
    }

    public function test_twilio_integration_page_mounts(): void
    {
        $this->assertPageMounts(TwilioIntegration::class);
    }

    public function test_twilio_settings_page_mounts(): void
    {
        $this->assertPageMounts(TwilioSettings::class);
    }

    public function test_update_profile_information_page_mounts(): void
    {
        $this->assertPageMounts(UpdateProfileInformationPage::class);
    }

    public function test_visual_pipeline_page_mounts(): void
    {
        $this->assertPageMounts(VisualPipeline::class);
    }

    // Tenancy pages have their own (non-slug) routes.

    public function test_create_team_page_mounts(): void
    {
        $this->actingManagerWithTeam();
        // /app/new is the tenant-registration route (no tenant in context); the
        // shouldRegisterMenuItem() guard now skips the tenant-scoped menu items
        // there, so it no longer throws UrlGenerationException.
        $this->get('/app/new')->assertStatus(200);
    }

    public function test_edit_team_page_mounts(): void
    {
        $team = $this->actingManagerWithTeam();
        // EditTeam is the tenant profile page: /app/{tenant}/profile
        $this->get('/app/'.$team->id.'/profile')->assertStatus(200);
    }
}
