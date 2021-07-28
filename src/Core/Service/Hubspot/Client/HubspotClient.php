<?php

declare(strict_types=1);

namespace Unilend\Core\Service\Hubspot\Client;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class HubspotClient
{
    private const GET_DAILY_USAGE_URL = 'v1/limit/daily?';
    public const CONTACTS_LIMIT = 5;

    private const GET_DAILY_USAGE_URL = 'https://api.hubapi.com/integrations/v1/limit/daily?';
    private const GET_CONTACTS_URL    = 'contact';

    public HttpClientInterface $hubspotIntegrationClient;
    public string $hubspotApiKey;
    public HttpClientInterface $hubspotClient;
    private LoggerInterface $logger;

    public function __construct(
        HttpClientInterface $hubspotIntegrationClient
        string $hubspotApiKey,
        HttpClientInterface $hubspotClient,
        LoggerInterface $logger
    ) {
        $this->hubspotIntegrationClient = $hubspotIntegrationClient;
        $this->hubspotClient = $hubspotClient;
        $this->hubspotApiKey = $hubspotApiKey;
        $this->logger        = $logger;
    }

    public function getDailyUsageApi(): ResponseInterface
    {
        return $this->hubspotIntegrationClient->request('GET', self::GET_DAILY_USAGE_URL, []);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function fetchAllContacts(?array $params = []): ResponseInterface
    {
        $queryParams = [
            'limit'    => 2,
            'archived' => 'false',
            'hapikey'  => $this->hubspotApiKey,
        ];
        foreach ($params as $paramKey => $paramValue) {
            $queryParams[$paramKey] = $paramValue;
        }

        return $this->hubspotClient->request('GET', self::GET_CONTACTS_URL, [
            'query' => $queryParams,
        ]);
    }
}
