<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\App\Resources\WebhookDeliveryResource;
use App\Filament\App\Resources\WebhookDeliveryResource\Pages\ListWebhookDeliveries;
use App\Models\Team;
use App\Models\User;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use App\Services\WebhookService;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class WebhookDeliveryTest extends TestCase
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

    private function makeWebhook(array $events, ?int $teamId = null): Webhook
    {
        return Webhook::create([
            'name' => 'Hook',
            'url' => 'https://example.test/hook',
            'events' => $events,
            'team_id' => $teamId ?? $this->team->id,
        ]);
    }

    public function test_successful_send_records_a_delivery(): void
    {
        Http::fake(['*' => Http::response('ok', 200)]);
        $webhook = $this->makeWebhook(['contact.created']);

        $result = app(WebhookService::class)->send($webhook, 'contact.created', ['id' => 1]);

        $this->assertTrue($result);
        $delivery = WebhookDelivery::where('webhook_id', $webhook->id)->firstOrFail();
        $this->assertSame('contact.created', $delivery->event);
        $this->assertTrue($delivery->success);
        $this->assertSame(200, $delivery->status_code);
        $this->assertSame($this->team->id, $delivery->team_id);
    }

    public function test_failed_send_records_a_delivery(): void
    {
        Http::fake(['*' => Http::response('boom', 500)]);
        $webhook = $this->makeWebhook(['contact.updated']);

        $result = app(WebhookService::class)->send($webhook, 'contact.updated', []);

        $this->assertFalse($result);
        $delivery = WebhookDelivery::where('webhook_id', $webhook->id)->firstOrFail();
        $this->assertFalse($delivery->success);
        $this->assertSame(500, $delivery->status_code);
    }

    public function test_resource_lists_only_current_team_deliveries(): void
    {
        $mine = WebhookDelivery::create([
            'webhook_id' => $this->makeWebhook(['contact.created'])->id,
            'team_id' => $this->team->id,
            'event' => 'contact.created',
            'success' => true,
            'status_code' => 200,
        ]);

        $otherTeam = Team::factory()->create();
        $others = WebhookDelivery::create([
            'webhook_id' => $this->makeWebhook(['contact.created'], $otherTeam->id)->id,
            'team_id' => $otherTeam->id,
            'event' => 'contact.created',
            'success' => false,
            'status_code' => 500,
        ]);

        Livewire::test(ListWebhookDeliveries::class)
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$others]);
    }

    public function test_access_is_admin_only(): void
    {
        $this->assertTrue(WebhookDeliveryResource::canAccess());

        $rep = User::factory()->withPersonalTeam()->create(['email_verified_at' => now()]);
        setPermissionsTeamId($rep->currentTeam->id);
        $rep->assignRole('sales_rep');
        $this->actingAs($rep);

        $this->assertFalse(WebhookDeliveryResource::canAccess());
    }
}
