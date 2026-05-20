<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\AccountingIntegration;
use App\Services\AccountingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Mockery;

class AccountingIntegrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockAccountingService = Mockery::mock(AccountingService::class);
        $this->app->instance(AccountingService::class, $this->mockAccountingService);
    }

    public function testAccountingIntegrationCanBeCreated()
    {
        $user = User::factory()->create();

        $integration = AccountingIntegration::factory()->create(['user_id' => $user->id]);

        $this->assertDatabaseHas('accounting_integrations', [
            'user_id' => $user->id,
            'platform' => $integration->platform,
        ]);
    }

    public function testAccountingIntegrationBelongsToUser()
    {
        $user = User::factory()->create();
        $integration = AccountingIntegration::factory()->create(['user_id' => $user->id]);

        $this->assertEquals($user->id, $integration->user->id);
    }

    public function testAccountingServiceConnectPlatformMock()
    {
        $user = User::factory()->create();

        $this->mockAccountingService->shouldReceive('connectPlatform')
            ->once()
            ->with('quickbooks', Mockery::any())
            ->andReturn(['access_token' => 'fake_token']);

        $result = app(AccountingService::class)->connectPlatform('quickbooks', ['code' => 'fake_code']);

        $this->assertEquals('fake_token', $result['access_token']);
    }

    public function testAccountingIntegrationCanBeDeleted()
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
