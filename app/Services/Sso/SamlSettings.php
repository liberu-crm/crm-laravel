<?php

declare(strict_types=1);

namespace App\Services\Sso;

use App\Models\SamlConnection;
use App\Models\Team;
use OneLogin\Saml2\Constants;

/**
 * Builds a OneLogin\Saml2 settings array for a team's SAML connection. The SP
 * side mirrors the metadata served by SamlMetadataController (same entityId +
 * ACS URL, unsigned AuthnRequests, signed assertions required); the IdP side is
 * the team's stored connection. Used for both the AuthnRequest (redirect) and
 * the response validation (ACS).
 */
class SamlSettings
{
    /**
     * @return array<string, mixed>
     */
    public static function for(Team $team, SamlConnection $connection): array
    {
        $teamId = $team->getKey();

        return [
            // Reject anything that doesn't validate — never trust an unsigned or
            // malformed response.
            'strict' => true,
            'sp' => [
                'entityId' => url('/saml/'.$teamId.'/metadata'),
                'assertionConsumerService' => [
                    'url' => url('/saml/'.$teamId.'/acs'),
                    'binding' => Constants::BINDING_HTTP_POST,
                ],
                'NameIDFormat' => Constants::NAMEID_EMAIL_ADDRESS,
            ],
            'idp' => [
                'entityId' => (string) $connection->getAttribute('idp_entity_id'),
                'singleSignOnService' => [
                    'url' => (string) $connection->getAttribute('idp_sso_url'),
                    'binding' => Constants::BINDING_HTTP_REDIRECT,
                ],
                'x509cert' => (string) $connection->getAttribute('idp_x509_cert'),
            ],
            'security' => [
                // The SP doesn't sign its AuthnRequests (no SP key); it does
                // require the IdP to sign the assertion it returns.
                'authnRequestsSigned' => false,
                'wantAssertionsSigned' => true,
                'wantMessagesSigned' => false,
                'wantNameId' => true,
            ],
        ];
    }
}
