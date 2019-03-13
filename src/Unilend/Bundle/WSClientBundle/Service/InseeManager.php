<?php

namespace Unilend\Bundle\WSClientBundle\Service;

use GuzzleHttp\Client;

class InseeManager
{
    const ENDPOINT_URL = 'https://api.insee.fr/entreprises/sirene/V3/siren/';

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
     * @return string|null
     */
    public function searchSiren(string $siren): ?string
    {
        $response = $this->client->get(self::ENDPOINT_URL . $siren);

        if (200 !== $response->getStatusCode()) {
            return null;
        }

        $content = json_decode($response->getBody()->getContents(), true);

        if (null === $content || empty($content['uniteLegale'])) {
            return null;
        }

        $legalEntity = $content['uniteLegale'];

        if (false === empty($legalEntity['periodesUniteLegale'][0]['denominationUniteLegale'])) {
            return $legalEntity['periodesUniteLegale'][0]['denominationUniteLegale'];
        }

        if (
            false === empty($legalEntity['prenom1UniteLegale'])
            && false === empty($legalEntity['periodesUniteLegale'][0]['nomUniteLegale'])
        ) {
            return trim($legalEntity['prenom1UniteLegale'] . ' ' . $legalEntity['periodesUniteLegale'][0]['nomUniteLegale']);
        }

        return null;
    }
}
