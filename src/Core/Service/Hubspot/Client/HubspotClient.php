<?php

declare(strict_types=1);

namespace KLS\Core\Service\Hubspot\Client;

use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class HubspotClient
{
    public const CONTACTS_LIMIT       = 100; // Maximum authorised
    private const GET_DAILY_USAGE_URL = 'v1/limit/daily?';
    private const CONTACTS_URL        = 'contact';

    public HttpClientInterface $hubspotIntegrationClient;
    public HttpClientInterface $hubspotCrmClient;

    public function __construct(
        HttpClientInterface $hubspotIntegrationClient,
        HttpClientInterface $hubspotCrmClient
    ) {
        $this->hubspotIntegrationClient = $hubspotIntegrationClient;
        $this->hubspotCrmClient         = $hubspotCrmClient;
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function getDailyUsageApi(): ResponseInterface
    {
        return $this->hubspotIntegrationClient->request('GET', self::GET_DAILY_USAGE_URL, []);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function fetchAllContacts(int $afterContactId = 0): ResponseInterface
    {
        $queryParams = [
            'limit'    => self::CONTACTS_LIMIT,
            'archived' => 'false',
            'after'    => $afterContactId,
        ];

        return $this->hubspotCrmClient->request('GET', self::CONTACTS_URL, [
            'query' => $queryParams,
        ]);
    }

    public function postNewContact(array $data): ResponseInterface
    {
        return $this->hubspotCrmClient->request('POST', self::CONTACTS_URL, [
            'json' => $data,
        ]);
    }

    public function updateContact(int $contactId, array $data): ResponseInterface
    {
        return $this->hubspotCrmClient->request('PATCH', self::CONTACTS_URL . '/' . $contactId, [
            'json' => $data,
        ]);
    }
}
