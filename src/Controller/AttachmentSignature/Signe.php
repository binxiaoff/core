<?php

declare(strict_types=1);

namespace Unilend\Controller\AttachmentSignature;

use Exception;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Unilend\Entity\AttachmentSignature;
use Unilend\Repository\AttachmentSignatureRepository;
use Unilend\Service\Psn\{RequestSender, XmlGenerator, XmlSigner};

class Signe
{
    /**
     * @var XmlSigner
     */
    private $xmlSigner;
    /**
     * @var XmlGenerator
     */
    private $xmlGenerator;
    /**
     * @var AttachmentSignatureRepository
     */
    private $attachmentSignatureRepository;
    /**
     * @var RequestSender
     */
    private $requestSender;

    /**
     * @param XmlSigner                     $xmlSigner
     * @param XmlGenerator                  $xmlGenerator
     * @param AttachmentSignatureRepository $attachmentSignatureRepository
     * @param RequestSender                 $requestSender
     */
    public function __construct(XmlSigner $xmlSigner, XmlGenerator $xmlGenerator, AttachmentSignatureRepository $attachmentSignatureRepository, RequestSender $requestSender)
    {
        $this->xmlSigner                     = $xmlSigner;
        $this->xmlGenerator                  = $xmlGenerator;
        $this->attachmentSignatureRepository = $attachmentSignatureRepository;
        $this->requestSender                 = $requestSender;
    }

    /**
     * @param AttachmentSignature $data
     *
     * @throws TransportExceptionInterface
     * @throws Exception
     *
     * @return string
     */
    public function __invoke(AttachmentSignature $data)
    {
        if ($data->getStatus() > AttachmentSignature::STATUS_REQUESTED) {
            throw new \InvalidArgumentException(sprintf('The signature (id: %s) has already been treated', $data->getPublicId()));
        }

        $xml = $this->xmlSigner->signe($this->xmlGenerator->generate($data));

        $this->requestSender->requestSignature($xml);

        $data->setStatus(AttachmentSignature::STATUS_REQUESTED);
        $this->attachmentSignatureRepository->save($data);
    }
}
