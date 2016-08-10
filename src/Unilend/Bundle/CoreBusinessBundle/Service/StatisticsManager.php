<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Cache\Adapter\Memcache\MemcacheCachePool;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\Bundle\FrontBundle\Service\MapsService;

class StatisticsManager
{
    /** @var  EntityManager */
    private $entityManager;
    /** @var  IRRManager */
    private $IRRManager;
    /** @var  mapsService */
    private $mapsService;
    /** @var MemcacheCachePool */
    private $cachePool;

    public function __construct(EntityManager $entityManager, IRRManager $IRRManager, MapsService $mapsService, MemcacheCachePool $cachePool)
    {
        $this->entityManager = $entityManager;
        $this->IRRManager    = $IRRManager;
        $this->mapsService   = $mapsService;
        $this->cachePool     = $cachePool;
    }

    public function getAllStatistics()
    {
        return [
            'numberProjects'                 => 260,
            'numberProjectRequest'           => 1587952,
            'numberLenders'                  => 32930,
            'amountBorrowedInMillions'       => 19,
            'amountBorrowed'                 => 19516150,
            'irrUnilend'                     => 4.73,
            'avgFundingTime'                 => [
                'days'     => 11,
                'hours'    => 22,
                'minutes'  => 40,
                'unixtime' => 2864000,
            ],
            'percentageSuccessFullyFunded'   => 96,
            'numberActiveLenders'            => 10271,
            'lendersByType'                  => [
                'person'      => [
                    'count'      => 31393,
                    'percentage' => 95
                ],
                'legalEntity' => [
                    'count'      => 624,
                    'percentage' => 2
                ],
                'active'      => 10271,
                'community'   => 32930
            ],
            'lenderCountByRegion'            => [
                0  => [
                    'region'     => 'Auvergne-Rhone-Alpes',
                    'count'      => 2857,
                    'percentage' => 10
                ],
                1  => [
                    'region'     => 'Bourgogne-Franche-Comté',
                    'count'      => 2709,
                    'percentage' => 10
                ],
                2  => [
                    'region'     => 'Bretagne',
                    'count'      => 1896,
                    'percentage' => 7
                ],
                3  => [
                    'region'     => 'Centre',
                    'count'      => 2522,
                    'percentage' => 9
                ],
                4  => [
                    'region'     => 'Champane-Ardenne-Lorraine-Alsace',
                    'count'      => 3195,
                    'percentage' => 11
                ],
                5  => [
                    'region'     => 'Corse',
                    'count'      => 471,
                    'percentage' => 2
                ],
                6  => [
                    'region'     => 'Ile-de-France',
                    'count'      => 430,
                    'percentage' => 2
                ],
                7  => [
                    'region'     => 'Midi-Pyrénées-Languedoc-Roussillon',
                    'count'      => 4632,
                    'percentage' => 17
                ],
                8  => [
                    'region'     => 'Nord-Pas-de-Calais-Picardie',
                    'count'      => 1043,
                    'percentage' => 4
                ],
                9  => [
                    'region'     => 'Normandie',
                    'count'      => 1709,
                    'percentage' => 6
                ],
                10 => [
                    'region'     => 'not-in-France',
                    'count'      => 197,
                    'percentage' => 1
                ],
                11 => [
                    'region'     => 'Pays-de-la-Loire',
                    'count'      => 1200,
                    'percentage' => 4
                ],
                12 => [
                    'region'     => 'Poitou-Charentes-Limousin-Aquitaine',
                    'count'      => 4456,
                    'percentage' => 16
                ],
                13 => [
                    'region'     => 'Provence-Alpes-Côte-dAzur',
                    'count'      => 718,
                    'percentage' => 3
                ]
            ],
            'averageNumberLendersPerProject' => 449,
            'borrowerCountByRegion'          => [
                0 => [
                    'region'     => 'Auvergne-Rhone-Alpes',
                    'count'      => 3,
                    'percentage' => 23
                ],
                1 => [
                    'region'     => 'Bourgogne-Franche-Comté',
                    'count'      => 3,
                    'percentage' => 23
                ],
                2 => [
                    'region'     => 'Centre',
                    'count'      => 1,
                    'percentage' => 8
                ],
                3 => [
                    'region'     => 'Ile-de-France',
                    'count'      => 1,
                    'percentage' => 8
                ],
                4 => [
                    'region'     => 'Midi-Pyrénées-Languedoc-Roussillon',
                    'count'      => 2,
                    'percentage' => 15
                ],
                5 => [
                    'region'     => 'not-in-France',
                    'count'      => 1,
                    'percentage' => 8
                ],
                6 => [
                    'region'     => 'Poitou-Charentes-Limousin-Aquitaine',
                    'count'      => 1,
                    'percentage' => 8
                ],
                7 => [
                    'region'     => 'Provence-Alpes-Côte-dAzur',
                    'count'      => 1,
                    'percentage' => 8
                ]
            ]
        ];

        // @todo hidden because of performance issue
        $borrowedAmount = $this->getAmountBorrowed();
        $aStatistics    = [
            'numberProjects'                 => $this->getNumberOfFinancedProjects(),
            'numberProjectRequest'           => 1587952,
            'numberLenders'                  => $this->getNumberOfLenders(),
            //TODO replace in code with lendersByType
            'amountBorrowedInMillions'       => bcdiv($borrowedAmount, 1000000),
            'amountBorrowed'                 => $borrowedAmount,
            'irrUnilend'                     => $this->getUnilendIRR(),
            'avgFundingTime'                 => $this->getAverageFundingTime(),
            'percentageSuccessFullyFunded'   => $this->getPercentageSuccessfullyFinancedProjects(),
            'numberActiveLenders'            => $this->getNumberOfActiveLenders(),
            //TODO replace in code with lendersByType
            'lendersByType'                  => $this->getLendersByType(),
            'lenderCountByRegion'            => $this->getLendersByRegion(),
            'averageNumberLendersPerProject' => $this->getAverageNumberOfLenders(),
            'borrowerCountByRegion'          => $this->getBorrowersByRegion()

        ];

        return $aStatistics;
    }

