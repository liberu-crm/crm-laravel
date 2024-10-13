<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Transaction;
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

    public function testTransactionCreationWithAccountingPlatformSync()
    {
        $user = User::factory()->create();
        $integration = AccountingIntegration::factory()->create(['user_id' => $user->id]);

        $this->mockAccountingService->shouldReceive('syncInvoice')
            ->once()
            ->andReturn(true);

        $response = $this->actingAs($user)->postJson('/api/transactions', [
            'amount' => 1000,
            'description' => 'Test transaction',
            'accounting_integration_id' => $integration->id,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('transactions', [
            'amount' => 1000,
            'description' => 'Test transaction',
        ]);
    }

    public function testUpdatingTransactionStatusWithAccountingPlatformSync()
    {
        $user = User::factory()->create();
        $integration = AccountingIntegration::factory()->create(['user_id' => $user->id]);
        $transaction = Transaction::factory()->create([
            'user_id' => $user->id,
            'accounting_integration_id' => $integration->id,
        ]);

        $this->mockAccountingService->shouldReceive('syncPayment')
            ->once()
            ->andReturn(true);

        $response = $this->actingAs($user)->patchJson("/api/transactions/{$transaction->id}", [
            'status' => 'paid',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'status' => 'paid',
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}