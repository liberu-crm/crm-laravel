<?php

namespace Tests\Feature\Filament;

use App\Filament\App\Resources\CallSettingResource;
use App\Filament\App\Resources\DashboardWidgetResource;
use App\Filament\App\Resources\FormBuilderResource;
use App\Filament\App\Resources\MessageResource;
use App\Filament\App\Resources\OAuthConfigurationResource;
use App\Filament\App\Resources\TicketResource;
use App\Filament\App\Resources\WhatsAppNumberResource;
use App\Filament\App\Resources\WorkflowResource;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpsResourceMountTest extends TestCase
{
    use RefreshDatabase;

    private function actingWithTeam(string $role): Team
    {
        $this->seed(RolesSeeder::class);
        $user = User::factory()->withPersonalTeam()->create(['email_verified_at' => now()]);
        $team = $user->ownedTeams->first();
        $user->current_team_id = $team->id;
        $user->save();
        setPermissionsTeamId($team->id);
        $user->assignRole($role);
        $this->actingAs($user);

        return $team;
    }

    private function assertResourceMounts(string $resource, string $role = 'manager'): void
    {
        $team = $this->actingWithTeam($role);
        $url = '/app/'.$team->id.'/'.$resource::getSlug();
        $this->get($url)->assertStatus(200);
    }

    public function test_ticket_resource_index_mounts(): void
    {
        $this->assertResourceMounts(TicketResource::class);
    }

    public function test_message_resource_index_mounts(): void
    {
        $this->assertResourceMounts(MessageResource::class);
    }

    public function test_whatsapp_number_resource_index_mounts(): void
    {
        $this->assertResourceMounts(WhatsAppNumberResource::class);
    }

    public function test_workflow_resource_index_mounts(): void
    {
        $this->assertResourceMounts(WorkflowResource::class);
    }

    public function test_form_builder_resource_index_mounts(): void
    {
        $this->assertResourceMounts(FormBuilderResource::class);
    }

    public function test_oauth_configuration_resource_index_mounts(): void
    {
        // OAuth config holds client secrets — a security/settings resource that
        // is admin-only under permission enforcement.
        $this->assertResourceMounts(OAuthConfigurationResource::class, 'admin');
    }

    public function test_call_setting_resource_index_mounts(): void
    {
        $this->assertResourceMounts(CallSettingResource::class);
    }

    public function test_dashboard_widget_resource_index_mounts(): void
    {
        $this->assertResourceMounts(DashboardWidgetResource::class);
    }
}
