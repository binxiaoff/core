<?php

declare(strict_types=1);

namespace Unilend\Core\Controller\FileVersionSignature;

use League\Flysystem\FilesystemException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Unilend\Core\Entity\FileVersionSignature;
use Unilend\Core\Service\ElectronicSignature\RequestSender;

class Sign
{
    private RequestSender $requestSender;

    public function __construct(RequestSender $requestSender)
    {
        $this->requestSender = $requestSender;
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws FilesystemException
     */
    public function __invoke(FileVersionSignature $data): FileVersionSignature
    {
        // todo: If the business requires, we can have a option to "force" the sending of signature request even the status is not valid.
        if ($data->getStatus() > FileVersionSignature::STATUS_REQUESTED) {
            throw new \InvalidArgumentException(sprintf('The signature (id: %s) has already been treated', $data->getPublicId()));
        }

        return $this->requestSender->requestSignature($data);
    }
}
