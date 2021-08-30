<?php

declare(strict_types=1);

namespace KLS\Core\Service\Hubspot\Client;

use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class HubspotClient
{
    public const RESULT_PER_PAGE       = 100; // Maximum authorised
    private const GET_DAILY_USAGE_PATH = 'v1/limit/daily?';
    private const CONTACTS_PATH        = 'contact';
    private const COMPANY_PATH         = 'companies';

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
        return $this->hubspotIntegrationClient->request('GET', self::GET_DAILY_USAGE_PATH, []);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function fetchAllContacts(int $afterContactId = 0): ResponseInterface
    {
        $queryParams = [
            'limit'    => self::RESULT_PER_PAGE,
            'archived' => 'false',
            'after'    => $afterContactId,
        ];

        return $this->hubspotCrmClient->request('GET', self::CONTACTS_PATH, [
            'query' => $queryParams,
        ]);
    }

    public function postNewContact(array $data): ResponseInterface
    {
        return $this->hubspotCrmClient->request('POST', self::CONTACTS_PATH, [
            'json' => $data,
        ]);
    }

    public function updateContact(int $contactId, array $data): ResponseInterface
    {
        return $this->hubspotCrmClient->request('PATCH', self::CONTACTS_PATH . '/' . $contactId, [
            'json' => $data,
        ]);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function fetchAllCompanies(?int $afterCompanyId = null): ResponseInterface
    {
        $queryParams = [
            'limit'      => self::RESULT_PER_PAGE,
            'archived'   => 'false',
            'after'      => $afterCompanyId,
            'properties' => 'kls_short_code',
        ];

        return $this->hubspotCrmClient->request('GET', self::COMPANY_PATH, [
            'query' => $queryParams,
        ]);
    }
}