    /**
     * @param string $name
     */
    public function getStatistic($name)
    {
        $function  = 'get' . ucfirst($name);
        return call_user_func([$this, $function]);
    }

    public function getNumberOfLendersInCommunity()
    {
        $cachedItem = $this->cachePool->getItem('numberOfLendersInCommunity');

        if (false === $cachedItem->isHit()) {
            /** @var \lenders_accounts $lenders */
            $lenders = $this->entityManager->getRepository('lenders_accounts');
            /** @var int $numberOfLendersInCommunity */
            $numberOfLendersInCommunity = $lenders->countLenders();
            $cachedItem->set($numberOfLendersInCommunity)->expiresAfter(86400);
            $this->cachePool->save($cachedItem);

            return $numberOfLendersInCommunity;
        } else {
            return $cachedItem->get();
        }
    }

    public function getNumberOfActiveLenders()
    {
        $cachedItem = $this->cachePool->getItem('numberOfActiveLenders');

        if (false === $cachedItem->isHit()) {
            /** @var \lenders_accounts $lenders */
            $lenders = $this->entityManager->getRepository('lenders_accounts');
            /** @var int $numberOfActiveLenders */
            $numberOfActiveLenders = $lenders->countLenders(true);
            $cachedItem->set($numberOfActiveLenders)->expiresAfter(86400);
            $this->cachePool->save($cachedItem);

            return $numberOfActiveLenders;
        } else {
            return $cachedItem->get();
        }
    }

    public function getNumberOfFinancedProjects()
    {
        $cachedItem = $this->cachePool->getItem('numberOfFinancedProjects');

        if (false === $cachedItem->isHit()) {
            /** @var \projects $projects */
            $projects = $this->entityManager->getRepository('projects');
            /** @var int $numberOfProjects */
            $numberOfProjects = $projects->countSelectProjectsByStatus(implode(',', \projects_status::$afterRepayment));
            $cachedItem->set($numberOfProjects)->expiresAfter(3600);
            $this->cachePool->save($cachedItem);

            return $numberOfProjects;
        } else {
            return $cachedItem->get();
        }
    }

    public function getAmountBorrowed()
    {
        $cachedItem = $this->cachePool->getItem('amountBorrowed');

        if (false === $cachedItem->isHit()) {
            /** @var \transactions $transactions */
            $transactions    = $this->entityManager->getRepository('transactions');
            /** @var int $amountBorrowed */
            $amountBorrowed = bcdiv($transactions->sum('type_transaction = ' . \transactions_types::TYPE_BORROWER_BANK_TRANSFER_CREDIT, 'montant_unilend-montant'), 100);
            $cachedItem->set($amountBorrowed)->expiresAfter(3600);
            $this->cachePool->save($cachedItem);

            return $amountBorrowed;
        } else {
            return $cachedItem->get();
        }
    }

    public function getUnilendIRR()
    {
        $cachedItem = $this->cachePool->getItem('unilendIRR');

        if (false === $cachedItem->isHit()) {
            /** @var array $lastUnilendIRR */
            $lastUnilendIRR = $this->IRRManager->getLastUnilendIRR();
            $cachedItem->set($lastUnilendIRR['value'])->expiresAfter(86400);
            $this->cachePool->save($cachedItem);

            return $lastUnilendIRR['value'];
        } else {
            return $cachedItem->get();
        }
    }

