<?php

declare(strict_types=1);

namespace KLS\Core\Service\ElectronicSignature;

use DOMDocument;
use Exception;
use InvalidArgumentException;
use RobRichards\XMLSecLibs\XMLSecEnc;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use RuntimeException;

class XmlSigner
{
    private const CANONICAL_METHOD      = XMLSecurityDSig::C14N;
    private const DIGEST_METHOD         = XMLSecurityDSig::SHA256;
    private const PRIVATE_KEY_ALGORITHM = XMLSecurityKey::RSA_SHA256;
    private const TRANSFORM_ALGORITHMS  = ['http://www.w3.org/2000/09/xmldsig#enveloped-signature', XMLSecurityDSig::EXC_C14N];

    /**
     * @var string
     */
    private $publicKey;
    /**
     * @var string
     */
    private $privateKey;

    public function __construct(string $publicKey, string $privateKey)
    {
        $this->publicKey  = $publicKey;
        $this->privateKey = $privateKey;
    }

    /**
     * @throws Exception
     */
    public function sign(string $xmlSource): string
    {
        $xml = new DOMDocument();
        $xml->loadXML($xmlSource);

        if (false === $xml) {
            throw new InvalidArgumentException('The xml is not valid');
        }

        $xmlDSig = new XMLSecurityDSig();
        $xmlDSig->setCanonicalMethod(self::CANONICAL_METHOD);
        $xmlDSig->addReference($xml, self::DIGEST_METHOD, self::TRANSFORM_ALGORITHMS, ['force_uri' => true]);

        $xmlDSigKey = new XMLSecurityKey(self::PRIVATE_KEY_ALGORITHM, ['type' => 'private']);
        $xmlDSigKey->loadKey($this->privateKey);

        $xmlDSig->sign($xmlDSigKey);
        $xmlDSig->add509Cert($this->publicKey);

        $xmlDSig->appendSignature($xml->documentElement);

        return $xml->saveXML();
    }

    /**
     * @throws Exception
     */
    public function verify(string $xmlSource): bool
    {
        $xml = new DOMDocument();
        $xml->loadXML($xmlSource);

        if (false === $xml) {
            throw new InvalidArgumentException('The xml is not valid');
        }

        $xmlDSig = new XMLSecurityDSig();

        $signature = $xmlDSig->locateSignature($xml);
        if (!$signature) {
            throw new RuntimeException('Cannot locate Signature Node');
        }

        if (!$xmlDSig->validateReference()) {
            throw new RuntimeException('Reference Validation Failed');
        }

        $key = $xmlDSig->locateKey();
        if (!$key) {
            throw new RuntimeException('We have no idea about the key');
        }

        $keyInfo = XMLSecEnc::staticLocateKeyInfo($key, $signature);
        if (null === $keyInfo || !$keyInfo->key) {
            throw new RuntimeException('Key info not found');
        }

        return 1 === $xmlDSig->verify($key);
    }
}
