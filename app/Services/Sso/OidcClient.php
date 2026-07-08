<?php

declare(strict_types=1);

namespace App\Services\Sso;

use App\Exceptions\SsoException;
use App\Models\SsoConnection;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Minimal OIDC authorization-code client, driven by a team's SsoConnection.
 * Endpoints come from the provider's discovery document. The email is read from
 * the userinfo endpoint using an access token obtained by our own authenticated
 * token request — so no id_token signature handling is needed for this slice.
 */
class OidcClient
{
    /**
     * @return array<string, mixed>
     */
    public function discover(SsoConnection $connection): array
    {
        $issuer = rtrim((string) $connection->getAttribute('issuer_url'), '/');

        return Cache::remember("sso_discovery:{$issuer}", 3600, function () use ($issuer): array {
            $response = Http::acceptJson()->get($issuer.'/.well-known/openid-configuration');

            if (! $response->successful()) {
                throw new SsoException('Could not load the identity provider configuration.');
            }

            $data = (array) $response->json();

            foreach (['authorization_endpoint', 'token_endpoint', 'userinfo_endpoint'] as $key) {
                if (empty($data[$key])) {
                    throw new SsoException("Identity provider discovery is missing {$key}.");
                }
            }

            return $data;
        });
    }

    public function authorizeUrl(SsoConnection $connection, string $redirectUri, string $state, string $nonce, string $codeChallenge): string
    {
        $endpoint = (string) $this->discover($connection)['authorization_endpoint'];

        return $endpoint.'?'.http_build_query([
            'client_id' => $connection->getAttribute('client_id'),
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'nonce' => $nonce,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ]);
    }

    /**
     * @return array<string, mixed> the full token response (access_token, id_token, ...)
     */
    public function exchangeCode(SsoConnection $connection, string $code, string $redirectUri, string $codeVerifier): array
    {
        $endpoint = (string) $this->discover($connection)['token_endpoint'];

        $response = Http::asForm()->acceptJson()->post($endpoint, [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirectUri,
            'client_id' => $connection->getAttribute('client_id'),
            'client_secret' => $connection->getAttribute('client_secret'),
            'code_verifier' => $codeVerifier,
        ]);

        if (! $response->successful() || ! is_string($response->json('access_token'))) {
            throw new SsoException('The identity provider rejected the login.');
        }

        return (array) $response->json();
    }

    /**
     * @return array<string, mixed>
     */
    public function jwks(SsoConnection $connection): array
    {
        $uri = (string) ($this->discover($connection)['jwks_uri'] ?? '');
        if ($uri === '') {
            throw new SsoException('Identity provider discovery is missing jwks_uri.');
        }

        $issuer = rtrim((string) $connection->getAttribute('issuer_url'), '/');

        return Cache::remember("sso_jwks:{$issuer}", 3600, function () use ($uri): array {
            $response = Http::acceptJson()->get($uri);
            if (! $response->successful()) {
                throw new SsoException('Could not load the identity provider keys.');
            }

            return (array) $response->json();
        });
    }

    /**
     * Validates the id_token: RS256 signature against the JWKS (+ exp), then
     * issuer / audience / nonce. Returns the verified claims.
     *
     * @return array<string, mixed>
     */
    public function validateIdToken(SsoConnection $connection, string $idToken, string $expectedNonce): array
    {
        try {
            $claims = (array) JWT::decode($idToken, JWK::parseKeySet($this->jwks($connection)));
        } catch (Throwable) {
            throw new SsoException('The identity provider token could not be verified.');
        }

        $issuer = rtrim((string) $connection->getAttribute('issuer_url'), '/');
        if (rtrim((string) ($claims['iss'] ?? ''), '/') !== $issuer) {
            throw new SsoException('SSO token issuer mismatch.');
        }

        $clientId = (string) $connection->getAttribute('client_id');
        $aud = $claims['aud'] ?? null;
        $audOk = is_array($aud) ? in_array($clientId, $aud, true) : ($aud === $clientId);
        if (! $audOk) {
            throw new SsoException('SSO token audience mismatch.');
        }

        if (($claims['nonce'] ?? null) !== $expectedNonce) {
            throw new SsoException('SSO token nonce mismatch.');
        }

        return $claims;
    }

    /**
     * @return array<string, mixed>
     */
    public function userinfo(SsoConnection $connection, string $accessToken): array
    {
        $endpoint = (string) $this->discover($connection)['userinfo_endpoint'];

        $response = Http::withToken($accessToken)->acceptJson()->get($endpoint);

        if (! $response->successful()) {
            throw new SsoException('Could not read the profile from the identity provider.');
        }

        return (array) $response->json();
    }
}
