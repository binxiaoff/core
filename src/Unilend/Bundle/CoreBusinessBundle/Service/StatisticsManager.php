<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Cache\Adapter\Memcache\MemcacheCachePool;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\librairies\CacheKeys;

class StatisticsManager
{
    const HISTORIC_NUMBER_OF_SIREN = 26205;
    const VALUE_DATE_HISTORIC_NUMBER_OF_SIREN = '2016-08-31 00:00:00';

    /** @var  EntityManager */
    private $entityManager;
    /** @var  IRRManager */
    private $IRRManager;
    /** @var MemcacheCachePool */
    private $cachePool;
    /** @var LocationManager */
    private $locationManager;

    public function __construct(EntityManager $entityManager, IRRManager $IRRManager,MemcacheCachePool $cachePool, LocationManager $locationManager)
    {
        $this->entityManager      = $entityManager;
        $this->IRRManager         = $IRRManager;
        $this->cachePool          = $cachePool;
        $this->locationManager    = $locationManager;
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
        $cachedItem = $this->cachePool->getItem(CacheKeys::LENDERS_IN_COMMUNITY);

        if (false === $cachedItem->isHit()) {
            /** @var \lenders_accounts $lenders */
            $lenders = $this->entityManager->getRepository('lenders_accounts');
            /** @var int $numberOfLendersInCommunity */
            $numberOfLendersInCommunity = $lenders->countLenders();
            $cachedItem->set($numberOfLendersInCommunity)->expiresAfter(CacheKeys::DAY);
            $this->cachePool->save($cachedItem);

            return $numberOfLendersInCommunity;
        } else {
            return $cachedItem->get();
        }
    }

    public function getNumberOfActiveLenders()
    {
        $cachedItem = $this->cachePool->getItem(CacheKeys::ACTIVE_LENDERS);

        if (false === $cachedItem->isHit()) {
            /** @var \lenders_accounts $lenders */
            $lenders = $this->entityManager->getRepository('lenders_accounts');
            /** @var int $numberOfActiveLenders */
            $numberOfActiveLenders = $lenders->countLenders(true);
            $cachedItem->set($numberOfActiveLenders)->expiresAfter(CacheKeys::DAY);
            $this->cachePool->save($cachedItem);

            return $numberOfActiveLenders;
        } else {
            return $cachedItem->get();
        }
    }

    public function getNumberOfFinancedProjects()
    {
        $cachedItem = $this->cachePool->getItem(CacheKeys::FINANCED_PROJECTS);

        if (false === $cachedItem->isHit()) {
            /** @var \projects $projects */
            $projects = $this->entityManager->getRepository('projects');
            /** @var int $numberOfProjects */
            $numberOfProjects = $projects->countSelectProjectsByStatus(implode(',', \projects_status::$afterRepayment));
            $cachedItem->set($numberOfProjects)->expiresAfter(CacheKeys::LONG_TIME);
            $this->cachePool->save($cachedItem);

            return $numberOfProjects;
        } else {
            return $cachedItem->get();
        }
    }

    public function getAmountBorrowed()
    {
        $cachedItem = $this->cachePool->getItem(CacheKeys::AMOUNT_BORRWED);

        if (false === $cachedItem->isHit()) {
            /** @var \transactions $transactions */
            $transactions    = $this->entityManager->getRepository('transactions');
            /** @var int $amountBorrowed */
            $amountBorrowed = bcdiv($transactions->sum('type_transaction = ' . \transactions_types::TYPE_BORROWER_BANK_TRANSFER_CREDIT, 'montant_unilend-montant'), 100);
            $cachedItem->set($amountBorrowed)->expiresAfter(CacheKeys::LONG_TIME);
            $this->cachePool->save($cachedItem);

            return $amountBorrowed;
        } else {
            return $cachedItem->get();
        }
    }

    public function getAmountBorrowedInMillions()
    {
        return bcdiv($this->getAmountBorrowed(), 1000000, 0);
    }

