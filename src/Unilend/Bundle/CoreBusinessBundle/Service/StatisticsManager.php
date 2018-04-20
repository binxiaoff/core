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
    const START_FRONT_STATISTICS_HISTORY = '2016-11-17';
    const START_FPF_STATISTIC_HISTORY    = '2017-10-30';
    const START_INCIDENCE_RATE_IFP       = '2015-12-31';
    const START_INCIDENCE_RATE_CIP       = '2016-11-30';

    /** Constants to make method calls more readable */
    const GROUP_FIRST_YEAR_COHORT = false;
    const HEALTHY_PROJECTS        = true;
    const PROBLEMATIC_PROJECTS    = false;

    const NOT_APPLICABLE = 'NA';

    /** Time frames defined in ACPR 2017-P-02 */
    const ACPR_INCIDENCE_RATE_LATE_MONTH = 2;
    const ACPR_CALCULATION_PERIOD_MONTHS = 36;

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
     * @param string    $name
     * @param \DateTime $date
     *
     * @return mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getStatistic(string $name, \DateTime $date)
    {
        if ($name === 'incidenceRate') {
            return $this->getIncidenceRateAtDate($date);
        }

        if ($name === 'performanceIndicator') {
            return $this->getPerformanceIndicatorAtDate($date);
        }

        $statistics = $this->getStatisticsAtDate($date);

        return $statistics[lcfirst($name)];
    }

    /**
     * @param \DateTime $date
     *
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getStatisticsAtDate(\DateTime $date): array
    {
        $today    = new \DateTime('NOW');
        $cacheKey = $date->format('Y-m-d') == $today->format('Y-m-d') ? CacheKeys::UNILEND_STATISTICS : CacheKeys::UNILEND_STATISTICS . '_' . $date->format('Y-m-d');

        $cachedItem = $this->cachePool->getItem($cacheKey);
        if (false === $cachedItem->isHit()) {
            if ($date->format('Y-m-d') == $today->format('Y-m-d')) {
                $statsEntry = $this->entityManager->getRepository('UnilendCoreBusinessBundle:UnilendStats')->findOneBy(['typeStat' => CacheKeys::UNILEND_STATISTICS], ['added' => 'DESC']);
            } else {
                $statsEntry = $this->entityManager->getRepository('UnilendCoreBusinessBundle:UnilendStats')->findStatisticAtDate($date, CacheKeys::UNILEND_STATISTICS);
            }
            $statistics = json_decode($statsEntry->getValue(), true);
            $cachedItem->set($statistics)->expiresAfter(CacheKeys::LONG_TIME);
            $this->cachePool->save($cachedItem);

            return $statistics;
        } else {
            return $cachedItem->get();
        }
    }

    /**
     * @param \DateTime $date
     *
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getPerformanceIndicatorAtDate(\DateTime $date): array
    {
        $today      = new \DateTime('NOW');
        $cacheKey   = $date->format('Y-m-d') == $today->format('Y-m-d') ? CacheKeys::UNILEND_PERFORMANCE_INDICATOR : CacheKeys::UNILEND_PERFORMANCE_INDICATOR . '_' . $date->format('Y-m-d');
        $cachedItem = $this->cachePool->getItem($cacheKey);

        if (false === $cachedItem->isHit()) {
            if ($date->format('Y-m-d') == $today->format('Y-m-d')) {
                $statsEntry = $this->entityManager->getRepository('UnilendCoreBusinessBundle:UnilendStats')->findOneBy(['typeStat' => UnilendStats::TYPE_FPF_FRONT_STATISTIC], ['added' => 'DESC']);
            } else {
                $statsEntry = $this->entityManager->getRepository('UnilendCoreBusinessBundle:UnilendStats')->findStatisticAtDate($date, UnilendStats::TYPE_FPF_FRONT_STATISTIC);
            }
            $statistics = json_decode($statsEntry->getValue(), true);
            $cachedItem->set($statistics)->expiresAfter(CacheKeys::LONG_TIME);
            $this->cachePool->save($cachedItem);

            return $statistics;
        } else {
            return $cachedItem->get();
        }
    }

    /**
     * @param \DateTime $date
     *
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getIncidenceRateAtDate(\DateTime $date): array
    {
        $today    = new \DateTime('NOW');
        $cacheKey = $date->format('Y-m-d') == $today->format('Y-m-d') ? CacheKeys::UNILEND_INCIDENCE_RATE : CacheKeys::UNILEND_INCIDENCE_RATE . '_' . $date->format('Y-m-d');

        $cachedItem = $this->cachePool->getItem($cacheKey);
        if (false === $cachedItem->isHit()) {
            if ($date->format('Y-m-d') == $today->format('Y-m-d')) {
                $statsEntry = $this->entityManager->getRepository('UnilendCoreBusinessBundle:UnilendStats')->findOneBy(['typeStat' => UnilendStats::TYPE_INCIDENCE_RATE], ['added' => 'DESC']);
            } else {
                $statsEntry = $this->entityManager->getRepository('UnilendCoreBusinessBundle:UnilendStats')->findStatisticAtDate($date, UnilendStats::TYPE_INCIDENCE_RATE);
            }
            $statistics = json_decode($statsEntry->getValue(), true);
            $cachedItem->set($statistics)->expiresAfter(CacheKeys::LONG_TIME);
            $this->cachePool->save($cachedItem);

            return $statistics;
        } else {
            return $cachedItem->get();
        }
    }

    /**
     * @return array
     */
    public function getAvailableDatesForFPFStatistics(): array
    {
        $availableDates = [];
        foreach ($this->entityManager->getRepository('UnilendCoreBusinessBundle:UnilendStats')->getAvailableDatesForStatisticType(UnilendStats::TYPE_FPF_FRONT_STATISTIC) as $date) {
            $availableDates[] = $date['added'];
        }

        return $availableDates;
    }

    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function saveQuarterIncidenceRate(): void
    {
        $unilendStatRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:UnilendStats');
        $thirdMonth            = new \DateTime('Last day of last month');
        $secondMonth           = new \DateTime('Last day of 2 months ago');
        $firstMonth            = new \DateTime('Last day of 3 months ago');

        $thirdMonthStat  = $unilendStatRepository->findStatisticAtDate($thirdMonth, UnilendStats::TYPE_INCIDENCE_RATE);
        $secondMonthStat = $unilendStatRepository->findStatisticAtDate($secondMonth, UnilendStats::TYPE_INCIDENCE_RATE);
        $firstMonthStat  = $unilendStatRepository->findStatisticAtDate($firstMonth, UnilendStats::TYPE_INCIDENCE_RATE);

        $thirdMonthData  = json_decode($thirdMonthStat->getValue(), true);
        $secondMonthData = json_decode($secondMonthStat->getValue(), true);
        $firstMonthData  = json_decode($firstMonthStat->getValue(), true);

        if (null === $thirdMonthData || false === isset($thirdMonthData['ratioIFP'])) {
            throw new \Exception('There is no ratio IFP for ' . $thirdMonth->format('Y-m-d'));
        }

        if (null === $secondMonthData || false === isset($secondMonthData['ratioIFP'])) {
            throw new \Exception('There is no ratio IFP for ' . $secondMonth->format('Y-m-d'));
        }

        if (null === $firstMonthData || false === isset($firstMonthData['ratioIFP'])) {
            throw new \Exception('There is no ratio IFP for ' . $firstMonth->format('Y-m-d'));
        }

        if (null === $thirdMonthData || false === isset($thirdMonthData['ratioCIP'])) {
            throw new \Exception('There is no ratio CIP for ' . $thirdMonth->format('Y-m-d'));
        }

        if (null === $secondMonthData || false === isset($secondMonthData['ratioCIP'])) {
            throw new \Exception('There is no ratio CIP for ' . $secondMonth->format('Y-m-d'));
        }

        if (null === $firstMonthData || false === isset($firstMonthData['ratioCIP'])) {
            throw new \Exception('There is no ratio CIP for ' . $firstMonth->format('Y-m-d'));
        }

        $thirdMonthData['quarterRatioIFP'] = round(bcdiv(bcadd(bcadd($thirdMonthData['ratioIFP'], $secondMonthData['ratioIFP'], 4), $firstMonthData['ratioIFP'], 4), 3, 4), 2);
        $thirdMonthData['quarterRatioCIP'] = round(bcdiv(bcadd(bcadd($thirdMonthData['ratioCIP'], $secondMonthData['ratioCIP'], 4), $firstMonthData['ratioCIP'], 4), 3, 4), 2);

        $quarterStat = new UnilendStats();
        $quarterStat
            ->setTypeStat(UnilendStats::TYPE_QUARTER_INCIDENCE_RATE)
            ->setValue(json_encode($thirdMonthData));

        $this->entityManager->persist($quarterStat);
        $this->entityManager->flush($quarterStat);
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function saveFrontStatistics(): void
    {
        $statistics = $this->calculateStatistics();
        $frontStats = new UnilendStats();
        $frontStats
            ->setTypeStat(UnilendStats::TYPE_STAT_FRONT_STATISTIC)
            ->setValue(json_encode($statistics));

        $this->entityManager->persist($frontStats);
        $this->entityManager->flush($frontStats);
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function savePerformanceIndicators(): void
    {
        $fpfStatistics = $this->calculatePerformanceIndicators();
        $fpfStats      = new UnilendStats();
        $fpfStats
            ->setTypeStat(UnilendStats::TYPE_FPF_FRONT_STATISTIC)
            ->setValue(json_encode($fpfStatistics));

        $this->entityManager->persist($fpfStats);
        $this->entityManager->flush($fpfStats);
    }

    /**
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function saveIncidenceRate(): void
    {
        $incidenceRate      = $this->calculateIncidenceRate();
        $incidenceRateStats = new UnilendStats();
        $incidenceRateStats
            ->setTypeStat(UnilendStats::TYPE_INCIDENCE_RATE)
            ->setValue(json_encode($incidenceRate));

        $this->entityManager->persist($incidenceRateStats);
        $this->entityManager->flush($incidenceRateStats);
    }

    /**
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    private function calculateStatistics(): array
    {
        /** @var \projects $projects */
        $projects = $this->entityManagerSimulator->getRepository('projects');
        /** @var \loans $loans */
        $loans            = $this->entityManagerSimulator->getRepository('loans');
        $startDate        = new \DateTime('NOW - 6 MONTHS');
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

    /**
     * @return array
     */
    private function getLendersByType(): array
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
     *
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

    /**
     * @return string
     */
    private function getSecondsForBid(): string
    {
        /** @var \bids $bids */
        $bids               = $this->entityManagerSimulator->getRepository('bids');
        $maxCountBidsPerDay = $bids->getMaxCountBidsPerDay();
        $secondsPerDay      = 24 * 60 * 60;
        $secondsForBid      = bcdiv($secondsPerDay, $maxCountBidsPerDay, 0);

        return $secondsForBid;
    }

    /**
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    private function calculateRegulatoryData(): array
    {
        $years = array_merge(['2013-2014'], range(2015, date('Y')));

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
        $debtCollectionRepaymentHealthyProjects      = $this->formatCohortQueryResult($operationRepository->getTotalDebtCollectionRepaymentByCohort(self::HEALTHY_PROJECTS), $years);
        $debtCollectionCommissionHealthyProjects     = $this->formatCohortQueryResult($operationRepository->getTotalDebtCollectionLenderCommissionByCohort(self::HEALTHY_PROJECTS), $years);
        $debtCollectionRepaymentProblematicProjects  = $this->formatCohortQueryResult($operationRepository->getTotalDebtCollectionRepaymentByCohort(self::PROBLEMATIC_PROJECTS), $years);
        $debtCollectionCommissionProblematicProjects = $this->formatCohortQueryResult($operationRepository->getTotalDebtCollectionLenderCommissionByCohort(self::PROBLEMATIC_PROJECTS), $years);
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
                $data['IRR'][$year] = self::NOT_APPLICABLE;
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

    /**
     * @param array $datas
     * @param array $years
     *
     * @return array
     */
    private function formatCohortQueryResult(array $datas, array $years): array
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

    /**
     * @param array $data
     * @param array $problematicCompanies
     * @param array $countFundedCompanies
     *
     * @return array
     */
    private function addTotalToData(array &$data, array $problematicCompanies, array $countFundedCompanies): array
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

    /**
     * @param array $data
     * @param array $problematicCompanies
     * @param array $countFundedCompanies
     *
     * @return array
     */
    private function addTotalPercentages(array &$data, array $problematicCompanies, array $countFundedCompanies): array
    {
        $data['pct']['owed-problematic-over-borrowed-capital']['total'] = $data['borrowed-capital']['total'] > 0 ? bcmul(bcdiv($data['total-owed-problematic-and-late-capital']['total'], $data['borrowed-capital']['total'], 4), 100, 2) : 0;
        $data['pct']['interest-over-owed-problematic-capital']['total'] = $data['total-owed-problematic-and-late-capital']['total'] > 0 ? bcmul(bcdiv(($data['repaid-interest']['total'] + $data['owed-healthy-interest']['total']), $data['total-owed-problematic-and-late-capital']['total'], 4), 100, 2) : 0;

        $capitalAndInterestLessProblems               = bcsub(bcadd(bcadd($data['borrowed-capital']['total'], $data['repaid-interest']['total']), $data['owed-healthy-interest']['total']), $data['total-owed-problematic-and-late-capital']['total']);
        $data['pct']['expected-performance']['total'] = $data['borrowed-capital']['total'] > 0 ? bcmul(bcdiv($capitalAndInterestLessProblems, $data['borrowed-capital']['total'], 4) - 1, 100, 2) : 0;
        $data['pct']['problematic-rate']['total']     = bcmul(bcdiv(array_sum($problematicCompanies), array_sum($countFundedCompanies), 4), 100, 2);

        return $data;
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function calculateIncidenceRate(): array
    {
        /** @var \echeanciers $paymentSchedule */
        $paymentSchedule = $this->entityManagerSimulator->getRepository('echeanciers');

        $problematicProjectsIfp       = $paymentSchedule->getProblematicOwedCapitalByProjects(\underlying_contract::CONTRACT_IFP, self::ACPR_INCIDENCE_RATE_LATE_MONTH);
        $owedCapitalProjectsIfp       = $paymentSchedule->getProblematicOwedCapitalByProjects(\underlying_contract::CONTRACT_IFP);
        $allProjectsIfp               = $paymentSchedule->getOwedCapitalByProjects(\underlying_contract::CONTRACT_IFP);
        $incidenceRate['amountIFP']   = bcmul(bcdiv(array_sum(array_column($problematicProjectsIfp, 'amount')), array_sum(array_column($allProjectsIfp, 'amount')), 4), 100, 2);
        $incidenceRate['projectsIFP'] = bcmul(bcdiv(count($problematicProjectsIfp), count($allProjectsIfp), 4), 100, 2);
        $incidenceRate['ratioIFP']    = bcmul(bcdiv(count($owedCapitalProjectsIfp), count($allProjectsIfp), 4), 100, 2);

        $problematicProjectsCip       = $paymentSchedule->getProblematicOwedCapitalByProjects(\underlying_contract::CONTRACT_MINIBON, self::ACPR_INCIDENCE_RATE_LATE_MONTH);
        $owedCapitalProjectsCip       = $paymentSchedule->getProblematicOwedCapitalByProjects(\underlying_contract::CONTRACT_MINIBON);
        $allProjectsCip               = $paymentSchedule->getOwedCapitalByProjects(\underlying_contract::CONTRACT_MINIBON);
        $incidenceRate['amountCIP']   = bcmul(bcdiv(array_sum(array_column($problematicProjectsCip, 'amount')), array_sum(array_column($allProjectsCip, 'amount')), 4), 100, 2);
        $incidenceRate['projectsCIP'] = bcmul(bcdiv(count($problematicProjectsCip), count($allProjectsCip), 4), 100, 2);
        $incidenceRate['ratioCIP']    = bcmul(bcdiv(count($owedCapitalProjectsCip), count($allProjectsCip), 4), 100, 2);

        return $incidenceRate;
    }

    /**
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function calculatePerformanceIndicators(): array
    {
        $date = new \DateTime('NOW');
        /** @var \loans $loans */
        $loans = $this->entityManagerSimulator->getRepository('loans');
        /** @var \projects $projects */
        $projects = $this->entityManagerSimulator->getRepository('projects');
        /** @var \echeanciers_emprunteur $borrowerPaymentSchedule */
        $borrowerPaymentSchedule = $this->entityManagerSimulator->getRepository('echeanciers_emprunteur');

        $projectRepository         = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects');
        $paymentScheduleRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur');
        $operationRepository       = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Operation');

        $years                                       = range(2013, date('Y'));
        $borrowedCapital                             = $this->formatCohortQueryResult($loans->sumLoansByCohort(self::GROUP_FIRST_YEAR_COHORT), $years);
        $fundedProjects                              = $this->formatCohortQueryResult($projects->countFundedProjectsByCohort(self::GROUP_FIRST_YEAR_COHORT), $years);
        $repaidCapital                               = $this->formatCohortQueryResult($operationRepository->getTotalRepaymentByCohort(OperationType::CAPITAL_REPAYMENT, self::GROUP_FIRST_YEAR_COHORT), $years);
        $repaidCapitalRegularized                    = $this->formatCohortQueryResult($operationRepository->getTotalRepaymentByCohort(OperationType::CAPITAL_REPAYMENT_REGULARIZATION, self::GROUP_FIRST_YEAR_COHORT), $years);
        $repaidInterest                              = $this->formatCohortQueryResult($operationRepository->getTotalRepaymentByCohort(OperationType::GROSS_INTEREST_REPAYMENT, self::GROUP_FIRST_YEAR_COHORT), $years);
        $repaidInterestRegularized                   = $this->formatCohortQueryResult($operationRepository->getTotalRepaymentByCohort(OperationType::GROSS_INTEREST_REPAYMENT_REGULARIZATION, self::GROUP_FIRST_YEAR_COHORT), $years);
        $weightedAverageInterestRate                 = $this->formatCohortQueryResult($projectRepository->getWeightedAverageInterestRateByCohortUntil(self::GROUP_FIRST_YEAR_COHORT), $years);
        $notWeightedAverageInterestRate              = $this->formatCohortQueryResult($projectRepository->getNonWeightedAverageInterestRateByCohortUntil(self::GROUP_FIRST_YEAR_COHORT), $years);
        $weightedAveragePeriod                       = $this->formatCohortQueryResult($projectRepository->getWeightedAveragePeriodByCohortUntil(self::GROUP_FIRST_YEAR_COHORT), $years);
        $notWeightedAveragePeriod                    = $this->formatCohortQueryResult($projectRepository->getNonWeightedAveragePeriodByCohortUntil(self::GROUP_FIRST_YEAR_COHORT), $years);
        $weightedAverageLoanAge                      = $this->formatCohortQueryResult($projectRepository->getAverageLoanAgeByCohortUntil(true, self::GROUP_FIRST_YEAR_COHORT), $years);
        $NotWeightedAverageLoanAge                   = $this->formatCohortQueryResult($projectRepository->getAverageLoanAgeByCohortUntil(false, self::GROUP_FIRST_YEAR_COHORT), $years);
        $totalInterest                               = $this->formatCohortQueryResult($paymentScheduleRepository->getTotalInterestToBePaidByCohortUntil(self::GROUP_FIRST_YEAR_COHORT), $years);
        $numberLateHealthyProjects                   = $this->formatCohortQueryResult($projectRepository->getCountProjectsWithLateRepayments(self::HEALTHY_PROJECTS, self::GROUP_FIRST_YEAR_COHORT), $years);
        $numberLateProblematicProjects               = $this->formatCohortQueryResult($projectRepository->getCountProjectsWithLateRepayments(self::PROBLEMATIC_PROJECTS, self::GROUP_FIRST_YEAR_COHORT), $years);
        $lateCapitalRepaymentsHealthyProjects        = $this->formatCohortQueryResult($borrowerPaymentSchedule->getLateCapitalRepaymentsHealthyProjects(self::GROUP_FIRST_YEAR_COHORT), $years);
        $debtCollectionRepaymentHealthyProjects      = $this->formatCohortQueryResult($operationRepository->getTotalDebtCollectionRepaymentByCohort(self::HEALTHY_PROJECTS, self::GROUP_FIRST_YEAR_COHORT), $years);
        $debtCollectionCommissionHealthyProjects     = $this->formatCohortQueryResult($operationRepository->getTotalDebtCollectionLenderCommissionByCohort(self::HEALTHY_PROJECTS, self::GROUP_FIRST_YEAR_COHORT), $years);
        $lateCapitalRepaymentsProblematicProjects    = $this->formatCohortQueryResult($borrowerPaymentSchedule->getLateCapitalRepaymentsProblematicProjects(self::GROUP_FIRST_YEAR_COHORT), $years);
        $debtCollectionRepaymentProblematicProjects  = $this->formatCohortQueryResult($operationRepository->getTotalDebtCollectionRepaymentByCohort(self::PROBLEMATIC_PROJECTS, self::GROUP_FIRST_YEAR_COHORT), $years);
        $debtCollectionCommissionProblematicProjects = $this->formatCohortQueryResult($operationRepository->getTotalDebtCollectionLenderCommissionByCohort(self::PROBLEMATIC_PROJECTS, self::GROUP_FIRST_YEAR_COHORT), $years);
        $futureCapitalProblematicProjects            = $this->formatCohortQueryResult($borrowerPaymentSchedule->getFutureOwedCapitalOfProblematicProjectsByCohort(self::GROUP_FIRST_YEAR_COHORT), $years);

        $data = [];
        foreach ($years as $year) {
            $cohortStartDate = $year . '-01-01 00:00:00';
            $cohortEndDate   = $year . '-12-31 23:59:59';

            try {
                $data['optimistic-unilend-irr'][$year] = $this->IRRManager->getOptimisticUnilendIRRByCohort($cohortStartDate, $cohortEndDate);
            } catch (\Exception $exception) {
                $data['optimistic-unilend-irr'][$year] = self::NOT_APPLICABLE;
            }

            try {
                $data['realistic-unilend-irr'][$year] = $this->IRRManager->getUnilendIRRByCohort($cohortStartDate, $cohortEndDate);
            } catch (\Exception $exception) {
                $data['realistic-unilend-irr'][$year] = self::NOT_APPLICABLE;
            }

            $data['borrowed-capital'][$year]                 = round($borrowedCapital[$year]);
            $data['number-of-projects'][$year]               = $fundedProjects[$year];
            $data['number-late-healthy-projects'][$year]     = $numberLateHealthyProjects[$year];
            $data['number-late-problematic-projects'][$year] = $numberLateProblematicProjects[$year];
            $data['average-borrowed-amount'][$year]          = round(bcdiv($borrowedCapital[$year], $fundedProjects[$year], 4));
            $data['average-interest-rate'][$year]            = [
                'volume' => $weightedAverageInterestRate[$year],
                'number' => $notWeightedAverageInterestRate[$year]
            ];
            $data['average-period'][$year]                   = [
                'volume' => $weightedAveragePeriod[$year],
                'number' => $notWeightedAveragePeriod[$year]
            ];
            $data['average-loan-age'][$year]                 = [
                'volume' => $weightedAverageLoanAge[$year],
                'number' => $NotWeightedAverageLoanAge[$year]
            ];
            $data['repaid-capital'][$year]                   = round(bcsub($repaidCapital[$year], $repaidCapitalRegularized[$year], 4));
            $data['repaid-capital-ratio'][$year]             = round(bcmul(bcdiv($data['repaid-capital'][$year], $data['borrowed-capital'][$year], 6), 100, 3), 2);
            $data['repaid-interest'][$year]                  = round(bcsub($repaidInterest[$year], $repaidInterestRegularized[$year], 4), 2);
            $data['repaid-interest-ratio'][$year]            = round(bcmul(bcdiv($data['repaid-interest'][$year], $totalInterest[$year], 6), 100, 3), 2);
            $data['annual-cost-of-risk'][$year]              = self::NOT_APPLICABLE === $data['optimistic-unilend-irr'][$year] ? self::NOT_APPLICABLE : round(bcsub($data['optimistic-unilend-irr'][$year], $data['realistic-unilend-irr'][$year], 4), 2);
            $data['late-owed-capital-healthy'][$year]        = round(bcsub($lateCapitalRepaymentsHealthyProjects[$year], bcsub($debtCollectionRepaymentHealthyProjects[$year], $debtCollectionCommissionHealthyProjects[$year], 4), 4));
            $data['late-capital-percentage'][$year]          = [
                'volume' => round(bcmul(bcdiv($data['late-owed-capital-healthy'][$year], $data['borrowed-capital'][$year], 6), 100, 3), 2),
                'number' => round(bcmul(bcdiv($numberLateHealthyProjects[$year], $data['number-of-projects'][$year], 6), 100, 3), 2)
            ];
            $data['late-owed-capital-problematic'][$year]    = round(bcadd($futureCapitalProblematicProjects[$year], bcsub($lateCapitalRepaymentsProblematicProjects[$year], bcsub($debtCollectionRepaymentProblematicProjects[$year], $debtCollectionCommissionProblematicProjects[$year], 4), 4), 4));

            $data['late-problematic-capital-percentage'][$year] = [
                'volume' => round(bcmul(bcdiv($data['late-owed-capital-problematic'][$year], $data['borrowed-capital'][$year], 6), 100, 3), 2),
                'number' => round(bcmul(bcdiv($numberLateProblematicProjects[$year], $data['number-of-projects'][$year], 6), 100, 3), 2)
            ];
        }

        $data['borrowed-capital']['total']                    = array_sum($data['borrowed-capital']);
        $data['number-of-projects']['total']                  = array_sum($data['number-of-projects']);
        $data['average-borrowed-amount']['total']             = round(bcdiv($data['borrowed-capital']['total'], $data['number-of-projects']['total'], 4));
        $data['repaid-capital']['total']                      = array_sum($data['repaid-capital']);
        $data['repaid-interest']['total']                     = array_sum($data['repaid-interest']);
        $data['repaid-capital-ratio']['total']                = round(bcmul(bcdiv($data['repaid-capital']['total'], $data['borrowed-capital']['total'], 4), 100, 3), 2);
        $data['repaid-interest-ratio']['total']               = round(bcmul(bcdiv($data['repaid-interest']['total'], array_sum($totalInterest), 4), 100, 3), 2);
        $data['late-owed-capital-healthy']['total']           = array_sum($data['late-owed-capital-healthy']);
        $data['late-owed-capital-problematic']['total']       = array_sum($data['late-owed-capital-problematic']);
        $data['optimistic-unilend-irr']['total']              = $this->IRRManager->getLastOptimisticUnilendIRR()->getValue();
        $data['realistic-unilend-irr']['total']               = $this->IRRManager->getLastUnilendIRR()->getValue();
        $data['annual-cost-of-risk']['total']                 = self::NOT_APPLICABLE === $data['optimistic-unilend-irr']['total'] ? self::NOT_APPLICABLE : bcsub($data['optimistic-unilend-irr']['total'], $data['realistic-unilend-irr']['total'], 4);
        $data['average-interest-rate']['total']               = [
            'volume' => $this->getStatistic('averageInterestRateForLenders', $date),
            'number' => $projectRepository->getNonWeightedAverageInterestRateUntil()
        ];
        $data['late-capital-percentage']['total']             = [
            'volume' => round(bcmul(bcdiv($data['late-owed-capital-healthy']['total'], $data['borrowed-capital']['total'], 6), 100, 3), 2),
            'number' => round(bcmul(bcdiv(array_sum($numberLateHealthyProjects), $data['number-of-projects']['total'], 6), 100, 3), 2)
        ];
        $data['late-problematic-capital-percentage']['total'] = [
            'volume' => round(bcmul(bcdiv($data['late-owed-capital-problematic']['total'], $data['borrowed-capital']['total'], 6), 100, 3), 2),
            'number' => round(bcmul(bcdiv(array_sum($numberLateProblematicProjects), $data['number-of-projects']['total'], 6), 100, 3), 2)
        ];
        $data['average-loan-age']['total']                    = [
            'volume' => $projectRepository->getAverageLoanAgeUntil(true),
            'number' => $projectRepository->getAverageLoanAgeUntil(false)
        ];

        $data['average-period']['total'] = [
            'volume' => round($projectRepository->getWeightedAveragePeriodUntil(), 1),
            'number' => round($projectRepository->getNonWeightedAveragePeriodUntil(), 1)
        ];

        return $data;
    }

    /**
     * @param \DateTime $date
     *
     * @return array
     */
    public function getIncidenceRatesOfLast36Months(\DateTime $date): array
    {
        $data        = $this->entityManager->getRepository('UnilendCoreBusinessBundle:UnilendStats')->getQuarterIncidenceRate($date, self::ACPR_CALCULATION_PERIOD_MONTHS);
        $quarterData = [];

        /** @var UnilendStats $stat */
        foreach ($data as $stat) {
            $quarterData[$stat->getAdded()->format('Y-m-d')] = json_decode($stat->getValue(), true);
        }

       return $quarterData;
    }
}
