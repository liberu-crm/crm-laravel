<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\SamlConnection;
use App\Models\Team;
use Illuminate\Http\Response;

/**
 * Serves the Service-Provider (SP) SAML metadata for a team — the XML the admin
 * hands to their IdP. Available once the team has a SAML connection. The ACS
 * endpoint it advertises is implemented in the SAML login slice.
 */
class SamlMetadataController extends Controller
{
    public function __invoke(Team $team): Response
    {
        $exists = SamlConnection::withoutGlobalScope('tenant')
            ->where('team_id', $team->getKey())
            ->exists();

        abort_unless($exists, 404);

        $entityId = url('/saml/'.$team->getKey().'/metadata');
        $acsUrl = url('/saml/'.$team->getKey().'/acs');
        $slsUrl = url('/saml/'.$team->getKey().'/sls');

        $xml = <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <EntityDescriptor xmlns="urn:oasis:names:tc:SAML:2.0:metadata" entityID="{$entityId}">
          <SPSSODescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol" AuthnRequestsSigned="false" WantAssertionsSigned="true">
            <SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect" Location="{$slsUrl}"/>
            <NameIDFormat>urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress</NameIDFormat>
            <AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" Location="{$acsUrl}" index="1"/>
          </SPSSODescriptor>
        </EntityDescriptor>
        XML;

        return response($xml, 200)->header('Content-Type', 'application/samlmetadata+xml');
    }
}
