<?php

declare(strict_types=1);

namespace Unilend\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Unilend\CacheKeys;
use Unilend\Entity\{Pays, Villes};

/**
 * Class LocationManager.
 */
class LocationManager
{
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var CacheItemPoolInterface */
    private $cachePool;

    /**
     * @param EntityManagerInterface $entityManager
     * @param CacheItemPoolInterface $cachePool
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        CacheItemPoolInterface $cachePool
    ) {
        $this->entityManager = $entityManager;
        $this->cachePool     = $cachePool;
    }

    /**
     * @throws InvalidArgumentException
     *
     * @return array
     */
    public function getCountries(): array
    {
        $cachedItem = $this->cachePool->getItem('countryList');

        if ($cachedItem->isHit()) {
            return $cachedItem->get();
        }

        $countyList = [];
        $countries  = $this->entityManager->getRepository(Pays::class)->findBy([], ['ordre' => 'ASC']);

        foreach ($countries as $country) {
            $countyList[$country->getIdPays()] = $country->getFr();
        }

        $cachedItem->set($countyList)->expiresAfter(CacheKeys::LONG_TIME);
        $this->cachePool->save($cachedItem);

        return $countyList;
    }

    /**
     * @return array
     */
    public function getFrenchRegions(): array
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
            '93' => 'Provence-Alpes-Côte d\'Azur',
        ];
    }

    /**
     * @param string $postCode
     * @param string $city
     *
     * @return string|null
     */
    public function getInseeCog($postCode, $city): ?string
    {
        $citiesInsee = $this->entityManager->getRepository(Villes::class)->findBy(['cp' => $postCode]);

        if (1 === count($citiesInsee)) {
            return $citiesInsee[0]->getInsee();
        }
        $cityInsee = $this->entityManager->getRepository(Villes::class)->findOneBy(['cp' => $postCode, 'ville' => $this->cleanLookupCityName($city)]);

        if ($cityInsee) {
            return $cityInsee->getInsee();
        }

        return null;
    }

    /**
     * @param $city
     *
     * @return string
     */
    public function cleanLookupCityName($city): string
    {
        $city = str_replace(['\' ', ' D ', ' '], ['\'', ' D\'', '-'], mb_strtoupper(\URLify::downcode($city)));
        // Replace ST, SNT with SAINT
        $city = preg_replace('/(^|.+-)((ST)|(SNT))(-)(.+)/', '$1SAINT$5$6', $city);
        // Replace STE with SAINTE
        $city = preg_replace('/(^|.+-)(STE)(-)(.+)/', '$1SAINTE$3$4', $city);
        // Remove le la les l' from the beginning of the term
        return preg_replace('/^(LE-|LA-|LES-|L\')(.+)/', '$2', $city);
    }

    /**
     * $frenchRegions taken from http://www.insee.fr/fr/methodes/nomenclatures/cog/default.asp.
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
            $region = [
                'name'  => $frenchRegions[$regionDetails['insee_region_code']],
                'insee' => $regionDetails['insee_region_code'],
                'value' => (float) round(bcmul(bcdiv($regionDetails['count'], $total, 4), 100, 4), 1),
            ];
            $regions[] = $region;
        }

        return $regions;
    }
}