    public function getUnilendIRR()
    {
        $cachedItem = $this->cachePool->getItem(CacheKeys::UNILEND_IRR);

        if (false === $cachedItem->isHit()) {
            /** @var array $lastUnilendIRR */
            $lastUnilendIRR = $this->IRRManager->getLastUnilendIRR();
            $cachedItem->set($lastUnilendIRR['value'])->expiresAfter(CacheKeys::DAY);
            $this->cachePool->save($cachedItem);

            return $lastUnilendIRR['value'];
        } else {
            return $cachedItem->get();
        }
    }

    public function getPercentageSuccessfullyFinancedProjects()
    {
        $cachedItem = $this->cachePool->getItem(CacheKeys::PERCENT_FULLY_FINANCED_PROJECTS);

        if (false === $cachedItem->isHit()) {
            /** @var \projects_status_history $projectStatusHistory */
            $projectStatusHistory = $this->entityManager->getRepository('projects_status_history');
            /** @var array $countByStatus */
            $countByStatus = $projectStatusHistory->countProjectsHavingHadStatus([\projects_status::EN_FUNDING, \projects_status::FUNDE]);
            /** @var string $percentageSuccessfullyFunded */
            $percentageSuccessfullyFunded = bcmul(bcdiv($countByStatus[\projects_status::FUNDE], $countByStatus[\projects_status::EN_FUNDING], 4), 100);
            $cachedItem->set($percentageSuccessfullyFunded)->expiresAfter(CacheKeys::LONG_TIME);
            $this->cachePool->save($cachedItem);

            return $percentageSuccessfullyFunded;
        } else {
            return $cachedItem->get();
        }
    }

    public function getAverageFundingTime()
    {
        $cachedItem = $this->cachePool->getItem(CacheKeys::AVG_FUNDING_TIME);

        if (false === $cachedItem->isHit()) {
            /** @var \projects $projects */
            $projects = $this->entityManager->getRepository('projects');
            /** @var array $averageFundingTime */
            $averageFundingTime = $projects->getAverageFundingTime();

            $cachedItem->set($averageFundingTime)->expiresAfter(CacheKeys::LONG_TIME);
            $this->cachePool->save($cachedItem);

            return $averageFundingTime;
        } else {
            return $cachedItem->get();
        }
    }

    public function getAverageInterestRateForLenders()
    {
        $cachedItem = $this->cachePool->getItem(CacheKeys::AVG_INTEREST_RATE_LENDERS);

        if (false === $cachedItem->isHit()) {
            /** @var \projects $projects */
            $projects = $this->entityManager->getRepository('projects');
            /** @var int $averageRate */
            $averageRate = $projects->getGlobalAverageRateOfFundedProjects(PHP_INT_MAX);
            $cachedItem->set($averageRate)->expiresAfter(CacheKeys::LONG_TIME);
            $this->cachePool->save($cachedItem);

            return $averageRate;
        } else {
            return $cachedItem->get();
        }
    }

    public function getAverageNumberOfLenders()
    {
        $cachedItem = $this->cachePool->getItem(CacheKeys::AVG_LENDER_ON_PROJECT);

        if (false === $cachedItem->isHit()) {
            /** @var \projects $projects */
            $projects = $this->entityManager->getRepository('projects');
            /** @var int $averageNumberOfLenders */
            $averageNumberOfLenders = $projects->getAverageNumberOfLendersForProject();
            $cachedItem->set($averageNumberOfLenders)->expiresAfter(CacheKeys::DAY);
            $this->cachePool->save($cachedItem);

            return $averageNumberOfLenders;
        } else {
            return $cachedItem->get();
        }
    }

    public function getAverageProjectAmount()
    {
        $cachedItem = $this->cachePool->getItem(CacheKeys::AVG_PROJECT_AMOUNT);

        if (false === $cachedItem->isHit()) {
            /** @var \projects $projects */
            $projects = $this->entityManager->getRepository('projects');
            /** @var int $averageProjectAmount */
            $averageProjectAmount = $projects->getAverageAmount();
            $cachedItem->set($averageProjectAmount)->expiresAfter(CacheKeys::DAY);
            $this->cachePool->save($cachedItem);

            return $averageProjectAmount;
        } else {
            return $cachedItem->get();
        }
    }

