<?php

declare(strict_types=1);

namespace Unilend\Service\WebServiceClient;

use GuzzleHttp\Client;

class InseeManager
{
    private const ENDPOINT_URL = 'https://api.insee.fr/entreprises/sirene/V3/siren';

    /** @var Client */
    private $client;

    /**
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param string $siren
     *
     * @return array
     */
    public function searchByName(string $siren): ?array
    {
        $siren     = str_replace(' ', '-', trim($siren));
        $response  = $this->client->get(self::ENDPOINT_URL . '?nombre=5&q=periode(denominationUniteLegale:' . $siren . ')');
        $companies = [];

        if (200 !== $response->getStatusCode()) {
            return null;
        }

        $content = json_decode($response->getBody()->getContents(), true);

        if (null === $content || empty($content['unitesLegales'])) {
            return null;
        }

        foreach ($content['unitesLegales'] as $legalEntity) {
            $company = $this->extractSirenAndName($legalEntity);
            if ($company) {
                array_push($companies, $company);
            }
        }

        return $companies ? $companies : null;
    }

    /**
     * @param string $siren
     *
     * @return array|null
     */
    public function searchBySirenNumber(string $siren): ?array
    {
        $response = $this->client->get(self::ENDPOINT_URL . '/' . $siren);

        if (200 !== $response->getStatusCode()) {
            return null;
        }

        $content = json_decode($response->getBody()->getContents(), true);

        if (null === $content || empty($content['uniteLegale'])) {
            return null;
        }

        return $this->extractSirenAndName($content['uniteLegale']);
    }

    /**
     * @param array $legalEntity
     *
     * @return mixed
     */
    public function extractSirenAndName(array $legalEntity): ?array
    {
        $company['siren'] = $legalEntity['siren'];

        if (false === empty($legalEntity['periodesUniteLegale'][0]['denominationUniteLegale'])) {
            $company['name'] = $legalEntity['periodesUniteLegale'][0]['denominationUniteLegale'];
        } elseif (
            false === empty($legalEntity['prenom1UniteLegale'])
            && false === empty($legalEntity['periodesUniteLegale'][0]['nomUniteLegale'])
        ) {
            $company['name'] = trim($legalEntity['prenom1UniteLegale'] . ' ' . $legalEntity['periodesUniteLegale'][0]['nomUniteLegale']);
        }

        if (false === empty($company['name']) && false === empty($company['siren'])) {
            return $company;
        }

        return null;
    }
}
