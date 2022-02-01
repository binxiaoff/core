<?php

declare(strict_types=1);

namespace KLS\Core\Service\WebServiceClient;

use JsonException;
use KLS\Core\Entity\Company;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class InseeManager
{
    private HttpClientInterface $inseeClient;

    public function __construct(HttpClientInterface $inseeClient)
    {
        $this->inseeClient = $inseeClient;
    }

    /**
     * @throws JsonException
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     *
     * @return array|Company[]
     */
    public function searchByName(string $name, int $limit = 5): array
    {
        $name     = \str_replace(' ', '-', \trim($name));
        $response = $this->inseeClient->request('GET', "?nombre={$limit}&q=periode(denominationUniteLegale:'{$name}')");

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            return [];
        }

        $content = \json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        if (null === $content || empty($content['unitesLegales'])) {
            return [];
        }

        $companies = [];

        foreach ($content['unitesLegales'] as $legalEntity) {
            if ($company = $this->extractSirenAndName($legalEntity)) {
                $companies[] = $company;
            }
        }

        return $companies;
    }

    /**
     * @throws ClientExceptionInterface
     * @throws JsonException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function searchBySirenNumber(string $siren): ?array
    {
        $response = $this->inseeClient->request('GET', $siren);

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            return null;
        }

        $content = \json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        if (null === $content || empty($content['uniteLegale'])) {
            return null;
        }

        return $this->extractSirenAndName($content['uniteLegale']);
    }

    /**
     * @return mixed
     */
    public function extractSirenAndName(array $legalEntity): ?array
    {
        $siren = $legalEntity['siren'];

        if (false === empty($legalEntity['periodesUniteLegale'][0]['denominationUniteLegale'])) {
            $name = $legalEntity['periodesUniteLegale'][0]['denominationUniteLegale'];

            return ['name' => $name, 'siren' => $siren];
        }

        if (
            false === empty($legalEntity['prenom1UniteLegale'])
            && false === empty($legalEntity['periodesUniteLegale'][0]['nomUniteLegale'])
        ) {
            $name = \trim(
                $legalEntity['prenom1UniteLegale'] . ' ' .
                $legalEntity['periodesUniteLegale'][0]['nomUniteLegale']
            );

            return ['name' => $name, 'siren' => $siren];
        }

        return null;
    }
}
