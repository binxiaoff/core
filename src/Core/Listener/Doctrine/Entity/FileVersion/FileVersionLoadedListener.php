<?php

declare(strict_types=1);

namespace Unilend\Core\Listener\Doctrine\Entity\FileVersion;

use Defuse\Crypto\Exception\{EnvironmentIsBrokenException, WrongKeyOrModifiedCiphertextException};
use Unilend\Core\Entity\FileVersion;
use Unilend\Core\Service\DataCrypto;

class FileVersionLoadedListener
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
     * @throws WrongKeyOrModifiedCiphertextException
     */
    public function decryptKey(FileVersion $attachment): void
    {
        if (null === $attachment->getEncryptionKey()) {
            return;
        }

        $attachment->setPlainEncryptionKey($this->dataCrypto->decrypt($attachment->getEncryptionKey()));
    }
}
