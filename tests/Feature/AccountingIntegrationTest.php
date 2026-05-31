<?php

namespace Tests\Feature;

use App\Models\AccountingIntegration;
use App\Models\User;
use App\Services\AccountingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Mockery;
use Tests\TestCase;

class AccountingIntegrationTest extends TestCase
{
    public $mockAccountingService;
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockAccountingService = Mockery::mock(AccountingService::class);
        $this->app->instance(AccountingService::class, $this->mockAccountingService);
    }

    public function test_accounting_integration_can_be_created(): void
    {
        $user = User::factory()->create();

        $integration = AccountingIntegration::factory()->create(['user_id' => $user->id]);

        $this->assertDatabaseHas('accounting_integrations', [
            'user_id' => $user->id,
            'platform' => $integration->platform,
        ]);
    }

    public function test_accounting_integration_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $integration = AccountingIntegration::factory()->create(['user_id' => $user->id]);

        $this->assertEquals($user->id, $integration->user->id);
    }

    public function test_accounting_service_connect_platform_mock(): void
    {
        User::factory()->create();

        $this->mockAccountingService->shouldReceive('connectPlatform')
            ->once()
            ->with('quickbooks', Mockery::any())
            ->andReturn(['access_token' => 'fake_token']);

        $result = app(AccountingService::class)->connectPlatform('quickbooks', ['code' => 'fake_code']);

        $this->assertEquals('fake_token', $result['access_token']);
    }

    public function test_accounting_integration_can_be_deleted(): void
    {
        $integration = AccountingIntegration::factory()->create();
        $id = $integration->id;

        $integration->delete();

        $this->assertDatabaseMissing('accounting_integrations', ['id' => $id]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
