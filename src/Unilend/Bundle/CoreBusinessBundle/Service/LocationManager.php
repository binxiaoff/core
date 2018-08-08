<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Psr\Cache\CacheItemPoolInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{CompanyAddress, Pays};
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;

/**
 * Class LocationManager
 * @package Unilend\Bundle\CoreBusinessBundle\Service
 */
class LocationManager
{
    /** @var EntityManagerSimulator */
    private $entityManagerSimulator;
    /** @var EntityManager */
    private $entityManager;
    /** @var string */
    private $mapboxToken;
    /** @var CacheItemPoolInterface */
    private $cachePool;

    /**
     * @param EntityManager          $entityManager
     * @param EntityManagerSimulator $entityManagerSimulator
     * @param string                 $mapboxToken
     * @param CacheItemPoolInterface $cachePool
     */
    public function __construct(
        EntityManager $entityManager,
        EntityManagerSimulator $entityManagerSimulator,
        string $mapboxToken,
        CacheItemPoolInterface $cachePool
    )
    {
        $this->entityManager          = $entityManager;
        $this->entityManagerSimulator = $entityManagerSimulator;
        $this->mapboxToken            = $mapboxToken;
        $this->cachePool              = $cachePool;
    }

    /**
     * @param CompanyAddress $companyAddress
     *
     * @return float[]|null
     */
    public function getCompanyCoordinates(CompanyAddress $companyAddress)
    {
        return $this->getMapboxGeocoding($companyAddress->getCity(), $companyAddress->getZip(), $companyAddress->getIdCountry()->getIdPays());
    }

    /**
     * @param string $city
     * @param string $postCode
     * @param int    $countryId
     *
     * @return float[]|null [Latitude, Longitude]
     */
    private function getMapboxGeocoding($city, $postCode, $countryId)
    {
        if (empty($countryId)) {
            $countryId = Pays::COUNTRY_FRANCE;
        }

        $country = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Pays')->find($countryId);

        if (null !== $country) {
            $curl = curl_init('https://api.mapbox.com/geocoding/v5/mapbox.places/' . urlencode($city . ' ' . $postCode . ' ' . $country->getFr()) . '.json?access_token=' . $this->mapboxToken);

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
     * @param bool   $lookUpBirthplace
     *
     * @return array
     */
    public function getCities($city, $lookUpBirthplace = false)
    {
        $cityList = [];
        /** @var \villes $cities */
        $cities = $this->entityManagerSimulator->getRepository('villes');

        if ($lookUpBirthplace) {
            $results = $cities->lookupCities($city, ['ville', 'cp'], true);
        } else {
            $results = $cities->lookupCities($city);
        }

        if (false === empty($results)) {
            foreach ($results as $item) {
                if ($lookUpBirthplace) {
                    $cityList[$item['insee']] = [
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

        return array_values($cityList);
    }

    /**
     * @return array
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getCountries(): array
    {
        $cachedItem = $this->cachePool->getItem('countryList');

        if ($cachedItem->isHit()) {
            return $cachedItem->get();
        }

        $countyList = [];
        $countries  = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Pays')->findBy([], ['ordre' => 'ASC']);

        foreach ($countries as $country) {
            $countyList[$country->getIdPays()] = $country->getFr();
        }

        $cachedItem->set($countyList)->expiresAfter(3600);
        $this->cachePool->save($cachedItem);

        return $countyList;
    }

    public function getNationalities()
    {
        $cachedItem = $this->cachePool->getItem('nationalityList');

        if ($cachedItem->isHit()) {
            return $cachedItem->get();
        }

        /** @var \nationalites_v2 $nationalities */
        $nationalities = $this->entityManagerSimulator->getRepository('nationalites_v2');
        /** @var array $nationalityList */
        $nationalityList = [];

        foreach ($nationalities->select('', 'ordre ASC') as $nationality) {
            $nationalityList[$nationality['id_nationalite']] = $nationality['fr_f'];
        }

        $cachedItem->set($nationalityList)->expiresAfter(3600);
        $this->cachePool->save($cachedItem);

        return $nationalityList;
    }

    /**
     * $frenchRegions taken from http://www.insee.fr/fr/methodes/nomenclatures/cog/default.asp
     *
     * @param array $countByRegion
     *
     * @return array
     */
    private function getPercentageByRegion($countByRegion)
    {

        $frenchRegions = $this->getFrenchRegions();

        $regions = [];
        $total   = array_sum(array_column($countByRegion, 'count'));

        foreach ($countByRegion as $regionDetails) {
            $region    = array(
                'name'  => $frenchRegions[$regionDetails['insee_region_code']],
                'insee' => $regionDetails['insee_region_code'],
                'value' => (float) round(bcmul(bcdiv($regionDetails['count'], $total, 4), 100, 4), 1)
            );
            $regions[] = $region;
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
        $clients       = $this->entityManagerSimulator->getRepository('clients');
        $countByRegion = $clients->countClientsByRegion();

        return $this->getPercentageByRegion($countByRegion);
    }

    public function getProjectsByRegion()
    {
        /** @var \projects $projects */
        $projects      = $this->entityManagerSimulator->getRepository('projects');
        $countByRegion = $projects->countProjectsByRegion();

        return $this->getPercentageByRegion($countByRegion);
    }

    public function checkFrenchCity($city, $zip = null)
    {
        /** @var \villes $cities */
        $cities = $this->entityManagerSimulator->getRepository('villes');

        if (is_null($zip)) {
            return $cities->exist(str_replace(array(' ', '-'), '', $city), 'REPLACE(REPLACE(ville, " ", ""), "-", "")');
        } else {
            return $cities->get($zip . '" AND ville = "' . $city, 'cp');
        }
    }

    public function checkFrenchCityInsee($inseeCode)
    {
        /** @var \villes $cities */
        $cities = $this->entityManagerSimulator->getRepository('villes');

        return $cities->exist($inseeCode, 'insee');
    }

    /**
     * @param string $postCode
     * @param string $city
     *
     * @return bool|string
     */
    public function getInseeCode($postCode, $city)
    {
        $citiesInsee = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Villes')->findBy(['cp' => $postCode]);

        if (1 === count($citiesInsee)) {
            return $citiesInsee[0]->getInsee();
        } else {
            $cityInsee = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Villes')->findOneBy(['cp' => $postCode, 'ville' => $this->cleanLookupCityName($city)]);

            if ($cityInsee) {
                return $cityInsee->getInsee();
            }
        }

        return false;
    }

    /**
     * @param $city
     *
     * @return string
     */
    public function cleanLookupCityName($city)
    {
        $city = str_replace(['\' ',' D ', ' '], ['\'', ' D\'', '-'], strtoupper(\ficelle::speCharNoAccent($city)));
        // Replace ST, SNT with SAINT
        $city = preg_replace('/(^|.+-)((ST)|(SNT))(-)(.+)/', '$1SAINT$5$6', $city);
        // Replace STE with SAINTE
        $city = preg_replace('/(^|.+-)(STE)(-)(.+)/', '$1SAINTE$3$4', $city);
        // Remove le la les l' from the beginning of the term
        $city = preg_replace('/^(LE-|LA-|LES-|L\')(.+)/', '$2', $city);

        return $city;
    }
}
