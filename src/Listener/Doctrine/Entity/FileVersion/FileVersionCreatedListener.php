<?php

declare(strict_types=1);

namespace Unilend\Listener\Doctrine\Entity\FileVersion;

use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Unilend\Core\Entity\FileVersion;
use Unilend\Service\DataCrypto;

class FileVersionCreatedListener
{
    /**
     * @var DataCrypto
     */
    private $dataCrypto;

    /**
     * @param DataCrypto $dataCrypto
     */
    public function __construct(DataCrypto $dataCrypto)
    {
        $this->dataCrypto = $dataCrypto;
    }

    /**
     * @param FileVersion $attachment
     *
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
