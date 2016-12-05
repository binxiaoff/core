<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Cache\Adapter\Memcache\MemcacheCachePool;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\librairies\CacheKeys;

class StatisticsManager
{
    /**
     * Siren count has first started in an excel spreadsheet. For that reason DB data will always be inconsistent with previously announced data.
     * For that reason it has been decided to start counting only from a given date and adding this count to the historic value
     */
    const HISTORIC_NUMBER_OF_SIREN = 26205;
    const VALUE_DATE_HISTORIC_NUMBER_OF_SIREN = '2016-08-31 00:00:00';

    /** @var EntityManager */
    private $entityManager;
    /** @var IRRManager */
    private $IRRManager;
    /** @var MemcacheCachePool */
    private $cachePool;
    /** @var LocationManager */
    private $locationManager;

    public function __construct(
        EntityManager $entityManager,
        IRRManager $IRRManager,
        MemcacheCachePool $cachePool,
        LocationManager $locationManager
    ) {
        $this->entityManager   = $entityManager;
        $this->IRRManager      = $IRRManager;
        $this->cachePool       = $cachePool;
        $this->locationManager = $locationManager;
    }

    /**
     * @param string $name
     * @param \DateTime $date
     * @return mixed
     */
    public function getStatistic($name, \DateTime $date)
    {
        $statistics = $this->getStatisticsAtDate($date);
        return $statistics[lcfirst($name)];
    }

    /**
     * @param \DateTime $date
     * @return mixed|null|void
     */
    public function getStatisticsAtDate(\DateTime $date)
    {
        $today    = new \DateTime('NOW');
        $cacheKey = $date->format('Y-m-d') == $today->format('Y-m-d') ? CacheKeys::UNILEND_STATISTICS : CacheKeys::UNILEND_STATISTICS . '_' . $date->format('Y-m-d');

        $cachedItem = $this->cachePool->getItem($cacheKey);
        if (false === $cachedItem->isHit()) {
            /** @var \unilend_stats $unilendStats */
            $unilendStats = $this->entityManager->getRepository('unilend_stats');

            if ($date->format('Y-m-d') == $today->format('Y-m-d')) {
                $statsEntry = $unilendStats->select('type_stat = "' . CacheKeys::UNILEND_STATISTICS . '"', 'added DESC', null, '1')[0];
            } else {
                $statsEntry = $unilendStats->select('type_stat = "' . CacheKeys::UNILEND_STATISTICS . '" AND DATE(added) = "' . $date->format('Y-m-d') . '"','added DESC', null, '1')[0];
            }
            $statistics = json_decode($statsEntry['value'], true);
            $cachedItem->set($statistics)->expiresAfter(CacheKeys::DAY);
            $this->cachePool->save($cachedItem);

            return $statistics;
        } else {
            return $cachedItem->get();
        }
    }

    public function calculateStatistics()
    {
        /** @var \lenders_accounts $lenders */
        $lenders = $this->entityManager->getRepository('lenders_accounts');
        /** @var \projects $projects */
        $projects = $this->entityManager->getRepository('projects');
        /** @var \loans $loans */
        $loans = $this->entityManager->getRepository('loans');
        /** @var \DateTime $startDate voluntarily on last 6 Months except for average funding time which is on 4 month */
        $startDate = new \DateTime('NOW - 6 MONTHS');

        $statistics = [
            'numberOfLendersInCommunity'      => $lenders->countLenders(),
            'numberOfActiveLenders'           => $lenders->countLenders(true),
            'numberOfFinancedProjects'        => $projects->countSelectProjectsByStatus(implode(',', \projects_status::$afterRepayment)),
            'numberOfProjectRequests'         => self::HISTORIC_NUMBER_OF_SIREN + $projects->getNumberOfUniqueProjectRequests(self::VALUE_DATE_HISTORIC_NUMBER_OF_SIREN),
            'averageFundingTime'              => $projects->getAverageFundingTime(new \DateTime('NOW - 4 MONTHS')),
            'averageInterestRateForLenders'   => $projects->getGlobalAverageRateOfFundedProjects(PHP_INT_MAX),
            'averageNumberOfLenders'          => $projects->getAverageNumberOfLendersForProject(),
            'averageProjectAmount'            => $projects->getAverageAmount(),
            'averageLoanAmount'               => $loans->getAverageLoanAmount(),
            'unilendIRR'                      => $this->IRRManager->getLastUnilendIRR()['value'],
            'lendersByType'                   => $this->getLendersByType(),
            'lendersByRegion'                 => $this->locationManager->getLendersByRegion(),
            'borrowersByRegion'               => $this->locationManager->getProjectsByRegion(),
            'projectCountByCategory'          => $projects->countProjectsByCategory(),
            'secondsForBid'                   => $this->getSecondsForBid(),
            'highestAmountObtainedFastest'    => $projects->getHighestAmountObtainedFastest()
        ];

        $statistics['percentageOfAcceptedProjects']        = $this->getPercentageOfAcceptedProjects($statistics['numberOfProjectRequests']);
        $statistics['percentageOfProjectsFundedIn24Hours'] = $this->getPercentageOfProjectsFundedIn24Hours($startDate);
        $statistics['regulatoryData']                      = $this->calculateRegulatoryData();
        $statistics['incidenceRate']                       = $this->calculateIncidenceRateOnIFPContracts();
        $statistics['amountBorrowed']                      = $statistics['regulatoryData']['borrowed-capital']['total'];
        $statistics['amountBorrowedInMillions']            = round(bcdiv($statistics['amountBorrowed'], 1000000, 2), 1);
        $statistics['totalRepaidCapital']                  = $statistics['regulatoryData']['repaid-capital']['total'];
        $statistics['totalRepaidInterests']                = $statistics['regulatoryData']['repaid-interest']['total'];

        return $statistics;
    }

