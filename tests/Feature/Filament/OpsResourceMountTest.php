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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OpsResourceMountTest extends TestCase
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

    private function assertResourceMounts(string $resource): void
    {
        $team = $this->actingManagerWithTeam();
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
        $this->assertResourceMounts(OAuthConfigurationResource::class);
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
