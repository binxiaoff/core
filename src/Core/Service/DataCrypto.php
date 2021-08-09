<?php

declare(strict_types=1);

namespace KLS\Core\Service;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Exception\BadFormatException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Defuse\Crypto\Key;
use Defuse\Crypto\KeyProtectedByPassword;

class DataCrypto
{
    /**
     * @var Key
     */
    private $masterKey;

    /**
     * @throws BadFormatException
     * @throws EnvironmentIsBrokenException
     * @throws WrongKeyOrModifiedCiphertextException
     */
    public function __construct(string $masterKeyPath, string $masterKeyPassPhrase)
    {
        $this->masterKey = (KeyProtectedByPassword::loadFromAsciiSafeString(\file_get_contents($masterKeyPath)))->unlockKey(\hex2bin($masterKeyPassPhrase));
    }

    /**
     * @throws EnvironmentIsBrokenException
     */
    public function encrypt(string $plaintext): string
    {
        return Crypto::encrypt($plaintext, $this->masterKey);
    }

    /**
     * @throws EnvironmentIsBrokenException
     * @throws WrongKeyOrModifiedCiphertextException
     */
    public function decrypt(string $cipherText): string
    {
        return Crypto::decrypt($cipherText, $this->masterKey);
    }
}
