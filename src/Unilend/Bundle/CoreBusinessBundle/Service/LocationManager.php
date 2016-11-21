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
                    'longitude' => $response->features[0]->center[0]
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
            $results = $cities->lookupCities($city, ['ville', 'cp'], true);
        } else {
            $results = $cities->lookupCities($city);
        }

        if (false === empty($results)) {
            foreach ($results as $item) {
                if ($lookUpBirthplace) {
                    $cityList[] = [
                        'label' => $item['ville'] . ' (' . $item['num_departement'] . ')',
                        'value' => $item['insee']
                    ];
                } else {
                    $cityList[] = [
                        'label' => $item['ville'] . ' (' . $item['cp'] . ')',
                        'value' => $item['insee']
                    ];
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

        $regions = [];

        if (isset($countByRegion[1]) && is_array($countByRegion[1]) && array_key_exists('insee_region_code', $countByRegion[0])) {
            array_shift($countByRegion);
            $total = array_sum(array_column($countByRegion, 'count'));
        } else {
            $total = array_sum($countByRegion);
        }

        foreach ($countByRegion as $insee => $row) {
            if (is_array($row) && array_key_exists('insee_region_code', $row)) {
                if ($row['insee_region_code'] != 0) {
                    $region = array(
                        'name' => $frenchRegions[$row['insee_region_code']],
                        'insee' => $row['insee_region_code'],
                        'value' => (float)round(bcmul(bcdiv($row['count'], $total, 3) , 100, 1))
                    );
                    $regions[] = $region;
                }
            } else {
                if ($insee > 0) {
                    $region = array(
                        'name' => $frenchRegions[$insee],
                        'insee' => (string)$insee,
                        'value' => (float)round(bcmul(bcdiv($row, $total, 4), 100, 1))
                    );
                    $regions[] = $region;
                }
            }
        }

        return $regions;
    }

    public function getFrenchRegions()
    {
        return [
            '44' => 'Grand Est',
            '75' => 'Nouvelle-Aquitaine',
            '84' => 'Auvergne-Rhône-Alpes',
            '27' => 'Bourgogne-Franche-Comté',
            '53' => 'Bretagne',
            '24' => 'Centre-Val de Loire',
            '94' => 'Corse',
            '01' => 'Guadeloupe',
            '03' => 'Guyane',
            '11' => 'Île-de-France',
            '04' => 'La Réunion',
            '76' => 'Occitanie',
            '02' => 'Martinique',
            '06' => 'Mayotte',
            '32' => 'Hauts-de-France',
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

    public function checkFrenchCity($city, $zip = null)
    {
        /** @var \villes $cities */
        $cities = $this->entityManager->getRepository('villes');

        if (is_null($zip)) {
            return $cities->exist(str_replace(array(' ', '-'), '', $city), 'REPLACE(REPLACE(ville, " ", ""), "-", "")');
        } else {
            return $cities->get($zip . '" AND ville = "' . $city, 'cp');
        }
    }

    public function checkFrenchCityInsee($inseeCode)
    {
        /** @var \villes $cities */
        $cities = $this->entityManager->getRepository('villes');

        return $cities->exist($inseeCode, 'insee');
    }
}
