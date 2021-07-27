<?php

declare(strict_types=1);

namespace Unilend\Core\Service\Hubspot\Client;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class HubspotClient
{
    public string $hubspotApiKey;
    public HttpClientInterface $hubspotClient;

    public function __construct(
        string $hubspotApiKey,
        HttpClientInterface $hubspotClient
    ) {
        $this->hubspotClient = $hubspotClient;
        $this->hubspotApiKey = $hubspotApiKey;
    }

    public function getDailyUsageApi(): ResponseInterface
    {
        return $this->hubspotClient->request('GET', 'https://api.hubapi.com/integrations/v1/limit/daily?', [
            'query' => [
                'hapikey' => $this->hubspotApiKey,
            ],
        ]);
    }
}
