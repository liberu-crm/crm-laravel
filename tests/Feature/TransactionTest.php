<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\AccountingIntegration;
use App\Services\AccountingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockAccountingService = Mockery::mock(AccountingService::class);
        $this->app->instance(AccountingService::class, $this->mockAccountingService);
    }

    public function testAccountingServiceSyncInvoiceMock()
    {
        $user = User::factory()->create();
        $integration = AccountingIntegration::factory()->create(['user_id' => $user->id]);

        $this->mockAccountingService->shouldReceive('syncInvoice')
            ->once()
            ->andReturn(true);

        $result = app(AccountingService::class)->syncInvoice($integration, []);

        $this->assertTrue($result);
    }

    public function testAccountingServiceSyncPaymentMock()
    {
        $user = User::factory()->create();
        $integration = AccountingIntegration::factory()->create(['user_id' => $user->id]);

        $this->mockAccountingService->shouldReceive('syncPayment')
            ->once()
            ->andReturn(true);

        $result = app(AccountingService::class)->syncPayment($integration, []);

        $this->assertTrue($result);
    }

    public function testAccountingIntegrationHasConnectionDetails()
    {
        $user = User::factory()->create();
        $integration = AccountingIntegration::factory()->create(['user_id' => $user->id]);

        $this->assertNotNull($integration->connection_details);
        $this->assertIsArray($integration->connection_details);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
