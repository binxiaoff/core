<?php

declare(strict_types=1);

namespace Unilend\Core\Service\Hubspot;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Unilend\Core\Service\Hubspot\Client\HubspotClient;

class HubspotManager
{
    private HubspotClient $hubspotIntegrationClient;

    public function __construct(
        HubspotClient $hubspotIntegrationClient
    ) {
        $this->hubspotIntegrationClient = $hubspotIntegrationClient;
    }

    /**
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getDailyApiUsage(): array
    {
        $response = $this->hubspotIntegrationClient->getDailyUsageApi();

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            return [];
        }

        return \json_decode($response->getContent(), true);
    }
}