    public function getAverageLoanAmount()
    {
        $cachedItem = $this->cachePool->getItem(CacheKeys::AVG_LOAN_AMOUNT);

        if (false === $cachedItem->isHit()) {
            /** @var \loans $loans */
            $loans = $this->entityManager->getRepository('loans');
            /** @var int $averageLoanAmount */
            $averageLoanAmount = $loans->getAverageLoanAmount();
            $cachedItem->set($averageLoanAmount)->expiresAfter(CacheKeys::DAY);
            $this->cachePool->save($cachedItem);

            return $averageLoanAmount;
        } else {
            return $cachedItem->get();
        }
    }

    /**
     * Siren count has first started in an excel spreadsheet. For that reason DB data will always be inconsistent with previously announced data.
     * For that reason it has been decided to start counting only from a given date and adding this count to the historic value
     */
    public function getNumberOfProjectRequests()
    {
        $cachedItem = $this->cachePool->getItem(CacheKeys::NUMBER_PROJECT_REQUESTS);

        //if (false === $cachedItem->isHit()) {
            /** @var \projects $projects */
            $projects = $this->entityManager->getRepository('projects');
            /** @var int $numberOfProjectRequests */
            $numberOfProjectRequests = self::HISTORIC_NUMBER_OF_SIREN + $projects->getNumberOfUniqueProjectRequests(self::VALUE_DATE_HISTORIC_NUMBER_OF_SIREN);
            $cachedItem->set($numberOfProjectRequests)->expiresAfter(CacheKeys::DAY);
            $this->cachePool->save($cachedItem);

            return $numberOfProjectRequests;
//        } else {
//            return $cachedItem->get();
//        }
    }

    public function getPercentageOfAcceptedProjects()
    {
        $cachedItem = $this->cachePool->getItem(CacheKeys::PERCENT_ACCEPTED_PROJECTS);

//        if (false === $cachedItem->isHit()) {
            $numberOfRequests = $this->getNumberOfProjectRequests();
            /** @var \projects_status_history $projectStatusHistory */
            $projectStatusHistory = $this->entityManager->getRepository('projects_status_history');
            /** @var array $countByStatus */
            $countByStatus = $projectStatusHistory->countProjectsHavingHadStatus([\projects_status::EN_FUNDING]);
            /** @var string $percentageOfAcceptedProjects */
            $percentageOfAcceptedProjects = bcdiv($countByStatus[\projects_status::EN_FUNDING], $numberOfRequests, 2);
            $cachedItem->set($percentageOfAcceptedProjects)->expiresAfter(CacheKeys::DAY);
            $this->cachePool->save($cachedItem);

            return $percentageOfAcceptedProjects;
//        } else {
//            return $cachedItem->get();
//        }
    }

    public function getAverageLenderIRR()
    {
        $cachedItem = $this->cachePool->getItem(CacheKeys::AVG_LENDER_IRR);

        if (false === $cachedItem->isHit()) {
            /** @var \lenders_account_stats $lendersAccountsStats */
            $lendersAccountsStats = $this->entityManager->getRepository('lenders_accounts_stats');
            $averageLenderIRR = $lendersAccountsStats->getAverageIRRofAllLenders();
            $cachedItem->set($averageLenderIRR)->expiresAfter(CacheKeys::DAY);
            $this->cachePool->save($cachedItem);

            return $averageLenderIRR;
        } else {
            return $cachedItem->get();
        }
    }

