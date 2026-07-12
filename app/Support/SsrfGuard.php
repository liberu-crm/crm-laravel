<?php

declare(strict_types=1);

namespace App\Support;

use App\Exceptions\SsoException;

/**
 * Guards outbound requests to admin-configured URLs (OIDC issuer + the endpoints
 * discovered from it) against SSRF: requires https and rejects any URL whose host
 * resolves to a private, loopback, link-local, or otherwise non-public address —
 * blocking access to cloud metadata (169.254.169.254), localhost services, and
 * the internal network.
 */
class SsrfGuard
{
    /**
     * @throws SsoException when the URL is not a public https endpoint.
     */
    public static function assertPublicHttps(string $url): void
    {
        $parts = parse_url($url);

        if ($parts === false || ($parts['scheme'] ?? null) !== 'https' || empty($parts['host'])) {
            throw new SsoException('Identity provider URL must be a valid https URL.');
        }

        // Normalise the host (lowercase, strip IPv6 [brackets]).
        $host = trim(strtolower($parts['host']), '[]');

        if ($host === 'localhost' || str_ends_with($host, '.localhost')) {
            throw new SsoException('Identity provider host resolves to a non-public address.');
        }

        // IP literal: check it directly.
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            if (! self::isPublicIp($host)) {
                throw new SsoException('Identity provider host resolves to a non-public address.');
            }

            return;
        }

        // Hostname: every resolved address must be public (guards round-robin).
        // An unresolvable host has no reachable target, so the outbound request
        // fails on its own — don't block it (and don't couple tests to live DNS).
        foreach (gethostbynamel($host) ?: [] as $ip) {
            if (! self::isPublicIp($ip)) {
                throw new SsoException('Identity provider host resolves to a non-public address.');
            }
        }
    }

    private static function isPublicIp(string $ip): bool
    {
        // Rejects RFC1918 private ranges and reserved ranges (loopback 127/8 + ::1,
        // link-local 169.254/16 + fe80::/10, and other reserved blocks).
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        ) !== false;
    }
}
