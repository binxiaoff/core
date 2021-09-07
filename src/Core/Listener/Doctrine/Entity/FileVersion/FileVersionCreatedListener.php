<?php

declare(strict_types=1);

namespace KLS\Core\Listener\Doctrine\Entity\FileVersion;

use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use KLS\Core\Entity\FileVersion;
use KLS\Core\Service\DataCrypto;

class FileVersionCreatedListener
{
    /**
     * @var DataCrypto
     */
    private $dataCrypto;

    public function __construct(DataCrypto $dataCrypto)
    {
        $this->dataCrypto = $dataCrypto;
    }

    /**
     * @throws EnvironmentIsBrokenException
     */
    public function encryptKey(FileVersion $attachment): void
    {
        if (null === $attachment->getPlainEncryptionKey()) {
            return;
        }

        $attachment->setEncryptionKey($this->dataCrypto->encrypt($attachment->getPlainEncryptionKey()));
    }
}
