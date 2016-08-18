<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Cache\Adapter\Memcache\MemcacheCachePool;
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

    /** @var MemcacheCachePool */
    private $cachePool;

    public function __construct(EntityManager $entityManager, $mapboxToken, MemcacheCachePool $cachePool)
    {
        $this->entityManager = $entityManager;
        $this->mapboxToken   = $mapboxToken;
        $this->cachePool     = $cachePool;
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

    /**
     * @param string $city
     * @param bool $lookUpBirthplace
     * @return array
     */
    public function getCities($city, $lookUpBirthplace = false)
    {
        $cityList = [];
        /** @var \villes $cities */
        $cities = $this->entityManager->getRepository('villes');

        if ($lookUpBirthplace) {
            $results = $cities->lookupCities($city, array('ville', 'cp'), true);
        } else {
            $results = $cities->lookupCities($city);
        }

        if (false === empty($results)) {
            foreach ($results as $item) {
                if ($lookUpBirthplace) {
                    // unique insee code
                    $cityList[$item['insee'].'-'.$item['ville']] = array(
                        'label' => $item['ville'] . ' (' . $item['num_departement'] . ')',
                        'value' => $item['insee']
                    );
                } else {
                    $cityList[] = array(
                        'label' => $item['ville'] . ' (' . $item['cp'] . ')',
                        'value' => $item['insee']
                    );
                }
            }
        }
        return $cityList;
    }

    public function getCountries()
    {
        $cachedItem = $this->cachePool->getItem('countryList');

        if (false === $cachedItem->isHit()) {
            /** @var \pays_v2 $countries */
            $countries = $this->entityManager->getRepository('pays_v2');
            /** @var array $countyList */
            $countyList = [];

            foreach ($countries->select('', 'ordre ASC') as $country) {
                $countyList[$country['id_pays']] = $country['fr'];
            }

            $cachedItem->set($countyList)->expiresAfter(3600);
            $this->cachePool->save($cachedItem);
            return $countyList;
        } else {
            return $cachedItem->get();
        }
    }

    public function getNationalities()
    {
        $cachedItem = $this->cachePool->getItem('nationalityList');

        if (false === $cachedItem->isHit()) {

            /** @var \nationalites_v2 $nationalities */
            $nationalities = $this->entityManager->getRepository('nationalites_v2');
            /** @var array $nationalityList */
            $nationalityList = [];

            foreach ($nationalities->select('', 'ordre ASC') as $nationality) {
                $nationalityList[$nationality['id_nationalite']] = $nationality['fr_f'];
            }

            $cachedItem->set($nationalityList)->expiresAfter(3600);
            $this->cachePool->save($cachedItem);
            return $nationalityList;
        } else {
            return $cachedItem->get();
        }
    }

    /**
     * $frenchRegions taken from http://www.insee.fr/fr/methodes/nomenclatures/cog/default.asp

     *
     * @param array $countByRegion
     * @return array
     */
    private function getPercentageByRegion($countByRegion)
    {
        $frenchRegions = $this->getFrenchRegions();

        if (isset($countByRegion['0'])) {
            unset($countByRegion['0']);
        }

        $regions = [];

        $total = array_sum($countByRegion);
        foreach ($countByRegion as $inseeRegionCode => $count) {

            if (array_key_exists($inseeRegionCode, $frenchRegions)) {
                $regions[$inseeRegionCode] = [
                    'count'      => $count,
                    'percentage' => bcdiv($count, $total, 2)
                ];
            } else {
                $regions[$inseeRegionCode] = [
                    'count'      => 0,
                    'percentage' => 0
                ];
            }
            $regions[$inseeRegionCode]['name'] = $frenchRegions[$inseeRegionCode];
        }

        return $regions;
    }

    public function getFrenchRegions()
    {
        return [
            '44' => 'Alsace-Champagne-Ardenne-Lorraine',
            '75' => 'Aquitaine-Limousin-Poitou-Charentes',
            '84' => 'Auvergne-Rhône-Alpes',
            '27' => 'Bourgogne-Franche-Comté',
            '53' => 'Bretagne',
            '24' => 'Centre-Val de Loire',
            '94' => 'Corse',
            '01' => 'Guadeloupe',
            '03' => 'Guyane',
            '11' => 'Île-de-France',
            '04' => 'La Réunion',
            '76' => 'Languedoc-Roussillon-Midi-Pyrénées',
            '02' => 'Martinique',
            '06' => 'Mayotte',
            '32' => 'Nord-Pas-de-Calais-Picardie',
            '28' => 'Normandie',
            '52' => 'Pays de la Loire',
            '93' => 'Provence-Alpes-Côte d\'Azur'
        ];
    }

    public function getLendersByRegion()
    {
        /** @var \clients $clients */
        $clients = $this->entityManager->getRepository('clients');
        $countByRegion = $clients->countClientsByRegion();

        return $this->getPercentageByRegion($countByRegion);
    }

    public function getProjectsByRegion()
    {
        /** @var \projects $projects */
        $projects = $this->entityManager->getRepository('projects');
        $countByRegion = $projects->countProjectsByRegion();

        return $this->getPercentageByRegion($countByRegion);
    }
}
