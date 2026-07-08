<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\ConnectedAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class ConnectedAccountEncryptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_social_tokens_are_encrypted_at_rest(): void
    {
        // user_id is satisfied by the factory's User::factory() default.
        $account = ConnectedAccount::factory()->create([
            'token' => 'plain-token-123',
            'secret' => 'plain-secret-456',
            'refresh_token' => 'plain-refresh-789',
        ]);

        $plaintext = [
            'token' => 'plain-token-123',
            'secret' => 'plain-secret-456',
            'refresh_token' => 'plain-refresh-789',
        ];

        foreach ($plaintext as $column => $value) {
            $this->assertNotSame($value, $account->getRawOriginal($column), "{$column} stored as plaintext");
            $this->assertSame($value, $account->fresh()->{$column}, "{$column} did not decrypt on read");
            $this->assertSame($value, Crypt::decryptString($account->getRawOriginal($column)), "{$column} ciphertext not decryptable");
        }
    }

    public function test_null_secret_does_not_error_on_read(): void
    {
        $account = ConnectedAccount::factory()->create(['secret' => null]);

        $this->assertNull($account->fresh()->secret);
    }
}
