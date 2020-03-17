<?php

declare(strict_types=1);

namespace Unilend\Service\Psn;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RequestSender
{
    private const REQUEST_PATH = 'souscription_ca/sgp';
    /**
     * @var HttpClientInterface
     */
    private $psnClient;

    /**
     * @param HttpClientInterface $psnClient
     */
    public function __construct(HttpClientInterface $psnClient)
    {
        $this->psnClient = $psnClient;
    }

    /**
     * @param string $xml
     *
     * @throws TransportExceptionInterface
     */
    public function requestSignature(string $xml)
    {
        $options = [
            'headers' => ['Content-Type' => 'application/gzip'],
            'body'    => gzencode($xml),
        ];

        $response = $this->psnClient->request(Request::METHOD_POST, self::REQUEST_PATH, $options);
    }
}
