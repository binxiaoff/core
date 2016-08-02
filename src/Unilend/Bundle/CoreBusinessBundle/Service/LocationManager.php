<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

/**
 * Class LocationManager
 * @package Unilend\Bundle\CoreBusinessBundle\Service
 */

class LocationManager
{
    /** @var EntityManager */
    private $entityManager;

    /** @var string */
    private $mapboxToken;

    public function __construct(EntityManager $entityManager, $mapboxToken)
    {
        $this->entityManager = $entityManager;
        $this->mapboxToken   = $mapboxToken;
    }

    /**
     * @param \companies $company
     * @return float[]|null [Latitude, Longitude]
     */
    public function getCompanyCoordinates(\companies $company)
    {
        return $this->getMapboxGeocoding($company->city, $company->zip, $company->id_pays);
    }

    /**
     * @param string $city
     * @param string $postCode
     * @param int    $countryId
     * @return float[]|null [Latitude, Longitude]
     */
    private function getMapboxGeocoding($city, $postCode, $countryId)
    {
        if (0 == $countryId) {
            $countryId = 1;
        }

        /** @var \pays_v2 $country */
        $country = $this->entityManager->getRepository('pays_v2');

        if ($country->get($countryId)) {
            $curl = curl_init('https://api.mapbox.com/geocoding/v5/mapbox.places/' . urlencode($city . ' ' . $postCode . ' ' . $country->fr) . '.json?access_token=' . $this->mapboxToken);

            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_TIMEOUT, 10);

            $response = curl_exec($curl);

            curl_close($curl);

            if ($response && ($response = json_decode($response)) && false === empty($response->features)) {
                return [
                    'latitude'  => $response->features[0]->center[1],
                    'longitude' =>$response->features[0]->center[0]
                ];
            }
        }

        return null;
    }
}
