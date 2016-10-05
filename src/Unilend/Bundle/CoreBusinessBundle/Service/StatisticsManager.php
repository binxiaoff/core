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

    public function __construct(EntityManager $entityManager, IRRManager $IRRManager, MemcacheCachePool $cachePool, LocationManager $locationManager)
    {
        $this->entityManager      = $entityManager;
        $this->IRRManager         = $IRRManager;
        $this->cachePool          = $cachePool;
        $this->locationManager    = $locationManager;
    }

    /**
     * @param string $name
     * @return mixed
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
        $cachedItem = $this->cachePool->getItem(CacheKeys::REGULATORY_TABLE);
        $regulatoryTable = $cachedItem->get();

        return $regulatoryTable['borrowed-capital']['total'];
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

    /**
     * Stat  is voluntarily only on the last 4 months
     * Should be changed to 6 months end of November 2016
     */
    public function getAverageFundingTime()
    {
        $cachedItem = $this->cachePool->getItem(CacheKeys::AVG_FUNDING_TIME);

        if (false === $cachedItem->isHit()) {
            $startDate = new \DateTime('NOW - 4 MONTHS');
            /** @var \projects $projects */
            $projects = $this->entityManager->getRepository('projects');
            /** @var array $averageFundingTime */
            $averageFundingTime = $projects->getAverageFundingTime($startDate);

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

        if (false === $cachedItem->isHit()) {
            /** @var \projects $projects */
            $projects = $this->entityManager->getRepository('projects');
            /** @var int $numberOfProjectRequests */
            $numberOfProjectRequests = self::HISTORIC_NUMBER_OF_SIREN + $projects->getNumberOfUniqueProjectRequests(self::VALUE_DATE_HISTORIC_NUMBER_OF_SIREN);
            $cachedItem->set($numberOfProjectRequests)->expiresAfter(CacheKeys::DAY);
            $this->cachePool->save($cachedItem);

            return $numberOfProjectRequests;
        } else {
            return $cachedItem->get();
        }
    }

    public function getPercentageOfAcceptedProjects()
    {
        $cachedItem = $this->cachePool->getItem(CacheKeys::PERCENT_ACCEPTED_PROJECTS);

        if (false === $cachedItem->isHit()) {
            $numberOfRequests = $this->getNumberOfProjectRequests();
            /** @var \projects_status_history $projectStatusHistory */
            $projectStatusHistory = $this->entityManager->getRepository('projects_status_history');
            /** @var array $countByStatus */
            $countByStatus = $projectStatusHistory->countProjectsHavingHadStatus([\projects_status::EN_FUNDING]);
            /** @var string $percentageOfAcceptedProjects */
            $percentageOfAcceptedProjects = bcmul(bcdiv($countByStatus[\projects_status::EN_FUNDING], $numberOfRequests, 4), 100, 2);
            $cachedItem->set($percentageOfAcceptedProjects)->expiresAfter(CacheKeys::DAY);
            $this->cachePool->save($cachedItem);

            return $percentageOfAcceptedProjects;
        } else {
            return $cachedItem->get();
        }
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
            $totalLenders = bcadd($lendersPerson, $lendersLegalEntity);

            $lendersByType = [
                'person' => [
                    'count'      => $lendersPerson,
                    'percentage' => round(bcmul(bcdiv($lendersPerson , $totalLenders, 4), 100, 2))
                ],
                'legalEntity' => [
                    'count' => $lendersLegalEntity,
                    'percentage' => round(bcmul(bcdiv($lendersLegalEntity , $totalLenders, 4), 100, 2))
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
        $cachedItem = $this->cachePool->getItem(CacheKeys::REGULATORY_TABLE);
        $regulatoryTable = $cachedItem->get();

        return $regulatoryTable['repaid-capital']['total'];
    }

    public function getTotalRepaidInterests()
    {
        $cachedItem = $this->cachePool->getItem(CacheKeys::REGULATORY_TABLE);
        $regulatoryTable = $cachedItem->get();

        return $regulatoryTable['repaid-interest']['total'];
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
     * Stat  is voluntarily only on the last 6 months
     */
    public function getNumberOfProjectsFundedIn24Hours()
    {
        $cachedItem = $this->cachePool->getItem(CacheKeys::PROJECTS_FUNDED_IN_24_HOURS);

        if (false === $cachedItem->isHit()) {
            $startDate = new \DateTime('NOW - 6 MONTHS');
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
     * Stat  is voluntarily only on the last 6 months
     */
    public function getPercentageOfProjectsFundedIn24Hours()
    {
        $cachedItem = $this->cachePool->getItem(CacheKeys::PERCENT_PROJECTS_FUNDED_IN_24_HOURS);

        if (false === $cachedItem->isHit()) {
            $startDate = new \DateTime('NOW - 6 MONTHS');
            /** @var \projects $projects */
            $projects = $this->entityManager->getRepository('projects');
            $countAllProjects = $projects->countProjectsFundedSince($startDate);
            $count24hFunding = $this->getNumberOfProjectsFundedIn24Hours();
            $percentageFunded24h = $countAllProjects > 0 ? bcmul(bcdiv($count24hFunding, $countAllProjects, 0), 100, 0) : 0;
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

    public function getRegulatoryData()
    {
        $cachedItem     = $this->cachePool->getItem(CacheKeys::REGULATORY_TABLE);
        return $cachedItem->get();
    }

    public function getIncidenceRate()
    {
        $cachedItem     = $this->cachePool->getItem(CacheKeys::INCIDENCE_RATE_IFP);
        return $cachedItem->get();
    }

    public function calculateRegulatoryData()
    {
        $years = array_merge(['2013-2014'], range(2015, date('Y')));

        /** @var \loans $loans */
        $loans = $this->entityManager->getRepository('loans');
        /** @var \echeanciers_emprunteur $borrowerPaymentSchedule */
        $borrowerPaymentSchedule = $this->entityManager->getRepository('echeanciers_emprunteur');
        /** @var \transactions $transactions */
        $transactions = $this->entityManager->getRepository('transactions');
        /** @var \echeanciers $lenderRepaymentSchedule */
        $lenderRepaymentSchedule = $this->entityManager->getRepository('echeanciers');
        /** @var \projects $projects */
        $projects = $this->entityManager->getRepository('projects');
        /** @var \companies $companies */
        $companies = $this->entityManager->getRepository('companies');

        $borrowedCapital                          = $this->formatCohortQueryResult($loans->sumLoansByCohort(), $years);
        $repaidCapital                            = $this->formatCohortQueryResult($borrowerPaymentSchedule->getRepaidCapitalByCohort(), $years);
        $recoveryPaymentsHealthyProjects          = $this->formatCohortQueryResult($transactions->getBorrowerRecoveryPaymentsOnHealthyProjectsByCohort(), $years);
        $recoveryPaymentsProblematicProjects      = $this->formatCohortQueryResult($transactions->getBorrowerRecoveryPaymentsOnProblematicProjectsByCohort(), $years);
        $repaidInterest                           = $this->formatCohortQueryResult($lenderRepaymentSchedule->getTotalRepaidInterestByCohort(), $years);
        $interestHealthyProjects                  = $this->formatCohortQueryResult($borrowerPaymentSchedule->getInterestPaymentsOfHealthyProjectsByCohort(), $years);
        $futureCapitalProblematicProjects         = $this->formatCohortQueryResult($borrowerPaymentSchedule->getFutureOwedCapitalOfProblematicProjectsByCohort(), $years);
        $futureCapitalHealthyProjects             = $this->formatCohortQueryResult($borrowerPaymentSchedule->getFutureCapitalPaymentsOfHealthyProjectsByCohort(), $years);
        $lateCapitalRepaymentsHealthyProjects     = $this->formatCohortQueryResult($borrowerPaymentSchedule->getLateCapitalRepaymentsHealthyProjects(), $years);
        $lateCapitalRepaymentsProblematicProjects = $this->formatCohortQueryResult($borrowerPaymentSchedule->getLateCapitalRepaymentsProblematicProjects(), $years);
        $countFundedCompanies                     = $this->formatCohortQueryResult($companies->countCompaniesFundedByCohort(), $years);
        $fundedProjects                           = $this->formatCohortQueryResult($projects->countFundedProjectsByCohort(), $years);
        $problematicCompanies                     = $this->formatCohortQueryResult($companies->countCompaniesWithProblematicProjectsByCohort(), $years);

        $data = [];

        foreach ($years as $year) {
            //beforehand calculations
            $upcomingPaymentsPerYear               = bcadd($futureCapitalHealthyProjects[$year], $futureCapitalProblematicProjects[$year]);
            $recoveryPayments                      = bcadd($recoveryPaymentsProblematicProjects, $recoveryPaymentsHealthyProjects);
            $latePaymentsPerYear                   = bcsub(bcadd($lateCapitalRepaymentsProblematicProjects[$year], $lateCapitalRepaymentsHealthyProjects[$year]), $recoveryPayments);
            $totalOwedProblematicCapitalPerYear    = bcadd($lateCapitalRepaymentsProblematicProjects[$year], $futureCapitalProblematicProjects[$year]);
            $capitalAndInterestLessProblemsPerYear = bcsub(bcadd(bcadd($borrowedCapital[$year], $repaidInterest[$year]), $interestHealthyProjects[$year]), $totalOwedProblematicCapitalPerYear);

            $data['IRR'][$year]                                 = $year == '2013-2014' ? $this->IRRManager->getUnilendIRRForCohort20132014() : $this->IRRManager->getUnilendIRRByCohort($year);
            $data['projects'][$year]                            = $fundedProjects[$year];
            $data['borrowed-capital'][$year]                    = $borrowedCapital[$year];
            $data['repaid-capital'][$year]                      = bcadd($repaidCapital[$year], bcadd($recoveryPaymentsHealthyProjects[$year], $recoveryPaymentsProblematicProjects[$year]));
            $data['repaid-interest'][$year]                     = $repaidInterest[$year];
            $data['owed-healthy-interest'][$year]               = $interestHealthyProjects[$year];
            $data['global-owed-capital'][$year]                 = bcadd($latePaymentsPerYear, $upcomingPaymentsPerYear);
            $data['future-owed-capital'][$year]                 = $upcomingPaymentsPerYear;
            $data['future-owed-capital-healthy'][$year]         = $futureCapitalHealthyProjects[$year];
            $data['future-owed-capital-problematic'][$year]     = $futureCapitalProblematicProjects[$year];
            $data['late-owed-capital'][$year]                   = $latePaymentsPerYear;
            $data['late-owed-capital-problematic'][$year]       = bcsub($lateCapitalRepaymentsProblematicProjects[$year], $recoveryPaymentsProblematicProjects[$year]);
            $data['late-owed-capital-healthy'][$year]           = bcsub($lateCapitalRepaymentsHealthyProjects[$year], $recoveryPaymentsHealthyProjects[$year]);
            $data['total-owed-problematic-capital'][$year]      = bcadd($futureCapitalProblematicProjects[$year], $latePaymentsPerYear);
            $data['total-owed-problematic-capital-late'][$year] = $totalOwedProblematicCapitalPerYear;

            //percentages
            $owedProblematicOverBorrowedCapital     = $borrowedCapital[$year] > 0 ? bcmul(bcdiv($data['total-owed-problematic-capital'][$year], $borrowedCapital[$year], 4), 100, 2) : 0;
            $owedInterestOverProblematicCapital     = bcmul(bcdiv(($data['repaid-interest'][$year] + $data['owed-healthy-interest'][$year]), $data['total-owed-problematic-capital'][$year], 4), 100, 2);

            $data['pct']['owed-problematic-over-borrowed-capital'][$year] = $owedProblematicOverBorrowedCapital;
            $data['pct']['problematic-rate'][$year]                       = bcmul(bcdiv($problematicCompanies[$year], $countFundedCompanies[$year], 4), 100, 2);
            $data['pct']['interest-over-owed-problematic-capital'][$year] = $owedInterestOverProblematicCapital;
            $data['pct']['expected-performance'][$year]                   = $borrowedCapital[$year] > 0 ? bcmul((bcdiv($capitalAndInterestLessProblemsPerYear, $borrowedCapital[$year], 4) - 1), 100, 2) : 0;
        }

        $data = $this->addTotalToData($data, $problematicCompanies, $fundedProjects);

        return $data;
    }

    private function formatCohortQueryResult($datas, $years)
    {
        $dataByCohort = [];

        foreach ($datas as $data) {
            $dataByCohort[$data['cohort']] = $data['amount'];
        }

        foreach($years as $year){
            if(empty($dataByCohort[$year])){
                $dataByCohort[$year] = 0;
            }
        }

        return $dataByCohort;
    }

    public function addTotalToData(&$data, $problematicCompanies, $fundedProjects)
    {
        $data['IRR']['total'] = $this->IRRManager->getLastUnilendIRR()['value'];

        foreach($data as $type => $numbers) {
            if (false === in_array($type, ['pct', 'IRR'])){
                $data[$type]['total'] = array_sum($numbers);
            }
        }

        $data = $this->addTotalPercentagesAndPlainNumbers($data, $problematicCompanies, $fundedProjects);

        return $data;
    }

    public function addTotalPercentagesAndPlainNumbers(&$data, $problematicCompanies, $fundedProjects)
    {
        $owedProblematicOverBorrowedCapital     = $data['borrowed-capital']['total'] > 0 ? bcmul(bcdiv($data['total-owed-problematic-capital']['total'], $data['borrowed-capital']['total'], 4), 100, 2) : 0;
        $capitalAndInterestLessProblems         = bcsub(bcadd(bcadd($data['borrowed-capital']['total'], $data['repaid-interest']['total']), $data['owed-healthy-interest']['total']), $data['total-owed-problematic-capital-late']['total']);
        $owedInterestOverProblematicCapital     = bcmul(bcdiv(($data['repaid-interest']['total'] + $data['owed-healthy-interest']['total']), $data['total-owed-problematic-capital']['total'], 4), 100, 2);

        $data['pct']['owed-problematic-over-borrowed-capital']['total'] = $owedProblematicOverBorrowedCapital;
        $data['pct']['interest-over-owed-problematic-capital']['total'] = $owedInterestOverProblematicCapital;
        $data['pct']['expected-performance']['total']                   = bcmul(bcdiv($capitalAndInterestLessProblems, $data['borrowed-capital']['total'], 4)-1, 100, 2);
        $data['pct']['problematic-rate']['total']                       = bcdiv(array_sum($problematicCompanies), array_sum($fundedProjects));

        return $data;
    }

    public function calculateIncidenceRateOnIFPContracts()
    {
        /** @var \echeanciers $paymentSchedule */
        $paymentSchedule = $this->entityManager->getRepository('echeanciers');

        $problematicProjects = [];
        $allProjects = [];

        foreach ($paymentSchedule->getOwedCapitalANdProjectsByContractType(\underlying_contract::CONTRACT_IFP) as $project) {
            $allProjects[$project['id_project']] = $project['amount'];

            if ($project['status'] >= \projects_status::PROBLEME && $project['delay'] >= 60 ) {
                $problematicProjects[$project['id_project']] = $project['amount'];
            }
        }

        $incidenceRate['amountIFP']   = bcmul(bcdiv(array_sum($problematicProjects), array_sum($allProjects), 4), 100, 2);
        $incidenceRate['projectsIFP'] = bcmul(bcdiv(count($problematicProjects), count($allProjects), 4), 100, 2);

        return $incidenceRate;
    }

}
