<?php

declare(strict_types=1);

namespace App\Services\Sso;

use App\Exceptions\SsoException;
use App\Models\SsoConnection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

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

    public function authorizeUrl(SsoConnection $connection, string $redirectUri, string $state): string
    {
        $endpoint = (string) $this->discover($connection)['authorization_endpoint'];

        return $endpoint.'?'.http_build_query([
            'client_id' => $connection->getAttribute('client_id'),
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
        ]);
    }

    public function exchangeCode(SsoConnection $connection, string $code, string $redirectUri): string
    {
        $endpoint = (string) $this->discover($connection)['token_endpoint'];

        $response = Http::asForm()->acceptJson()->post($endpoint, [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirectUri,
            'client_id' => $connection->getAttribute('client_id'),
            'client_secret' => $connection->getAttribute('client_secret'),
        ]);

        $token = $response->json('access_token');

        if (! $response->successful() || ! is_string($token)) {
            throw new SsoException('The identity provider rejected the login.');
        }

        return $token;
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
