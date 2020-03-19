<?php

declare(strict_types=1);

namespace Unilend\Controller\AttachmentSignature;

use League\Flysystem\FileNotFoundException;
use Symfony\Contracts\HttpClient\Exception\{ClientExceptionInterface, RedirectionExceptionInterface, ServerExceptionInterface, TransportExceptionInterface};
use Unilend\Entity\AttachmentSignature;
use Unilend\Service\Psn\RequestSender;

class Signe
{
    /**
     * @var RequestSender
     */
    private $requestSender;

    /**
     * @param RequestSender $requestSender
     */
    public function __construct(RequestSender $requestSender)
    {
        $this->requestSender = $requestSender;
    }

    /**
     * @param AttachmentSignature $data
     *
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws FileNotFoundException
     *
     * @return AttachmentSignature
     */
    public function __invoke(AttachmentSignature $data): AttachmentSignature
    {
        // todo: If the business requires, we can have a option to "force" the sending of signature request even the status is not valid.
        if ($data->getStatus() > AttachmentSignature::STATUS_REQUESTED) {
            throw new \InvalidArgumentException(sprintf('The signature (id: %s) has already been treated', $data->getPublicId()));
        }

        return $this->requestSender->requestSignature($data);
    }
}