    public function getPercentageSuccessfullyFinancedProjects()
    {
        $cachedItem = $this->cachePool->getItem('percentageSuccessfullyFinancedProjects');

        if (false === $cachedItem->isHit()) {
            /** @var \projects_status_history $projectStatusHistory */
            $projectStatusHistory = $this->entityManager->getRepository('projects_status_history');
            /** @var array $countByStatus */
            $countByStatus = $projectStatusHistory->countProjectsHavingHadStatus([\projects_status::EN_FUNDING, \projects_status::FUNDE]);
            /** @var string $percentageSuccessfullyFunded */
            $percentageSuccessfullyFunded = bcmul(bcdiv($countByStatus[\projects_status::FUNDE], $countByStatus[\projects_status::EN_FUNDING], 4), 100);
            $cachedItem->set($percentageSuccessfullyFunded)->expiresAfter(3600);
            $this->cachePool->save($cachedItem);

            return $percentageSuccessfullyFunded;
        } else {
            return $cachedItem->get();
        }
    }

    public function getAverageFundingTime()
    {
        $cachedItem = $this->cachePool->getItem('averageFundingTime');

        if (false === $cachedItem->isHit()) {
            /** @var \projects $projects */
            $projects = $this->entityManager->getRepository('projects');
            /** @var array $averageFundingTime */
            $averageFundingTime = $projects->getAverageFundingTime();

            $cachedItem->set($averageFundingTime)->expiresAfter(3600);
            $this->cachePool->save($cachedItem);

            return $averageFundingTime;
        } else {
            return $cachedItem->get();
        }
    }

    public function getAverageInterestRateForLenders()
    {
        $cachedItem = $this->cachePool->getItem('averageInterestRateForLenders');

        if (false === $cachedItem->isHit()) {
            /** @var \projects $projects */
            $projects = $this->entityManager->getRepository('projects');
            /** @var int $averageRate */
            $averageRate = $projects->getGlobalAverageRateOfFundedProjects(PHP_INT_MAX);
            $cachedItem->set($averageRate)->expiresAfter(3600);
            $this->cachePool->save($cachedItem);

            return $averageRate;
        } else {
            return $cachedItem->get();
        }
    }

    public function getPercentageSelectedProjects()
    {
        /** @var \projects_status_history $projectStatusHistory */
        $projectStatusHistory = $this->entityManager->getRepository('projects_status_history');
        $aCountByStatus = $projectStatusHistory->countProjectsHavingHadStatus(array(\projects_status::EN_FUNDING, \projects_status::FUNDE));

        $sPercentageSuccessfullyFunded = bcmul(bcdiv($aCountByStatus[\projects_status::FUNDE], $aCountByStatus[\projects_status::EN_FUNDING], 4), 100);
        return $sPercentageSuccessfullyFunded;
    }

    public function getLendersByType()
    {
        /** @var \lenders_accounts $lenders */
        $lenders   = $this->entityManager->getRepository('lenders_accounts');
        $iLendersPerson      = $lenders->countLendersByClientType(array(\clients::TYPE_PERSON, \clients::TYPE_PERSON_FOREIGNER));
        $iLendersLegalEntity = $lenders->countLendersByClientType(array(\clients::TYPE_LEGAL_ENTITY, \clients::TYPE_LEGAL_ENTITY_FOREIGNER));
        $iTotalLenders       = $this->getNumberOfLenders();

        $aLendersCountByType = [
            'person' => array(
                'count'      => $iLendersPerson,
                'percentage' => round(bcdiv($iLendersPerson , $iTotalLenders, 4) * 100, 0)
            ),
            'legalEntity' => array(
                'count' => $iLendersLegalEntity,
                'percentage' => round(bcdiv($iLendersLegalEntity , $iTotalLenders, 4) * 100, 0)
            ),
            'active'    => $this->getNumberOfActiveLenders(),
            'community' => $iTotalLenders
        ];

        return $aLendersCountByType;
    }

    public function getLendersByRegion()
    {
        /** @var \clients $clients */
        $clients = $this->entityManager->getRepository('clients');
        return $clients->countClientsByRegion('lender');
    }

    public function getAverageNumberOfLenders()
    {
        /** @var \projects $projects */
        $projects = $this->entityManager->getRepository('projects');
        return $projects->getAverageNumberOfLendersForProject();
    }

    public function getAverageProjectAmount()
    {
        /** @var \projects $projects */
        $projects = $this->entityManager->getRepository('projects');
        return $projects->getAverageAmount();
    }

    public function getAverageLoanAmount()
    {
        /** @var \loans $loans */
        $loans = $this->entityManager->getRepository('loans');

    }

    public function getBorrowersByRegion()
    {
        /** @var \clients $clients */
        $clients = $this->entityManager->getRepository('clients');
        return $clients->countClientsByRegion('borrower');
        //TODO compare to array in mapsService and array_fill missing regions
    }
}