    public function getLendersByType()
    {
        $cachedItem = $this->cachePool->getItem(CacheKeys::LENDERS_BY_TYPE);

        if (false === $cachedItem->isHit()) {
            /** @var \lenders_accounts $lenders */
            $lenders = $this->entityManager->getRepository('lenders_accounts');
            /** @var int $lendersPerson */
            $lendersPerson = $lenders->countLendersByClientType([\clients::TYPE_PERSON, \clients::TYPE_PERSON_FOREIGNER]);
            /** @var int $lendersLegalEntity */
            $lendersLegalEntity = $lenders->countLendersByClientType([\clients::TYPE_LEGAL_ENTITY, \clients::TYPE_LEGAL_ENTITY_FOREIGNER]);
            /** @var int $totalLenders */
            $totalLenders = $lenders->countLenders();

            $lendersByType = [
                'person' => [
                    'count'      => $lendersPerson,
                    'percentage' => round(bcdiv($lendersPerson , $totalLenders, 4) * 100, 0)
                ],
                'legalEntity' => [
                    'count' => $lendersLegalEntity,
                    'percentage' => round(bcdiv($lendersLegalEntity , $totalLenders, 4) * 100, 0)
                ]
            ];

            $cachedItem->set($lendersByType)->expiresAfter(CacheKeys::DAY);
            $this->cachePool->save($cachedItem);

            return $lendersByType;
        } else {
            return $cachedItem->get();
        }
    }

    public function getLendersByRegion()
    {
        $cachedItem = $this->cachePool->getItem(CacheKeys::LENDERS_BY_REGION);

        if (false === $cachedItem->isHit()) {
            $lendersByRegion = $this->locationManager->getLendersByRegion();
            $cachedItem->set($lendersByRegion)->expiresAfter(CacheKeys::DAY);
            $this->cachePool->save($cachedItem);

            return $lendersByRegion;
        } else {
            return $cachedItem->get();
        }
    }

    public function getBorrowersByRegion()
    {
        $cachedItem = $this->cachePool->getItem(CacheKeys::PROJECTS_BY_REGION);

        if (false === $cachedItem->isHit()) {
            $projectsByRegion = $this->locationManager->getProjectsByRegion();
            $cachedItem->set($projectsByRegion)->expiresAfter(CacheKeys::DAY);
            $this->cachePool->save($cachedItem);

            return $projectsByRegion;
        } else {
            return $cachedItem->get();
        }
    }

    public function getTotalRepaidCapital()
    {
        $cachedItem = $this->cachePool->getItem(CacheKeys::TOTAL_REPAID_CAPITAL);

        if (false === $cachedItem->isHit()) {
            /** @var \echeanciers $paymentSchedule */
            $paymentSchedule = $this->entityManager->getRepository('echeanciers');
            $repaidCapital = $paymentSchedule->getTotalRepaidCapital();
            $cachedItem->set($repaidCapital)->expiresAfter(CacheKeys::DAY);
            $this->cachePool->save($cachedItem);

            return $repaidCapital;
        } else {
            return $cachedItem->get();
        }
    }

    public function getTotalRepaidInterests()
    {
        $cachedItem = $this->cachePool->getItem(CacheKeys::TOTAL_REPAID_INTERST);

        if (false === $cachedItem->isHit()) {
            /** @var \echeanciers $paymentSchedule */
            $paymentSchedule = $this->entityManager->getRepository('echeanciers');
            $repaidInterests = $paymentSchedule->getTotalRepaidInterests();
            $cachedItem->set($repaidInterests)->expiresAfter(CacheKeys::DAY);
            $this->cachePool->save($cachedItem);

            return $repaidInterests;
        } else {
            return $cachedItem->get();
        }
    }

    public function getProjectCountByCategory()
    {
        $cachedItem = $this->cachePool->getItem(CacheKeys::PROJECTS_BY_CATEGORY);

        if (false === $cachedItem->isHit()) {
            /** @var \projects $projects */
            $projects = $this->entityManager->getRepository('projects');
            $projectsCountByCategory = $projects->countProjectsByCategory();
            $cachedItem->set($projectsCountByCategory)->expiresAfter(CacheKeys::DAY);
            $this->cachePool->save($cachedItem);

            return $projectsCountByCategory;
        } else {
            return $cachedItem->get();
        }
    }

