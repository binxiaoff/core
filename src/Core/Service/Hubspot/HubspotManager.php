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
    private HubspotClient $hubspotClient;

    public function __construct(
        HubspotClient $hubspotClient
    ) {
        $this->hubspotClient = $hubspotClient;
    }

    /**
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getDailyApiUsage(): array
    {
        $response = $this->hubspotClient->getDailyUsageApi();

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            return [];
        }

        return \json_decode($response->getContent(), true);
    }
}
