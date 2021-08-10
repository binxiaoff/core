<?php

declare(strict_types=1);

namespace KLS\Core\Service\ElectronicSignature;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use KLS\Core\Entity\FileVersionSignature;
use KLS\Core\Repository\FileVersionSignatureRepository;
use League\Flysystem\FilesystemException;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class RequestSender
{
    private const REQUEST_PATH = 'souscription_ca/sgp';

    private HttpClientInterface $psnClient;
    private LoggerInterface $logger;
    private XmlSigner $xmlSigner;
    private XmlGenerator $xmlGenerator;
    private FileVersionSignatureRepository $fileVersionSignatureRepository;

    public function __construct(
        XmlSigner $xmlSigner,
        XmlGenerator $xmlGenerator,
        FileVersionSignatureRepository $fileVersionSignatureRepository,
        HttpClientInterface $psnClient,
        LoggerInterface $logger
    ) {
        $this->psnClient                      = $psnClient;
        $this->logger                         = $logger;
        $this->xmlSigner                      = $xmlSigner;
        $this->xmlGenerator                   = $xmlGenerator;
        $this->fileVersionSignatureRepository = $fileVersionSignatureRepository;
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws Exception
     * @throws ClientExceptionInterface
     * @throws FilesystemException
     */
    public function requestSignature(FileVersionSignature $fileSignature): FileVersionSignature
    {
        $this->handleResponse($fileSignature, $this->psnClient->request(Request::METHOD_POST, self::REQUEST_PATH, [
            'headers' => ['Content-Type' => 'application/gzip'],
            'body'    => \gzencode($this->xmlSigner->sign($this->xmlGenerator->generate($fileSignature))),
        ]));

        return $fileSignature;
    }

    /**
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    private function handleResponse(FileVersionSignature $fileSignature, ResponseInterface $response): void
    {
        try {
            $this->xmlSigner->verify($response->getContent());
        } catch (\Exception $exception) {
            $this->handlePSNError($fileSignature, \sprintf(
                'Exception occurs when verify the signature of XML. Message: %s File Signature id: %d',
                $exception->getMessage(),
                $fileSignature->getId()
            ));
        }

        $xml = new SimpleXMLElement($response->getContent());

        $header = \current($xml->xpath('//RETOURdePM'));

        if (false === $header instanceof SimpleXMLElement) {
            $this->handlePSNError($fileSignature, 'Cannot find RETOURdePM field from the PSN response');
        }

        if (200 !== (int) $header['CodeMessage']) {
            $this->handlePSNError($fileSignature, \sprintf(
                'The PSN server returns a code other than 200. Code: %d. State: %s. Message: %s',
                (int) $header['CodeMessage'],
                (string) $header['ETAT'],
                (string) $header['LibelleMessage']
            ));
        }

        $content = \current($xml->xpath('//RETOURMETIER/RETOURSIGNATUREENTITE'));

        $transactionNumber = (string) $content->TRANSNUM;
        if (empty($transactionNumber)) {
            $this->logger->warning('PSN TRANSNUM is empty', ['file_signature_id' => $fileSignature->getId()]);
        }

        $signatureUrl = (string) $content->URL;
        if (empty($signatureUrl)) {
            $this->handlePSNError($fileSignature, \sprintf('PSN callback url is empty. PSN Transaction number : %s', $transactionNumber));
        }

        $fileSignature
            ->setTransactionNumber($transactionNumber)
            ->setSignatureUrl($signatureUrl)
            ->setStatus(FileVersionSignature::STATUS_REQUESTED)
        ;

        $this->fileVersionSignatureRepository->save($fileSignature);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function handlePSNError(FileVersionSignature $fileSignature, string $message): void
    {
        $fileSignature->setStatus(FileVersionSignature::STATUS_REQUEST_FAILED);
        $this->fileVersionSignatureRepository->save($fileSignature);

        throw new \RuntimeException($message);
    }
}