    /**
     * Stat  is voluntarily only on the last 3 months
     */
    public function getNumberOfProjectsFundedIn24Hours()
    {
        $cachedItem = $this->cachePool->getItem(CacheKeys::PROJECTS_FUNDED_IN_24_HOURS);

        if (false === $cachedItem->isHit()) {
            $startDate = new \DateTime('NOW - 3 MONTHS');
            /** @var \projects $projects */
            $projects = $this->entityManager->getRepository('projects');
            $count24hFunding = $projects->countProjectsFundedIn24Hours($startDate);
            $cachedItem->set($count24hFunding )->expiresAfter(CacheKeys::LONG_TIME);

            return $count24hFunding;
        }
        else {
            return $cachedItem->get();
        }
    }

    /**
     * Stat  is voluntarily only on the last 3 months
     */
    public function getPercentageOfProjectsFundedIn24Hours()
    {
        $cachedItem = $this->cachePool->getItem(CacheKeys::PERCENT_PROJECTS_FUNDED_IN_24_HOURS);

        if (false === $cachedItem->isHit()) {
            $startDate = new \DateTime('NOW - 3 MONTHS');
            /** @var \projects $projects */
            $projects = $this->entityManager->getRepository('projects');
            $countAllProjects = $projects->countProjectsFundedSince($startDate);
            $count24hFunding = $this->getNumberOfProjectsFundedIn24Hours();
            $percentageFunded24h = bcdiv($count24hFunding, $countAllProjects, 0);
            $cachedItem->set($percentageFunded24h)->expiresAfter(CacheKeys::DAY);

            return $percentageFunded24h;
        }
        else {
            return $cachedItem->get();
        }
    }

    public function getSecondsForBid()
    {
        $cachedItem = $this->cachePool->getItem(CacheKeys::BID_EVERY_X_SECOND);

        if (false === $cachedItem->isHit()) {
            /** @var \bids $bids */
            $bids               = $this->entityManager->getRepository('bids');
            $maxCountBidsPerDay = $bids->getMaxCountBidsPerDay();
            $secondsPerDay      = 24 * 60 * 60;
            $secondsForBid      = bcdiv($secondsPerDay, $maxCountBidsPerDay, 0);
            $cachedItem->set($secondsForBid)->expiresAfter(CacheKeys::LONG_TIME);

            return $secondsForBid;
        } else {
            return $cachedItem->get();
        }
    }

    public function getHighestAmountObtainedFastest()
    {
        $cachedItem = $this->cachePool->getItem(CacheKeys::AMOUNT_FINANCED_HIGHEST_FASTEST);

        if (false === $cachedItem->isHit()) {
            /** @var \projects $projects */
            $projects = $this->entityManager->getRepository('projects');
            $recordAmount = $projects->getHighestAmountObtainedFastest();
            $cachedItem->set($recordAmount)->expiresAfter(CacheKeys::LONG_TIME);
            return $recordAmount;
        } else {
            return $cachedItem->get();
        }

    }

    /** STATS FOR THE REGULATORY TABLE AND GRAPH */

    public function getOwedCapital()
    {
        $cachedItem = $this->cachePool->getItem('UnilendStatsOwedCapital');
        return $cachedItem->get();
    }


    public function getProblematicProjects()
    {
        $cachedItem = $this->cachePool->getItem('UnilendStatsProblematicProjects');
        return $cachedItem->get();
    }

    public function getCapitalInDifficulty()
    {
        $problematicProjects    = $this->getProblematicProjects();
        return round($problematicProjects['capital'], 2);
    }

    public function getUpcomingInterest()
    {
        $cachedItem = $this->cachePool->getItem('UnilendStatsUpcomingInterest');
        return $cachedItem->get();
    }

    public function getRegulatoryData()
    {
        $data = [
            '2013-2014' => [
                'borrowedCapital' => '',
                'repaidCapital' => '',
                'repaidInterest' => '',
                'owedCapital' => [
                    'total' => '',
                    'withoutProlematicProjects' => '',
                    'problematicProjects' => '',
                    'latePayments' => [
                        'moreThan180Days' => '',
                        'lessThan180days' => '',
                    ],
                ],




            ],
        ];
    }

}
