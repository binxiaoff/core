<?php

declare(strict_types=1);

namespace Unilend\Service\ElectronicSignature;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use Exception;
use League\Flysystem\FileNotFoundException;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\{Exception\ClientExceptionInterface, Exception\RedirectionExceptionInterface, Exception\ServerExceptionInterface,
    Exception\TransportExceptionInterface, HttpClientInterface, ResponseInterface};
use Unilend\Entity\AttachmentSignature;
use Unilend\Repository\AttachmentSignatureRepository;

class RequestSender
{
    private const REQUEST_PATH = 'souscription_ca/sgp';
    /**
     * @var HttpClientInterface
     */
    private $psnClient;
    /**
     * @var LoggerInterface
     */
    private $logger;
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
     * @param XmlSigner                     $xmlSigner
     * @param XmlGenerator                  $xmlGenerator
     * @param AttachmentSignatureRepository $attachmentSignatureRepository
     * @param HttpClientInterface           $psnClient
     * @param LoggerInterface               $logger
     */
    public function __construct(
        XmlSigner $xmlSigner,
        XmlGenerator $xmlGenerator,
        AttachmentSignatureRepository $attachmentSignatureRepository,
        HttpClientInterface $psnClient,
        LoggerInterface $logger
    ) {
        $this->psnClient                     = $psnClient;
        $this->logger                        = $logger;
        $this->xmlSigner                     = $xmlSigner;
        $this->xmlGenerator                  = $xmlGenerator;
        $this->attachmentSignatureRepository = $attachmentSignatureRepository;
    }

    /**
     * @param AttachmentSignature $fileSignature
     *
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws FileNotFoundException
     * @throws Exception
     *
     * @return AttachmentSignature
     */
    public function requestSignature(AttachmentSignature $fileSignature): AttachmentSignature
    {
        $this->handleResponse($fileSignature, $this->psnClient->request(Request::METHOD_POST, self::REQUEST_PATH, [
            'headers' => ['Content-Type' => 'application/gzip'],
            'body'    => gzencode($this->xmlSigner->sign($this->xmlGenerator->generate($fileSignature))),
        ]));

        return $fileSignature;
    }

    /**
     * @param AttachmentSignature $fileSignature
     * @param ResponseInterface   $response
     *
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function handleResponse(AttachmentSignature $fileSignature, ResponseInterface $response): void
    {
        try {
            $this->xmlSigner->verify($response->getContent());
        } catch (\Exception $exception) {
            $this->handlePSNError($fileSignature, sprintf(
                'Exception occurs when verify the signature of XML. Message: %s File Signature id: %d',
                $exception->getMessage(),
                $fileSignature->getId()
            ));
        }

        $xml = new SimpleXMLElement($response->getContent());

        $header = current($xml->xpath('//RETOURdePM'));

        if (false === $header instanceof SimpleXMLElement) {
            $this->handlePSNError($fileSignature, 'Cannot find RETOURdePM field from the PSN response');
        }

        if (200 !== (int) $header['CodeMessage']) {
            $this->handlePSNError($fileSignature, sprintf(
                'The PSN server returns a code other than 200. Code: %d. State: %s. Message: %s',
                (int) $header['CodeMessage'],
                (string) $header['ETAT'],
                (string) $header['LibelleMessage']
            ));
        }

        $content = current($xml->xpath('//RETOURMETIER/RETOURSIGNATUREENTITE'));

        $transactionNumber = (string) $content->TRANSNUM;
        if (empty($transactionNumber)) {
            $this->logger->warning('PSN TRANSNUM is empty', ['file_signature_id' => $fileSignature->getId()]);
        }

        $signatureUrl = (string) $content->URL;
        if (empty($signatureUrl)) {
            $this->handlePSNError($fileSignature, sprintf('PSN callback url is empty. PSN Transaction number : %s', $transactionNumber));
        }

        $fileSignature
            ->setTransactionNumber($transactionNumber)
            ->setSignatureUrl($signatureUrl)
            ->setStatus(AttachmentSignature::STATUS_REQUESTED)
        ;

        $this->attachmentSignatureRepository->save($fileSignature);
    }

    /**
     * @param AttachmentSignature $fileSignature
     * @param string              $message
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function handlePSNError(AttachmentSignature $fileSignature, string $message): void
    {
        $fileSignature->setStatus(AttachmentSignature::STATUS_REQUEST_FAILED);
        $this->attachmentSignatureRepository->save($fileSignature);

        throw new \RuntimeException($message);
    }
}
