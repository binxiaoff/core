<?php

declare(strict_types=1);

namespace Unilend\Listener\Doctrine\Entity\FileVersion;

use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Unilend\Entity\Attachment;
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
     * @param Attachment $attachment
     *
     * @throws EnvironmentIsBrokenException
     */
    public function encryptKey(Attachment $attachment): void
    {
        if (null === $attachment->getKey()) {
            return;
        }

        $attachment->setKey($this->dataCrypto->encrypt($attachment->getKey()));
    }
}
