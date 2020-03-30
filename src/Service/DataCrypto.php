<?php

declare(strict_types=1);

namespace Unilend\Service;

use Defuse\Crypto\Exception\{BadFormatException, EnvironmentIsBrokenException, WrongKeyOrModifiedCiphertextException};
use Defuse\Crypto\{Crypto, Key, KeyProtectedByPassword};

class DataCrypto
{
    /**
     * @var Key
     */
    private $masterKey;

    /**
     * @param string $masterKeyPath
     * @param string $masterKeyPassPhrase
     *
     * @throws BadFormatException
     * @throws EnvironmentIsBrokenException
     * @throws WrongKeyOrModifiedCiphertextException
     */
    public function __construct(string $masterKeyPath, string $masterKeyPassPhrase)
    {
        $this->masterKey = (KeyProtectedByPassword::loadFromAsciiSafeString(file_get_contents($masterKeyPath)))->unlockKey(hex2bin($masterKeyPassPhrase));
    }

    /**
     * @param string $plaintext
     *
     * @throws EnvironmentIsBrokenException
     *
     * @return string
     */
    public function encrypt(string $plaintext): string
    {
        return Crypto::encrypt($plaintext, $this->masterKey);
    }

    /**
     * @param string $cipherText
     *
     * @throws EnvironmentIsBrokenException
     * @throws WrongKeyOrModifiedCiphertextException
     *
     * @return string
     */
    public function decrypt(string $cipherText): string
    {
        return Crypto::decrypt($cipherText, $this->masterKey);
    }
}
