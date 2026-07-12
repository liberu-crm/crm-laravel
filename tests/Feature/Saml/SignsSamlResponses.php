<?php

declare(strict_types=1);

namespace Tests\Feature\Saml;

use App\Models\Team;
use DOMDocument;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;

/**
 * Builds signed SAMLResponses for tests using an in-test IdP keypair, so the
 * ACS runs its real OneLogin validation. Supports attribute statements (e.g. a
 * groups attribute for role mapping).
 */
trait SignsSamlResponses
{
    private string $idpPrivateKey = '';

    private string $idpCertPem = '';

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
        $this->idpPrivateKey = $privPem;
        $this->idpCertPem = $certPem;

        return [$privPem, $certPem];
    }

    /**
     * @param  array<string, array<int, string>>  $attributes  attr name => values
     */
    private function buildSignedResponse(Team $team, string $email, string $requestId, array $attributes = []): string
    {
        $acs = url('/saml/'.$team->id.'/acs');
        $spEntityId = url('/saml/'.$team->id.'/metadata');
        $idp = 'https://idp.example.com/entity';
        $now = gmdate('Y-m-d\TH:i:s\Z');
        $before = gmdate('Y-m-d\TH:i:s\Z', time() - 300);
        $after = gmdate('Y-m-d\TH:i:s\Z', time() + 300);
        $respId = '_resp'.bin2hex(random_bytes(8));
        $assertId = '_assert'.bin2hex(random_bytes(8));
        $attrXml = $this->attributeStatement($attributes);

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
            {$attrXml}
          </saml:Assertion>
        </samlp:Response>
        XML;

        $doc = new DOMDocument;
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
        $key->loadKey($this->idpPrivateKey, false);
        $dsig->sign($key);
        $dsig->add509Cert($this->idpCertPem, true);
        // SAML requires ds:Signature as the second child of Assertion (after Issuer).
        $dsig->insertSignature($assertion, $assertion->childNodes->item(1));

        return base64_encode($doc->saveXML());
    }

    /**
     * @param  array<string, array<int, string>>  $attributes
     */
    private function attributeStatement(array $attributes): string
    {
        if ($attributes === []) {
            return '';
        }

        $attrs = '';
        foreach ($attributes as $name => $values) {
            $vals = '';
            foreach ($values as $value) {
                $vals .= '<saml:AttributeValue>'.htmlspecialchars($value, ENT_XML1).'</saml:AttributeValue>';
            }
            $attrs .= '<saml:Attribute Name="'.htmlspecialchars($name, ENT_XML1).'">'.$vals.'</saml:Attribute>';
        }

        return '<saml:AttributeStatement>'.$attrs.'</saml:AttributeStatement>';
    }
}
