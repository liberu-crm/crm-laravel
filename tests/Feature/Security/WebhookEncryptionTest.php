<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\Webhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class WebhookEncryptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_secret_is_encrypted_at_rest(): void
    {
        $webhook = Webhook::create([
            'name' => 'Test hook',
            'url' => 'https://example.com/hook',
            'events' => ['contact.created'],
            'secret' => 'plain-whsec-1',
        ]);

        $this->assertNotSame('plain-whsec-1', $webhook->getRawOriginal('secret'));
        $this->assertSame('plain-whsec-1', $webhook->fresh()->secret);
        $this->assertSame('plain-whsec-1', Crypt::decryptString($webhook->getRawOriginal('secret')));
    }

    public function test_null_secret_stays_null(): void
    {
        $webhook = Webhook::create([
            'name' => 'No secret hook',
            'url' => 'https://example.com/hook',
            'events' => ['contact.created'],
        ]);

        $this->assertNull($webhook->secret);
        $this->assertNull($webhook->fresh()->secret);
    }
}
