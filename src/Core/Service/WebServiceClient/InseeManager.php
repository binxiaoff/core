<?php

declare(strict_types=1);

namespace Unilend\Core\Service\WebServiceClient;

use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Response;
use Unilend\Core\Entity\Company;

class InseeManager
{
    private const ENDPOINT_URL = 'https://api.insee.fr/entreprises/sirene/V3/siren';

    /** @var Client */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @return array|Company[]
     */
    public function searchByName(string $name, int $limit = 5): array
    {
        $name     = \str_replace(' ', '-', \trim($name));
        $response = $this->client->get(self::ENDPOINT_URL . "?nombre={$limit}&q=periode(denominationUniteLegale:'{$name}')");

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            return [];
        }

        $content = \json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

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

    public function searchBySirenNumber(string $siren): ?array
    {
        $response = $this->client->get(self::ENDPOINT_URL . '/' . $siren);

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            return null;
        }

        $content = \json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

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
            $name = \trim($legalEntity['prenom1UniteLegale'] . ' ' . $legalEntity['periodesUniteLegale'][0]['nomUniteLegale']);

            return ['name' => $name, 'siren' => $siren];
        }

        return null;
    }
}
