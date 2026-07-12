<?php

declare(strict_types=1);

namespace Tests\Feature\Saml;

use App\Models\SamlConnection;
use App\Models\Team;
use App\Models\User;
use DOMDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use Tests\TestCase;

/**
 * Exercises the real SAML response-validation path: an in-test IdP keypair signs
 * a crafted SAMLResponse, which is POSTed to the ACS. OneLogin validates the
 * XML-DSig signature, conditions, audience, and InResponseTo.
 */
class SamlAcsTest extends TestCase
{
    use RefreshDatabase;

    private string $privateKey = '';

    private string $certPem = '';

    /** @return array{team: Team, connection: SamlConnection} */
    private function setupConnection(): array
    {
        [$this->privateKey, $this->certPem] = $this->idpKeypair();

        $team = Team::factory()->create();
        $connection = SamlConnection::factory()->create([
            'team_id' => $team->id,
            'idp_entity_id' => 'https://idp.example.com/entity',
            'idp_sso_url' => 'https://idp.example.com/sso',
            'idp_x509_cert' => $this->certPem,
            'enabled' => true,
        ]);

        return ['team' => $team, 'connection' => $connection];
    }

    public function test_valid_signed_response_logs_in_a_member(): void
    {
        ['team' => $team] = $this->setupConnection();
        $member = User::factory()->create(['email' => 'alice@corp.example']);
        $team->users()->attach($member);

        $requestId = '_req'.bin2hex(random_bytes(8));
        $response = $this->buildSignedResponse($team, 'alice@corp.example', $requestId);

        $result = $this->withSession(['saml_request_id' => $requestId])
            ->post('/saml/'.$team->id.'/acs', ['SAMLResponse' => $response]);

        $result->assertRedirect('/app');
        $this->assertAuthenticatedAs($member->fresh());
        $result->assertSessionHas('sso_authenticated', true);
    }

    public function test_tampered_signature_is_rejected(): void
    {
        ['team' => $team] = $this->setupConnection();
        $member = User::factory()->create(['email' => 'alice@corp.example']);
        $team->users()->attach($member);

        $requestId = '_req'.bin2hex(random_bytes(8));
        $response = $this->buildSignedResponse($team, 'alice@corp.example', $requestId);
        // Corrupt the signed document after signing.
        $tampered = base64_encode(str_replace('alice@corp.example', 'attacker@evil.example', base64_decode($response)));

        $this->withSession(['saml_request_id' => $requestId])
            ->post('/saml/'.$team->id.'/acs', ['SAMLResponse' => $tampered])
            ->assertForbidden();

        $this->assertGuest();
    }

    public function test_valid_response_for_a_non_member_is_denied(): void
    {
        ['team' => $team] = $this->setupConnection();
        // No membership for this email.
        User::factory()->create(['email' => 'stranger@corp.example']);

        $requestId = '_req'.bin2hex(random_bytes(8));
        $response = $this->buildSignedResponse($team, 'stranger@corp.example', $requestId);

        $this->withSession(['saml_request_id' => $requestId])
            ->post('/saml/'.$team->id.'/acs', ['SAMLResponse' => $response])
            ->assertForbidden();

        $this->assertGuest();
    }

    public function test_wrong_in_response_to_is_rejected(): void
    {
        ['team' => $team] = $this->setupConnection();
        $member = User::factory()->create(['email' => 'alice@corp.example']);
        $team->users()->attach($member);

        $response = $this->buildSignedResponse($team, 'alice@corp.example', '_reqEXPECTED');

        // Session expects a different request id than the response was bound to.
        $this->withSession(['saml_request_id' => '_reqDIFFERENT'])
            ->post('/saml/'.$team->id.'/acs', ['SAMLResponse' => $response])
            ->assertForbidden();

        $this->assertGuest();
    }

    /** @return array{0: string, 1: string} [privateKeyPem, certPem] */
    private function idpKeypair(): array
    {
        $pkey = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        $csr = openssl_csr_new(['commonName' => 'idp.example.com'], $pkey);
        $x509 = openssl_csr_sign($csr, null, $pkey, 365, ['digest_alg' => 'sha256']);
        openssl_x509_export($x509, $certPem);
        openssl_pkey_export($pkey, $privPem);

        return [$privPem, $certPem];
    }

    private function buildSignedResponse(Team $team, string $email, string $requestId): string
    {
        $acs = url('/saml/'.$team->id.'/acs');
        $spEntityId = url('/saml/'.$team->id.'/metadata');
        $idp = 'https://idp.example.com/entity';
        $now = gmdate('Y-m-d\TH:i:s\Z');
        $before = gmdate('Y-m-d\TH:i:s\Z', time() - 300);
        $after = gmdate('Y-m-d\TH:i:s\Z', time() + 300);
        $respId = '_resp'.bin2hex(random_bytes(8));
        $assertId = '_assert'.bin2hex(random_bytes(8));

        $xml = <<<XML
        <samlp:Response xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="{$respId}" Version="2.0" IssueInstant="{$now}" Destination="{$acs}" InResponseTo="{$requestId}">
          <saml:Issuer>{$idp}</saml:Issuer>
          <samlp:Status><samlp:StatusCode Value="urn:oasis:names:tc:SAML:2.0:status:Success"/></samlp:Status>
          <saml:Assertion xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="{$assertId}" Version="2.0" IssueInstant="{$now}">
            <saml:Issuer>{$idp}</saml:Issuer>
            <saml:Subject>
              <saml:NameID Format="urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress">{$email}</saml:NameID>
              <saml:SubjectConfirmation Method="urn:oasis:names:tc:SAML:2.0:cm:bearer">
                <saml:SubjectConfirmationData NotOnOrAfter="{$after}" Recipient="{$acs}" InResponseTo="{$requestId}"/>
              </saml:SubjectConfirmation>
            </saml:Subject>
            <saml:Conditions NotBefore="{$before}" NotOnOrAfter="{$after}">
              <saml:AudienceRestriction><saml:Audience>{$spEntityId}</saml:Audience></saml:AudienceRestriction>
            </saml:Conditions>
            <saml:AuthnStatement AuthnInstant="{$now}" SessionIndex="_sess{$assertId}">
              <saml:AuthnContext><saml:AuthnContextClassRef>urn:oasis:names:tc:SAML:2.0:ac:classes:PasswordProtectedTransport</saml:AuthnContextClassRef></saml:AuthnContext>
            </saml:AuthnStatement>
          </saml:Assertion>
        </samlp:Response>
        XML;

        $doc = new DOMDocument;
        // Drop the indentation whitespace text nodes so the signed document is
        // clean XML that passes OneLogin's XSD validation.
        $doc->preserveWhiteSpace = false;
        $doc->loadXML($xml);
        $assertion = $doc->getElementsByTagNameNS('urn:oasis:names:tc:SAML:2.0:assertion', 'Assertion')->item(0);

        $dsig = new XMLSecurityDSig;
        $dsig->setCanonicalMethod(XMLSecurityDSig::EXC_C14N);
        $dsig->addReference(
            $assertion,
            XMLSecurityDSig::SHA256,
            ['http://www.w3.org/2000/09/xmldsig#enveloped-signature', XMLSecurityDSig::EXC_C14N],
            ['id_name' => 'ID', 'overwrite' => false],
        );
        $key = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'private']);
        $key->loadKey($this->privateKey, false);
        $dsig->sign($key);
        $dsig->add509Cert($this->certPem, true);
        // SAML requires ds:Signature as the second child of Assertion (after Issuer).
        $dsig->insertSignature($assertion, $assertion->childNodes->item(1));

        return base64_encode($doc->saveXML());
    }
}
