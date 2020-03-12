<?php

declare(strict_types=1);

namespace Unilend\Service\PSN;

use DOMDocument;
use Exception;
use InvalidArgumentException;
use RobRichards\XMLSecLibs\{XMLSecurityDSig, XMLSecurityKey};

class XmlSigner
{
    private const CANONICAL_METHOD      = XMLSecurityDSig::EXC_C14N;
    private const DIGEST_METHOD         = XMLSecurityDSig::SHA256;
    private const PRIVATE_KEY_ALGORITHM = XMLSecurityKey::RSA_SHA256;

    /**
     * @param string $xmlSource
     * @param string $privateKey
     * @param string $publicKey
     *
     * @throws Exception
     *
     * @return string
     */
    public function signe(string $xmlSource, string $privateKey, string $publicKey): string
    {
        $xml = new DOMDocument();
        $xml->loadXML($xmlSource);

        if (false === $xml) {
            throw new InvalidArgumentException('The xml is not valid');
        }

        $xmlDSig = new XMLSecurityDSig();
        $xmlDSig->setCanonicalMethod(self::CANONICAL_METHOD);
        $xmlDSig->addReference($xml, self::DIGEST_METHOD, ['http://www.w3.org/2000/09/xmldsig#enveloped-signature']);

        $xmlDSigKey = new XMLSecurityKey(self::PRIVATE_KEY_ALGORITHM, ['type' => 'private']);
        $xmlDSigKey->loadKey($privateKey, true);

        $xmlDSig->sign($xmlDSigKey);
        $xmlDSig->add509Cert($publicKey);

        $xmlDSig->appendSignature($xml->documentElement);

        return $xml->saveXML();
    }
}