    /**
     * @param int $numberOfProjectRequests
     * @return string
     */
    private function getPercentageOfAcceptedProjects($numberOfProjectRequests)
    {
        /** @var \projects_status_history $projectStatusHistory */
        $projectStatusHistory = $this->entityManager->getRepository('projects_status_history');
        /** @var array $countByStatus */
        $countByStatus = $projectStatusHistory->countProjectsHavingHadStatus([\projects_status::EN_FUNDING]);
        /** @var string $percentageOfAcceptedProjects */
        $percentageOfAcceptedProjects = bcmul(bcdiv($countByStatus[\projects_status::EN_FUNDING], $numberOfProjectRequests, 4), 100, 2);

        return $percentageOfAcceptedProjects;
    }

    private function getLendersByType()
    {
        /** @var \lenders_accounts $lenders */
        $lenders = $this->entityManager->getRepository('lenders_accounts');
        /** @var int $lendersPerson */
        $lendersPerson = $lenders->countLendersByClientType([\clients::TYPE_PERSON, \clients::TYPE_PERSON_FOREIGNER]);
        /** @var int $lendersLegalEntity */
        $lendersLegalEntity = $lenders->countLendersByClientType([\clients::TYPE_LEGAL_ENTITY, \clients::TYPE_LEGAL_ENTITY_FOREIGNER]);
        /** @var int $totalLenders */
        $totalLenders = bcadd($lendersPerson, $lendersLegalEntity);

        $lendersByType = [
            'person'      => [
                'count'      => $lendersPerson,
                'percentage' => round(bcmul(bcdiv($lendersPerson, $totalLenders, 4), 100, 2))
            ],
            'legalEntity' => [
                'count'      => $lendersLegalEntity,
                'percentage' => round(bcmul(bcdiv($lendersLegalEntity, $totalLenders, 4), 100, 2))
            ]
        ];

        return $lendersByType;
    }

    /**
     * @param \DateTime $startDate
     * @return int|string
     */
    private function getPercentageOfProjectsFundedIn24Hours(\DateTime $startDate)
    {
        /** @var \projects $projects */
        $projects                        = $this->entityManager->getRepository('projects');
        $countAllProjects                = $projects->countProjectsFundedSince($startDate);
        $numberOfProjectsFundedIn24Hours = $projects->countProjectsFundedIn24Hours($startDate);
        $percentageFunded24h             = $countAllProjects > 0 ? bcmul(bcdiv($numberOfProjectsFundedIn24Hours, $countAllProjects, 4), 100, 0) : 0;

        return $percentageFunded24h;
    }

    private function getSecondsForBid()
    {
        /** @var \bids $bids */
        $bids               = $this->entityManager->getRepository('bids');
        $maxCountBidsPerDay = $bids->getMaxCountBidsPerDay();
        $secondsPerDay      = 24 * 60 * 60;
        $secondsForBid      = bcdiv($secondsPerDay, $maxCountBidsPerDay, 0);

        return $secondsForBid;
    }

    private function calculateRegulatoryData()
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

            if ($year == '2013-2014') {
                $cohortStartDate = '2013-01-01 00:00:00';
                $cohortEndDate   = '2014-12-31 23:59:59';
            } else {
                $cohortStartDate = $year . '-01-01 00:00:00';
                $cohortEndDate   = $year . '-12-31 23:59:59';
            }

            $data['IRR'][$year]                                 = $this->IRRManager->getUnilendIRRByCohort($cohortStartDate, $cohortEndDate);
            $data['projects'][$year]                            = $fundedProjects[$year];

            $data['borrowed-capital'][$year]                    = $borrowedCapital[$year];
            $data['repaid-capital'][$year]                      = bcadd($repaidCapital[$year], bcadd($recoveryPaymentsHealthyProjects[$year], $recoveryPaymentsProblematicProjects[$year], 2), 2);
            $data['repaid-interest'][$year]                     = $repaidInterest[$year];
            $data['owed-healthy-interest'][$year]               = $interestHealthyProjects[$year];

            $data['future-owed-capital-healthy'][$year]         = $futureCapitalHealthyProjects[$year];
            $data['future-owed-capital-problematic'][$year]     = $futureCapitalProblematicProjects[$year];
            $data['future-owed-capital'][$year]                 = bcadd($data['future-owed-capital-healthy'][$year],$data['future-owed-capital-problematic'][$year], 2);

