<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\AdvertisingAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class AdvertisingAccountEncryptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_oauth_tokens_are_encrypted_at_rest(): void
    {
        // team_id is satisfied by the factory's Team::factory() default.
        $account = AdvertisingAccount::factory()->create([
            'access_token' => 'plain-access-123',
            'refresh_token' => 'plain-refresh-456',
        ]);

        // access_token
        $this->assertNotSame('plain-access-123', $account->getRawOriginal('access_token'));
        $this->assertSame('plain-access-123', $account->fresh()->access_token);
        $this->assertSame('plain-access-123', Crypt::decryptString($account->getRawOriginal('access_token')));

        // refresh_token
        $this->assertNotSame('plain-refresh-456', $account->getRawOriginal('refresh_token'));
        $this->assertSame('plain-refresh-456', $account->fresh()->refresh_token);
        $this->assertSame('plain-refresh-456', Crypt::decryptString($account->getRawOriginal('refresh_token')));
    }
}
