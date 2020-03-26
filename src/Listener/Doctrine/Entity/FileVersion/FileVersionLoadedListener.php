<?php

declare(strict_types=1);

namespace Unilend\Listener\Doctrine\Entity\FileVersion;

use Defuse\Crypto\Exception\{EnvironmentIsBrokenException, WrongKeyOrModifiedCiphertextException};
use Unilend\Entity\Attachment;
use Unilend\Service\DataCrypto;

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
     * @param Attachment $attachment
     *
     * @throws EnvironmentIsBrokenException
     * @throws WrongKeyOrModifiedCiphertextException
     */
    public function decryptKey(Attachment $attachment): void
    {
        if (null === $attachment->getEncryptionKey()) {
            return;
        }

        $attachment->setEncryptionKey($this->dataCrypto->decrypt($attachment->getEncryptionKey()));
    }
}
