<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Psr\Cache\CacheItemPoolInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\UnilendStats;
use Unilend\Bundle\CoreBusinessBundle\Repository\ClientsRepository;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;
use Unilend\librairies\CacheKeys;

class StatisticsManager
{
    /**
     * Siren count has first started in an excel spreadsheet. Consequently DB data will always be inconsistent with previously announced data.
     * For that reason it has been decided to start counting only from a given date and adding this count to the historic value
     */
    const HISTORIC_NUMBER_OF_SIREN            = 26205;
    const VALUE_DATE_HISTORIC_NUMBER_OF_SIREN = '2016-08-31 00:00:00';
    /**
     * Day we started saving front statistics. Before that data there is no data.
     */
    const START_FRONT_STATISTICS_HISTORY      = '2016-11-17';

    /** @var EntityManagerSimulator */
    private $entityManagerSimulator;
    /** @var  EntityManager */
    private $entityManager;
    /** @var IRRManager */
    private $IRRManager;
    /** @var CacheItemPoolInterface */
    private $cachePool;
    /** @var LocationManager */
    private $locationManager;

    public function __construct(
        EntityManagerSimulator $entityManagerSimulator,
        EntityManager $entityManager,
        IRRManager $IRRManager,
        CacheItemPoolInterface $cachePool,
        LocationManager $locationManager
    )
    {
        $this->entityManagerSimulator = $entityManagerSimulator;
        $this->entityManager          = $entityManager;
        $this->IRRManager             = $IRRManager;
        $this->cachePool              = $cachePool;
        $this->locationManager        = $locationManager;
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
     * @return mixed|null
     */
    public function getStatisticsAtDate(\DateTime $date)
    {
        $today    = new \DateTime('NOW');
        $cacheKey = $date->format('Y-m-d') == $today->format('Y-m-d') ? CacheKeys::UNILEND_STATISTICS : CacheKeys::UNILEND_STATISTICS . '_' . $date->format('Y-m-d');

        $cachedItem = $this->cachePool->getItem($cacheKey);
        if (false === $cachedItem->isHit()) {
            if ($date->format('Y-m-d') == $today->format('Y-m-d')) {
                $statsEntry = $this->entityManager->getRepository('UnilendCoreBusinessBundle:UnilendStats')->findOneBy(['typeStat' => CacheKeys::UNILEND_STATISTICS], ['added' => 'DESC']);
            } else {
                $statsEntry = $this->entityManager->getRepository('UnilendCoreBusinessBundle:UnilendStats')->getStatisticAtDate($date, CacheKeys::UNILEND_STATISTICS);
            }
            $statistics = json_decode($statsEntry->getValue(), true);
            $cachedItem->set($statistics)->expiresAfter(CacheKeys::DAY);
            $this->cachePool->save($cachedItem);

            return $statistics;
        } else {
            return $cachedItem->get();
        }
    }

    public function calculateStatistics()
    {
        /** @var \projects $projects */
        $projects = $this->entityManagerSimulator->getRepository('projects');
        /** @var \loans $loans */
        $loans = $this->entityManagerSimulator->getRepository('loans');
        /** @var \DateTime $startDate voluntarily on last 6 Months except for average funding time which is on 4 month */
        $startDate = new \DateTime('NOW - 6 MONTHS');

        $clientRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients');

        $statistics = [
            'numberOfLendersInCommunity'      => $clientRepository->countLenders(),
            'numberOfActiveLenders'           => $clientRepository->countLenders(true),
            'numberOfFinancedProjects'        => $projects->countSelectProjectsByStatus(ProjectsStatus::AFTER_REPAYMENT),
            'numberOfProjectRequests'         => self::HISTORIC_NUMBER_OF_SIREN + $projects->getNumberOfUniqueProjectRequests(self::VALUE_DATE_HISTORIC_NUMBER_OF_SIREN),
            'averageFundingTime'              => $projects->getAverageFundingTime(new \DateTime('NOW - 4 MONTHS')),
            'averageInterestRateForLenders'   => $projects->getGlobalAverageRateOfFundedProjects(PHP_INT_MAX),
            'averageNumberOfLenders'          => $projects->getAverageNumberOfLendersForProject(),
            'averageProjectAmount'            => $projects->getAverageAmount(),
            'averageLoanAmount'               => $loans->getAverageLoanAmount(),
            'unilendIRR'                      => $this->IRRManager->getLastUnilendIRR()->getValue(),
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
        $projectStatusHistory = $this->entityManagerSimulator->getRepository('projects_status_history');
        /** @var array $countByStatus */
        $countByStatus = $projectStatusHistory->countProjectsHavingHadStatus([\projects_status::EN_FUNDING]);
        /** @var string $percentageOfAcceptedProjects */
        $percentageOfAcceptedProjects = bcmul(bcdiv($countByStatus[\projects_status::EN_FUNDING], $numberOfProjectRequests, 4), 100, 2);

        return $percentageOfAcceptedProjects;
    }

    private function getLendersByType()
    {
        /** @var ClientsRepository $clientRepository */
        $clientRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients');
        /** @var int $lendersPerson */
        $lendersPerson = $clientRepository->countLendersByClientType([Clients::TYPE_PERSON, Clients::TYPE_PERSON_FOREIGNER]);
        /** @var int $lendersLegalEntity */
        $lendersLegalEntity = $clientRepository->countLendersByClientType([Clients::TYPE_LEGAL_ENTITY, Clients::TYPE_LEGAL_ENTITY_FOREIGNER]);
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
        $projects                        = $this->entityManagerSimulator->getRepository('projects');
        $countAllProjects                = $projects->countProjectsFundedSince($startDate);
        $numberOfProjectsFundedIn24Hours = $projects->countProjectsFundedIn24Hours($startDate);
        $percentageFunded24h             = $countAllProjects > 0 ? bcmul(bcdiv($numberOfProjectsFundedIn24Hours, $countAllProjects, 4), 100, 0) : 0;

        return $percentageFunded24h;
    }

    private function getSecondsForBid()
    {
        /** @var \bids $bids */
        $bids               = $this->entityManagerSimulator->getRepository('bids');
        $maxCountBidsPerDay = $bids->getMaxCountBidsPerDay();
        $secondsPerDay      = 24 * 60 * 60;
        $secondsForBid      = bcdiv($secondsPerDay, $maxCountBidsPerDay, 0);

        return $secondsForBid;
    }

    private function calculateRegulatoryData()
    {
        $years = $this->getYearsRange();

        /** @var \loans $loans */
        $loans = $this->entityManagerSimulator->getRepository('loans');
        /** @var \echeanciers_emprunteur $borrowerPaymentSchedule */
        $borrowerPaymentSchedule = $this->entityManagerSimulator->getRepository('echeanciers_emprunteur');
        /** @var \projects $projects */
        $projects = $this->entityManagerSimulator->getRepository('projects');
        /** @var \companies $companies */
        $companies           = $this->entityManagerSimulator->getRepository('companies');
        $operationRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Operation');

        $borrowedCapital                             = $this->formatCohortQueryResult($loans->sumLoansByCohort(), $years);
        $repaidCapital                               = $this->formatCohortQueryResult($operationRepository->getTotalRepaymentByCohort(OperationType::CAPITAL_REPAYMENT), $years);
        $repaidCapitalRegularized                    = $this->formatCohortQueryResult($operationRepository->getTotalRepaymentByCohort(OperationType::CAPITAL_REPAYMENT_REGULARIZATION), $years);
        $debtCollectionRepaymentHealthyProjects      = $this->formatCohortQueryResult($operationRepository->getTotalDebtCollectionRepaymentByCohort(true), $years);
        $debtCollectionCommissionHealthyProjects     = $this->formatCohortQueryResult($operationRepository->getTotalDebtCollectionLenderCommissionByCohort(true), $years);
        $debtCollectionRepaymentProblematicProjects  = $this->formatCohortQueryResult($operationRepository->getTotalDebtCollectionRepaymentByCohort(false), $years);
        $debtCollectionCommissionProblematicProjects = $this->formatCohortQueryResult($operationRepository->getTotalDebtCollectionLenderCommissionByCohort(false), $years);
        $repaidInterest                              = $this->formatCohortQueryResult($operationRepository->getTotalRepaymentByCohort(OperationType::GROSS_INTEREST_REPAYMENT), $years);
        $repaidInterestRegularized                   = $this->formatCohortQueryResult($operationRepository->getTotalRepaymentByCohort(OperationType::GROSS_INTEREST_REPAYMENT_REGULARIZATION), $years);
        $interestHealthyProjects                     = $this->formatCohortQueryResult($borrowerPaymentSchedule->getInterestPaymentsOfHealthyProjectsByCohort(), $years);
        $futureCapitalProblematicProjects            = $this->formatCohortQueryResult($borrowerPaymentSchedule->getFutureOwedCapitalOfProblematicProjectsByCohort(), $years);
        $futureCapitalHealthyProjects                = $this->formatCohortQueryResult($borrowerPaymentSchedule->getFutureCapitalPaymentsOfHealthyProjectsByCohort(), $years);
        $lateCapitalRepaymentsHealthyProjects        = $this->formatCohortQueryResult($borrowerPaymentSchedule->getLateCapitalRepaymentsHealthyProjects(), $years);
        $lateCapitalRepaymentsProblematicProjects    = $this->formatCohortQueryResult($borrowerPaymentSchedule->getLateCapitalRepaymentsProblematicProjects(), $years);
        $countFundedCompanies                        = $this->formatCohortQueryResult($companies->countCompaniesFundedByCohort(), $years);
        $fundedProjects                              = $this->formatCohortQueryResult($projects->countFundedProjectsByCohort(), $years);
        $problematicCompanies                        = $this->formatCohortQueryResult($companies->countCompaniesWithProblematicProjectsByCohort(), $years);

        $data = [];

        foreach ($years as $year) {

            if ($year == '2013-2014') {
                $cohortStartDate = '2013-01-01 00:00:00';
                $cohortEndDate   = '2014-12-31 23:59:59';
            } else {
                $cohortStartDate = $year . '-01-01 00:00:00';
                $cohortEndDate   = $year . '-12-31 23:59:59';
            }

            try {
                $data['IRR'][$year] = $this->IRRManager->getUnilendIRRByCohort($cohortStartDate, $cohortEndDate);
            } catch (\Exception $exception){
                $data['IRR'][$year] = 'NA';
            }

            $data['projects'][$year]                            = $fundedProjects[$year];

            $data['borrowed-capital'][$year]                    = $borrowedCapital[$year];
            $data['repaid-capital'][$year]                      = bcsub($repaidCapital[$year], $repaidCapitalRegularized[$year], 2);
            $data['repaid-interest'][$year]                     = bcsub($repaidInterest[$year], $repaidInterestRegularized[$year], 2);
            $data['owed-healthy-interest'][$year]               = $interestHealthyProjects[$year];

            $data['future-owed-capital-healthy'][$year]         = $futureCapitalHealthyProjects[$year];
            $data['future-owed-capital-problematic'][$year]     = $futureCapitalProblematicProjects[$year];
            $data['future-owed-capital'][$year]                 = bcadd($data['future-owed-capital-healthy'][$year],$data['future-owed-capital-problematic'][$year], 2);

            $data['late-owed-capital-problematic'][$year]       = bcsub($lateCapitalRepaymentsProblematicProjects[$year], bcsub($debtCollectionRepaymentProblematicProjects[$year], $debtCollectionCommissionProblematicProjects[$year], 2), 2);
            $data['late-owed-capital-healthy'][$year]           = bcsub($lateCapitalRepaymentsHealthyProjects[$year], bcsub($debtCollectionRepaymentHealthyProjects[$year], $debtCollectionCommissionHealthyProjects[$year], 2), 2);
            $data['late-owed-capital'][$year]                   = bcadd($data['late-owed-capital-problematic'][$year], $data['late-owed-capital-healthy'][$year], 2);

            $data['global-owed-capital'][$year]                     = bcadd($data['late-owed-capital'][$year], $data['future-owed-capital'][$year], 2);

            $data['total-owed-problematic-and-late-capital'][$year] = bcadd($data['future-owed-capital-problematic'][$year], $data['late-owed-capital'][$year], 2);
            $data['total-owed-problematic-capital'][$year]          = bcadd($data['future-owed-capital-problematic'][$year], $data['late-owed-capital-problematic'][$year], 2);

            //percentages
            $data['pct']['owed-problematic-over-borrowed-capital'][$year] = $data['borrowed-capital'][$year] > 0 ? bcmul(bcdiv($data['total-owed-problematic-and-late-capital'][$year], $data['borrowed-capital'][$year], 4), 100, 2) : 0;
            $data['pct']['interest-over-owed-problematic-capital'][$year] = $data['total-owed-problematic-and-late-capital'][$year] > 0 ? bcmul(bcdiv(($data['repaid-interest'][$year] + $data['owed-healthy-interest'][$year]), $data['total-owed-problematic-and-late-capital'][$year], 4), 100, 2) : 0;

            $capitalAndInterestLessProblemsPerYear      = bcsub(bcadd(bcadd($data['borrowed-capital'][$year], $data['repaid-interest'][$year], 2), $data['owed-healthy-interest'][$year], 2), $data['total-owed-problematic-capital'][$year], 2);
            $data['pct']['expected-performance'][$year] = $data['borrowed-capital'][$year] > 0 ? bcmul((bcdiv($capitalAndInterestLessProblemsPerYear, $data['borrowed-capital'][$year], 4) - 1), 100, 2) : 0;
            $data['pct']['problematic-rate'][$year]     = $countFundedCompanies[$year] > 0 ? bcmul(bcdiv($problematicCompanies[$year], $countFundedCompanies[$year], 4), 100, 2) : 0;
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
        $data['IRR']['total'] = $this->IRRManager->getLastUnilendIRR()->getValue();

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
        $data['pct']['interest-over-owed-problematic-capital']['total'] = $data['total-owed-problematic-and-late-capital']['total'] > 0 ? bcmul(bcdiv(($data['repaid-interest']['total'] + $data['owed-healthy-interest']['total']), $data['total-owed-problematic-and-late-capital']['total'], 4), 100, 2) : 0;

        $capitalAndInterestLessProblems               = bcsub(bcadd(bcadd($data['borrowed-capital']['total'], $data['repaid-interest']['total']), $data['owed-healthy-interest']['total']), $data['total-owed-problematic-and-late-capital']['total']);
        $data['pct']['expected-performance']['total'] = $data['borrowed-capital']['total'] > 0 ? bcmul(bcdiv($capitalAndInterestLessProblems, $data['borrowed-capital']['total'], 4) - 1, 100, 2) : 0;
        $data['pct']['problematic-rate']['total']     = bcmul(bcdiv(array_sum($problematicCompanies), array_sum($countFundedCompanies), 4), 100, 2);

        return $data;
    }

    private function calculateIncidenceRateOnIFPContracts()
    {
        /** @var \echeanciers $paymentSchedule */
        $paymentSchedule = $this->entityManagerSimulator->getRepository('echeanciers');

        $problematicProjectsIfp       = $paymentSchedule->getProblematicOwedCapitalByProjects(\underlying_contract::CONTRACT_IFP, 60);
        $allProjectsIfp               = $paymentSchedule->getOwedCapitalByProjects(\underlying_contract::CONTRACT_IFP);
        $incidenceRate['amountIFP']   = bcmul(bcdiv(array_sum(array_column($problematicProjectsIfp, 'amount')), array_sum(array_column($allProjectsIfp, 'amount')), 4), 100, 2);
        $incidenceRate['projectsIFP'] = bcmul(bcdiv(count($problematicProjectsIfp), count($allProjectsIfp), 4), 100, 2);

        $problematicProjectsCip       = $paymentSchedule->getProblematicOwedCapitalByProjects(\underlying_contract::CONTRACT_MINIBON, 60);
        $allProjectsCip               = $paymentSchedule->getOwedCapitalByProjects(\underlying_contract::CONTRACT_MINIBON);
        $incidenceRate['amountCIP']   = bcmul(bcdiv(array_sum(array_column($problematicProjectsCip, 'amount')), array_sum(array_column($allProjectsCip, 'amount')), 4), 100, 2);
        $incidenceRate['projectsCIP'] = bcmul(bcdiv(count($problematicProjectsCip), count($allProjectsCip), 4), 100, 2);

        return $incidenceRate;
    }

    /**
     * @param \DateTime $date
     *
     * @return array
     */
    public function calculatePerformanceIndicators(\DateTime $date)
    {
        $projectRepository         = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects');
        $paymentScheduleRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur');

        $years                          = $this->getYearsRange();
        $regulatoryData                 = $this->getStatisticsAtDate($date)['regulatoryData'];
        $weightedAverageInterestRate    = $this->formatCohortQueryResult($projectRepository->getWeightedAverageInterestRateByCohortUntil(), $years);
        $notWeightedAverageInterestRate = $this->formatCohortQueryResult($projectRepository->getNonWeightedAverageInterestRateByCohortUntil(), $years);
        $weightedAveragePeriod          = $this->formatCohortQueryResult($projectRepository->getWeightedAveragePeriodByCohortUntil(), $years);
        $notWeightedAveragePeriod       = $this->formatCohortQueryResult($projectRepository->getNonWeightedAveragePeriodByCohortUntil(), $years);
        $totalInterest                  = $this->formatCohortQueryResult($paymentScheduleRepository->getTotalInterestToBePaidByCohortUntil(), $years);
        $numberLateHealthyProjects      = $this->formatCohortQueryResult($projectRepository->getCountProjectsWithLateRepayments(true), $years);
        $numberLateProblematicProjects  = $this->formatCohortQueryResult($projectRepository->getCountProjectsWithLateRepayments(false), $years);

        $data = [];
        foreach ($years as $year) {
            if ($year == '2013-2014') {
                $cohortStartDate = '2013-01-01 00:00:00';
                $cohortEndDate   = '2014-12-31 23:59:59';
            } else {
                $cohortStartDate = $year . '-01-01 00:00:00';
                $cohortEndDate   = $year . '-12-31 23:59:59';
            }

            try {
                $data['optimistic-unilend-irr'][$year] = $this->IRRManager->getOptimisticUnilendIRRByCohort($cohortStartDate, $cohortEndDate);
            } catch (\Exception $exception) {
                $data['optimistic-unilend-irr'][$year] = 'NA';
            }

            $data['borrowed-capital'][$year]                    = $regulatoryData['borrowed-capital'][$year];
            $data['number-of-projects'][$year]                  = $regulatoryData['projects'][$year];
            $data['average-borrowed-amount'][$year]             = round(bcdiv($regulatoryData['borrowed-capital'][$year], $regulatoryData['projects'][$year], 4));
            $data['average-interest-rate'][$year]               = [
                'volume' => $weightedAverageInterestRate[$year],
                'number' => $notWeightedAverageInterestRate[$year]
            ];
            $data['average-period'][$year]                      = [
                'volume' => $weightedAveragePeriod[$year],
                'number' => $notWeightedAveragePeriod[$year]
            ];
            $data['average-loan-age'][$year]                    = [
                'volume' => 0, //@TODO calcul à faire
                'number' => 0 //@TODO calcul à faire
            ];
            $data['repaid-capital'][$year]                      = $regulatoryData['repaid-capital'][$year];
            $data['repaid-capital-ratio'][$year]                = round(bcmul(bcdiv($data['repaid-capital'][$year], $data['borrowed-capital'][$year], 6), 100, 3), 2);
            $data['repaid-interest'][$year]                     = $regulatoryData['repaid-interest'][$year];
            $data['repaid-interest-ratio'][$year]               = round(bcmul(bcdiv($data['repaid-interest'][$year], $totalInterest[$year], 6), 100, 3), 2);
            $data['realistic-unilend-irr'][$year]               = $regulatoryData['IRR'][$year];
            $data['annual-cost-of-risk'][$year]                 = 'NA' === $data['optimistic-unilend-irr'][$year] ? 'NA' : bcsub($data['optimistic-unilend-irr'][$year], $data['realistic-unilend-irr'][$year], 4);
            $data['late-owed-capital-healthy'][$year]           = $regulatoryData['late-owed-capital-healthy'][$year];
            $data['late-capital-percentage'][$year]             = [
                'volume' => round(bcmul(bcdiv($data['late-owed-capital-healthy'][$year], $data['borrowed-capital'][$year], 6), 100, 3), 2),
                'number' => round(bcdiv($numberLateHealthyProjects[$year], $data['number-of-projects'][$year], 6), 2)
            ];
            $data['late-owed-capital-problematic'][$year]       = $regulatoryData['late-owed-capital-problematic'][$year];
            $data['late-problematic-capital-percentage'][$year] = [
                'volume' => round(bcmul(bcdiv($data['late-owed-capital-problematic'][$year], $data['borrowed-capital'][$year], 6), 100, 3), 2),
                'number' => round(bcdiv($numberLateProblematicProjects[$year], $data['number-of-projects'][$year], 6), 2)
            ];
        }

        try {
            $data['optimistic-unilend-irr']['total'] = $this->IRRManager->getOptimisticUnilendIRR();
        } catch (\Exception $exception) {
            $data['optimistic-unilend-irr']['total'] = 'NA';
        }

        $data['borrowed-capital']['total']                    = $regulatoryData['borrowed-capital']['total'];
        $data['average-borrowed-amount']['total']             = round(bcdiv($regulatoryData['borrowed-capital']['total'], $regulatoryData['projects']['total'], 4));
        $data['number-of-projects']['total']                  = $regulatoryData['projects']['total'];
        $data['repaid-capital']['total']                      = $regulatoryData['repaid-capital']['total'];
        $data['repaid-interest']['total']                     = $regulatoryData['repaid-interest']['total'];
        $data['repaid-capital-ratio']['total']                = round(bcdiv($data['repaid-capital']['total'], $data['borrowed-capital']['total'], 6), 2);
        $data['repaid-interest-ratio']['total']               = round(bcdiv($data['repaid-interest']['total'], array_sum($totalInterest), 4), 2);
        $data['late-owed-capital-healthy']['total']           = $regulatoryData['late-owed-capital-healthy']['total'];
        $data['late-owed-capital-problematic']['total']       = $regulatoryData['late-owed-capital-problematic']['total'];
        $data['realistic-unilend-irr']['total']               = $regulatoryData['IRR']['total'];
        $data['annual-cost-of-risk']['total']                 = 'NA' === $data['optimistic-unilend-irr']['total'] ? 'NA' : bcsub($data['optimistic-unilend-irr']['total'], $data['realistic-unilend-irr']['total'], 4);
        $data['average-interest-rate']['total']               = [
            'volume' => $this->getStatistic('averageInterestRateForLenders', $date),
            'number' => $projectRepository->getNonWeightedAverageInterestRateUntil()
        ];
        $data['late-capital-percentage']['total']             = [
            'volume' => round(bcmul(bcdiv($data['late-owed-capital-healthy']['total'], $data['borrowed-capital']['total'], 6), 100, 3), 2),
            'number' => round(bcdiv(array_sum($numberLateHealthyProjects), $data['number-of-projects']['total'], 6), 2)
        ];
        $data['late-problematic-capital-percentage']['total'] = [
            'volume' => round(bcmul(bcdiv($data['late-owed-capital-problematic']['total'], $data['borrowed-capital']['total'], 6), 100, 3), 2),
            'number' => round(bcdiv(array_sum($numberLateProblematicProjects), $data['number-of-projects']['total'], 6), 2)
        ];
        $data['average-loan-age']['total']                    = [
            'volume' => 0, //@TODO calcul à faire
            'number' => 0 //@TODO calcul à faire
        ];

        $data['average-period']['total'] = [
            'volume' => $projectRepository->getWeightedAveragePeriodUntil(),
            'number' => $projectRepository->getNonWeightedAveragePeriodUntil()
        ];

        return $data;

    }
    /**
     * @return array
     */
    private function getYearsRange()
    {
        return array_merge(['2013-2014'], range(2015, date('Y')));
    }


    /**
     * @param \DateTime $date
     * @return mixed|null
     */
    public function getPerformanceIndicatorAtDate(\DateTime $date)
    {
        $today    = new \DateTime('NOW');
        $cacheKey = $date->format('Y-m-d') == $today->format('Y-m-d') ? CacheKeys::UNILEND_PERFORMANCE_INDICATOR : CacheKeys::UNILEND_PERFORMANCE_INDICATOR . '_' . $date->format('Y-m-d');
        $statsEntry = $this->entityManager->getRepository('UnilendCoreBusinessBundle:UnilendStats')->findOneBy(['typeStat' => UnilendStats::TYPE_FPF_FRONT_STATISTIC], ['added' => 'DESC']);

        return  json_decode($statsEntry->getValue(), true);


        $cachedItem = $this->cachePool->getItem($cacheKey);
        if (false === $cachedItem->isHit()) {
            if ($date->format('Y-m-d') == $today->format('Y-m-d')) {
                $statsEntry = $this->entityManager->getRepository('UnilendCoreBusinessBundle:UnilendStats')->findOneBy(['typeStat' => UnilendStats::TYPE_FPF_FRONT_STATISTIC], ['added' => 'DESC']);
            } else {
                $statsEntry = $this->entityManager->getRepository('UnilendCoreBusinessBundle:UnilendStats')->findOneBy(['typeStat' => UnilendStats::TYPE_FPF_FRONT_STATISTIC, 'added' => $date]);
            }
            $statistics = json_decode($statsEntry->getValue(), true);
            $cachedItem->set($statistics)->expiresAfter(CacheKeys::LONG_TIME);
            $this->cachePool->save($cachedItem);

            return $statistics;
        } else {
            return $cachedItem->get();
        }
    }

    public function getAvailableDatesForFPFStatistics()
    {
        $availableDates = [];

        foreach ($this->entityManager->getRepository('UnilendCoreBusinessBundle:UnilendStats')->getAvailableDatesForStatisticType(UnilendStats::TYPE_FPF_FRONT_STATISTIC) as $date) {
            $availableDates[] = $date['added']->format('d/m/Y');
        }

        return $availableDates;
    }
}
