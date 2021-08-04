<?php

declare(strict_types=1);

namespace Unilend\Core\Service\Hubspot\Client;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class HubspotClient
{
    private const GET_DAILY_USAGE_URL = 'v1/limit/daily?';

    public HttpClientInterface $hubspotIntegrationClient;

    public function __construct(
        HttpClientInterface $hubspotIntegrationClient
    ) {
        $this->hubspotIntegrationClient = $hubspotIntegrationClient;
    }

    public function getDailyUsageApi(): ResponseInterface
    {
        return $this->hubspotIntegrationClient->request('GET', self::GET_DAILY_USAGE_URL, []);
    }
}