            $data['late-owed-capital-problematic'][$year]       = bcsub($lateCapitalRepaymentsProblematicProjects[$year], $recoveryPaymentsProblematicProjects[$year], 2);
            $data['late-owed-capital-healthy'][$year]           = bcsub($lateCapitalRepaymentsHealthyProjects[$year], $recoveryPaymentsHealthyProjects[$year], 2);
            $data['late-owed-capital'][$year]                   = bcadd($data['late-owed-capital-problematic'][$year], $data['late-owed-capital-healthy'][$year], 2);

            $data['global-owed-capital'][$year]                     = bcadd($data['late-owed-capital'][$year], $data['future-owed-capital'][$year], 2);

            $data['total-owed-problematic-and-late-capital'][$year] = bcadd($data['future-owed-capital-problematic'][$year], $data['late-owed-capital'][$year], 2);
            $data['total-owed-problematic-capital'][$year]          = bcadd($data['future-owed-capital-problematic'][$year], $data['late-owed-capital-problematic'][$year], 2);

            //percentages
            $data['pct']['owed-problematic-over-borrowed-capital'][$year] = $data['borrowed-capital'][$year] > 0 ? bcmul(bcdiv($data['total-owed-problematic-and-late-capital'][$year], $data['borrowed-capital'][$year], 4), 100, 2) : 0;
            $data['pct']['interest-over-owed-problematic-capital'][$year] = $data['total-owed-problematic-and-late-capital'][$year] > 0 ? bcmul(bcdiv(($data['repaid-interest'][$year] + $data['owed-healthy-interest'][$year]), $data['total-owed-problematic-and-late-capital'][$year], 4), 100, 2) : 0;

            $capitalAndInterestLessProblemsPerYear      = bcsub(bcadd(bcadd($data['borrowed-capital'][$year], $data['repaid-interest'][$year], 2), $data['owed-healthy-interest'][$year], 2), $data['total-owed-problematic-capital'][$year], 2);
            $data['pct']['expected-performance'][$year] = $data['borrowed-capital'][$year] > 0 ? bcmul((bcdiv($capitalAndInterestLessProblemsPerYear, $data['borrowed-capital'][$year], 4) - 1), 100, 2) : 0;
            $data['pct']['problematic-rate'][$year]     = bcmul(bcdiv($problematicCompanies[$year], $countFundedCompanies[$year], 4), 100, 2);
        }

        $data = $this->addTotalToData($data, $problematicCompanies, $countFundedCompanies);

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

    private function addTotalToData(&$data, $problematicCompanies, $countFundedCompanies)
    {
        $data['IRR']['total'] = $this->IRRManager->getLastUnilendIRR()['value'];

        foreach($data as $type => $numbers) {
            if (false === in_array($type, ['pct', 'IRR'])){
                $data[$type]['total'] = array_sum($numbers);
            }
        }

        $data = $this->addTotalPercentages($data, $problematicCompanies, $countFundedCompanies);

        return $data;
    }

    private function addTotalPercentages(&$data, $problematicCompanies, $countFundedCompanies)
    {
        $data['pct']['owed-problematic-over-borrowed-capital']['total'] = $data['borrowed-capital']['total'] > 0 ? bcmul(bcdiv($data['total-owed-problematic-and-late-capital']['total'], $data['borrowed-capital']['total'], 4), 100, 2) : 0;
        $data['pct']['interest-over-owed-problematic-capital']['total'] = bcmul(bcdiv(($data['repaid-interest']['total'] + $data['owed-healthy-interest']['total']), $data['total-owed-problematic-and-late-capital']['total'], 4), 100, 2);

        $capitalAndInterestLessProblems               = bcsub(bcadd(bcadd($data['borrowed-capital']['total'], $data['repaid-interest']['total']), $data['owed-healthy-interest']['total']), $data['total-owed-problematic-and-late-capital']['total']);
        $data['pct']['expected-performance']['total'] = bcmul(bcdiv($capitalAndInterestLessProblems, $data['borrowed-capital']['total'], 4) - 1, 100, 2);
        $data['pct']['problematic-rate']['total']     = bcmul(bcdiv(array_sum($problematicCompanies), array_sum($countFundedCompanies), 4), 100, 2);

        return $data;
    }

    private function calculateIncidenceRateOnIFPContracts()
    {
        /** @var \echeanciers $paymentSchedule */
        $paymentSchedule = $this->entityManager->getRepository('echeanciers');

        $problematicProjects = $paymentSchedule->getProblematicOwedCapitalByProjects(\underlying_contract::CONTRACT_IFP, 60);

        $allProjects = $paymentSchedule->getOwedCapitalByProjects(\underlying_contract::CONTRACT_IFP);
        $incidenceRate['amountIFP']   = bcmul(bcdiv(array_sum(array_column($problematicProjects, 'amount')), array_sum(array_column($allProjects, 'amount')), 4), 100, 2);
        $incidenceRate['projectsIFP'] = bcmul(bcdiv(count($problematicProjects), count($allProjects), 4), 100, 2);

        return $incidenceRate;
    }

}
