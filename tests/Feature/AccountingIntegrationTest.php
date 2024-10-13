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

    public function testSuccessfulConnectionToQuickBooks()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->mockAccountingService->shouldReceive('connectPlatform')
            ->once()
            ->with('quickbooks', Mockery::any())
            ->andReturn(['access_token' => 'fake_token']);

        $response = $this->postJson('/api/accounting/connect', [
            'platform' => 'quickbooks',
            'credentials' => ['code' => 'fake_code'],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('accounting_integrations', [
            'user_id' => $user->id,
            'platform' => 'quickbooks',
        ]);
    }

    public function testInvoiceSyncingFromCRMToAccountingPlatform()
    {
        $user = User::factory()->create();
        $integration = AccountingIntegration::factory()->create(['user_id' => $user->id]);
        $transaction = Transaction::factory()->create(['user_id' => $user->id]);

        $this->mockAccountingService->shouldReceive('syncInvoice')
            ->once()
            ->with($integration, $transaction)
            ->andReturn(true);


        $response = $this->actingAs($user)->postJson("/api/transactions/{$transaction->id}/sync-invoice");

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Invoice synced successfully']);
    }

    public function testPaymentDataSyncingFromAccountingPlatformToCRM()
    {
        $user = User::factory()->create();
        $integration = AccountingIntegration::factory()->create(['user_id' => $user->id]);
        $transaction = Transaction::factory()->create(['user_id' => $user->id]);

        $this->mockAccountingService->shouldReceive('syncPayment')
            ->once()
            ->with($integration, $transaction)
            ->andReturn(true);

        $response = $this->actingAs($user)->postJson("/api/transactions/{$transaction->id}/sync-payment");

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Payment synced successfully']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}