<?php

declare(strict_types=1);

namespace Unilend\Controller\FileVersionSignature;

use League\Flysystem\FileNotFoundException;
use Symfony\Contracts\HttpClient\Exception\{ClientExceptionInterface, RedirectionExceptionInterface, ServerExceptionInterface, TransportExceptionInterface};
use Unilend\Entity\FileVersionSignature;
use Unilend\Service\ElectronicSignature\RequestSender;

class Sign
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
     * @param FileVersionSignature $data
     *
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws FileNotFoundException
     * @throws ClientExceptionInterface
     *
     * @return FileVersionSignature
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
