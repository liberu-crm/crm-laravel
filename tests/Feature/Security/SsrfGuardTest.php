<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Exceptions\SsoException;
use App\Support\SsrfGuard;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SsrfGuardTest extends TestCase
{
    public function test_accepts_a_public_https_url(): void
    {
        SsrfGuard::assertPublicHttps('https://accounts.google.com');
        $this->addToAssertionCount(1);
    }

    /** @return array<string, array{0: string}> */
    public static function blockedUrls(): array
    {
        return [
            'http scheme' => ['http://accounts.google.com'],
            'no scheme' => ['accounts.google.com'],
            'loopback ip' => ['https://127.0.0.1/x'],
            'localhost' => ['https://localhost/x'],
            'cloud metadata' => ['https://169.254.169.254/latest/meta-data'],
            'private 10' => ['https://10.0.0.5/x'],
            'private 192.168' => ['https://192.168.1.1/x'],
            'private 172.16' => ['https://172.16.0.1/x'],
            'ipv6 loopback' => ['https://[::1]/x'],
        ];
    }

    #[DataProvider('blockedUrls')]
    public function test_rejects_non_public_or_non_https_urls(string $url): void
    {
        $this->expectException(SsoException::class);
        SsrfGuard::assertPublicHttps($url);
    }
}
