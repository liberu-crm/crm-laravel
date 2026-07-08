<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\OAuthConfiguration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class OAuthConfigurationEncryptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_secret_is_encrypted_at_rest(): void
    {
        $config = OAuthConfiguration::factory()->create([
            'client_secret' => 'plain-secret-xyz',
        ]);

        // Stored value is ciphertext, not the plaintext.
        $this->assertNotSame('plain-secret-xyz', $config->getRawOriginal('client_secret'));

        // Reading back through the cast decrypts transparently.
        $this->assertSame('plain-secret-xyz', $config->fresh()->client_secret);

        // The raw column is a valid Laravel-encrypted payload.
        $this->assertSame('plain-secret-xyz', Crypt::decryptString($config->getRawOriginal('client_secret')));
    }
}
