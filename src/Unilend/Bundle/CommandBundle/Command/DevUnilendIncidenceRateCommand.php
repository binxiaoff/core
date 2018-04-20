<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Echeanciers;
use Unilend\Bundle\CoreBusinessBundle\Entity\Loans;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\UnilendStats;
use Unilend\Bundle\CoreBusinessBundle\Service\StatisticsManager;

class DevUnilendIncidenceRateCommand extends ContainerAwareCommand
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('unilend:dev_tools:statistics:incidence_rate')
            ->setDescription('Separate incidence rate from front statistics');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->saveRatioIFP();
        $this->saveRatioCIP();
        $this->createQuarterEntries();
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function saveRatioIFP()
    {
        $start         = new \DateTime(StatisticsManager::START_INCIDENCE_RATE_IFP);
        $end           = new \DateTime('Last day of last month');
        $monthInterval = \DateInterval::createFromDateString('1 month');
        $period        = new \DatePeriod($start, $monthInterval, $end);

        $entityManager                  = $this->getContainer()->get('doctrine.orm.entity_manager');
        $unilendStatisticsRepository    = $entityManager->getRepository('UnilendCoreBusinessBundle:UnilendStats');

        foreach ($period as $month) {
            $lastDayOfMonth = new \DateTime('Last day of ' . $month->format('F Y'));
            /** @var UnilendStats $incidenceRateStats */
            $incidenceRateStats = $unilendStatisticsRepository->findStatisticAtDate($lastDayOfMonth, UnilendStats::TYPE_INCIDENCE_RATE);
            if (null !== $incidenceRateStats) {
                $lateProjectsIFP = $this->getProjectInProblemWithContractOnDate(\underlying_contract::CONTRACT_IFP, $lastDayOfMonth);
                $allProjectsIFP  = $this->getProjectInRepaymentWithContractOnDate(\underlying_contract::CONTRACT_IFP, $lastDayOfMonth);

                $result['lateProjectsIFP'] = implode(',', $lateProjectsIFP);
                $result['allProjectsIFP']  = implode(',', $allProjectsIFP);
                $result['countLateIFP']    = count($lateProjectsIFP);
                $result['countAllIFP']     = count($allProjectsIFP);

                $data             = json_decode($incidenceRateStats->getValue(), true);
                $data['ratioIFP'] = 0 == $result['countAllIFP'] ? 0.00 : round(bcmul(bcdiv($result['countLateIFP'], $result['countAllIFP'], 6), 100, 4), 2);

                $incidenceRateStats->setValue(json_encode($data));

                $entityManager->flush($incidenceRateStats);

                $this->saveIntermediateDataInUnilendStatistics($result, $lastDayOfMonth);

            }
        }
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function saveRatioCIP()
    {
        $start         = new \DateTime(StatisticsManager::START_INCIDENCE_RATE_CIP);
        $end           = new \DateTime('Last day of this month');
        $monthInterval = \DateInterval::createFromDateString('1 month');
        $period        = new \DatePeriod($start, $monthInterval, $end);

        $entityManager                  = $this->getContainer()->get('doctrine.orm.entity_manager');
        $unilendStatisticsRepository    = $entityManager->getRepository('UnilendCoreBusinessBundle:UnilendStats');

        foreach ($period as $month) {
            $lastDayOfMonth = new \DateTime('Last day of ' . $month->format('F Y'));
            /** @var UnilendStats $incidenceRateStats */
            $incidenceRateStats = $unilendStatisticsRepository->findStatisticAtDate($lastDayOfMonth, UnilendStats::TYPE_INCIDENCE_RATE);
            if (null !== $incidenceRateStats) {
                $lateProjectsCIP = $this->getProjectInProblemWithContractOnDate(\underlying_contract::CONTRACT_MINIBON, $lastDayOfMonth);
                $allProjectsCIP  = $this->getProjectInRepaymentWithContractOnDate(\underlying_contract::CONTRACT_MINIBON, $lastDayOfMonth);

                $result['lateProjectsCIP'] = implode(',', $lateProjectsCIP);
                $result['allProjectsCIP']  = implode(',', $allProjectsCIP);
                $result['countLateCIP']    = count($lateProjectsCIP);
                $result['countAllCIP']     = count($allProjectsCIP);

                $data             = json_decode($incidenceRateStats->getValue(), true);
                $data['ratioCIP'] = 0 == $result['countAllCIP'] ? 0.00 : round(bcmul(bcdiv($result['countLateCIP'], $result['countAllCIP'], 6), 100, 4), 2);

                $incidenceRateStats->setValue(json_encode($data));

                $entityManager->flush($incidenceRateStats);

                $this->saveIntermediateDataInUnilendStatistics($result, $lastDayOfMonth);
            }
        }
    }

    /**
     * @param array     $projects
     * @param \DateTime $added
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function saveIntermediateDataInUnilendStatistics(array $projects, \DateTime $added)
    {
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');

        $unilendStats = new UnilendStats();
        $unilendStats
            ->setValue(json_encode($projects))
            ->setTypeStat('incidence_rate_ratio_raw_data')
            ->setAdded($added)
            ->setUpdated(new \DateTime('NOW'));

        $entityManager->persist($unilendStats);
        $entityManager->flush($unilendStats);
    }

    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function createQuarterEntries(): void
    {
        $entityManager         = $this->getContainer()->get('doctrine.orm.entity_manager');
        $unilendStatRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:UnilendStats');
        $years                 = [2015, 2016, 2017];

        foreach ($years as $year) {
            $firstQuarter  = new \DateTime('Last day of March ' . $year);
            $secondQuarter = new \DateTime('Last day of June ' . $year);
            $thirdQuarter  = new \DateTime('Last day of September ' . $year);
            $fourthQuarter = new \DateTime('Last day of December ' . $year);

            $firstQuarterStats  = $unilendStatRepository->findStatisticAtDate($firstQuarter, UnilendStats::TYPE_INCIDENCE_RATE);
            $secondQuarterStats = $unilendStatRepository->findStatisticAtDate($secondQuarter, UnilendStats::TYPE_INCIDENCE_RATE);
            $thirdQuarterStats  = $unilendStatRepository->findStatisticAtDate($thirdQuarter, UnilendStats::TYPE_INCIDENCE_RATE);
            $fourthQuarterStats = $unilendStatRepository->findStatisticAtDate($fourthQuarter, UnilendStats::TYPE_INCIDENCE_RATE);

            if (null !== $firstQuarterStats) {
                $incidenceRateStatT1 = clone $firstQuarterStats;
                $incidenceRateStatT1
                    ->setTypeStat(UnilendStats::TYPE_QUARTER_INCIDENCE_RATE)
                    ->setUpdated(new \DateTime('NOW'));

                $entityManager->persist($incidenceRateStatT1);
                $entityManager->flush($incidenceRateStatT1);

                $entityManager->refresh($incidenceRateStatT1);
                $this->saveQuarterIncidenceRate($incidenceRateStatT1);
            }

            if (null !== $secondQuarterStats) {
                $incidenceRateStatT2 = clone $secondQuarterStats;
                $incidenceRateStatT2
                    ->setTypeStat(UnilendStats::TYPE_QUARTER_INCIDENCE_RATE)
                    ->setUpdated(new \DateTime('NOW'));

                $entityManager->persist($incidenceRateStatT2);
                $entityManager->flush($incidenceRateStatT2);

                $entityManager->refresh($incidenceRateStatT2);
                $this->saveQuarterIncidenceRate($incidenceRateStatT2);
            }

            if (null !== $thirdQuarterStats) {
                $incidenceRateStatT3 = clone $thirdQuarterStats;
                $incidenceRateStatT3
                    ->setTypeStat(UnilendStats::TYPE_QUARTER_INCIDENCE_RATE)
                    ->setUpdated(new \DateTime('NOW'));

                $entityManager->persist($incidenceRateStatT3);
                $entityManager->flush($incidenceRateStatT3);

                $entityManager->refresh($incidenceRateStatT3);
                $this->saveQuarterIncidenceRate($incidenceRateStatT3);
            }

            if (null !== $fourthQuarterStats) {
                $incidenceRateStatT4 = clone $fourthQuarterStats;
                $incidenceRateStatT4
                    ->setTypeStat(UnilendStats::TYPE_QUARTER_INCIDENCE_RATE)
                    ->setUpdated(new \DateTime('NOW'));

                $entityManager->persist($incidenceRateStatT4);
                $entityManager->flush($incidenceRateStatT4);

                $entityManager->refresh($incidenceRateStatT4);
                $this->saveQuarterIncidenceRate($incidenceRateStatT4);
            }
        }
    }

    /**
     * @param UnilendStats $quarterStat
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function saveQuarterIncidenceRate(UnilendStats $quarterStat): void
    {
        $entityManager         = $this->getContainer()->get('doctrine.orm.entity_manager');
        $unilendStatRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:UnilendStats');

        $quarterData = json_decode($quarterStat->getValue(), true);

        if (isset($quarterData['ratioIFP'])) {
            unset($quarterData['ratioIFP']);
        }

        if (isset($quarterData['ratioCIP'])) {
            unset($quarterData['ratioCIP']);
        }

        if ($quarterStat->getAdded()->format('Y-m-d') > StatisticsManager::START_INCIDENCE_RATE_IFP) {
            $endOfQuarterStat = $unilendStatRepository->findStatisticAtDate($quarterStat->getAdded(), UnilendStats::TYPE_INCIDENCE_RATE);
            $todayData        = json_decode($endOfQuarterStat->getValue(), true);

            $lastDayLastMonth     = new \DateTime('Last day of previous month ' . $quarterStat->getAdded()->format('F Y'));
            $lastDayLastMonthStat = $unilendStatRepository->findStatisticAtDate($lastDayLastMonth, UnilendStats::TYPE_INCIDENCE_RATE);
            $lastDayLastMonthData = json_decode($lastDayLastMonthStat->getValue(), true);

            $lastDayOfTwoMonthAgo     = new \DateTime('Last day of previous month ' . $lastDayLastMonth->format('F Y'));
            $lastDayOfTwoMonthAgoStat = $unilendStatRepository->findStatisticAtDate($lastDayOfTwoMonthAgo, UnilendStats::TYPE_INCIDENCE_RATE);
            $lastDayOfTwoMonthAgoData = json_decode($lastDayOfTwoMonthAgoStat->getValue(), true);

            if ($quarterStat->getAdded()->format('Y-m-d') >= StatisticsManager::START_INCIDENCE_RATE_IFP) {
                $ratioIFP1                      = isset($todayData['ratioIFP']) ? $todayData['ratioIFP'] : 0.00;
                $ratioIFP2                      = isset($lastDayLastMonthData['ratioIFP']) ? $lastDayLastMonthData['ratioIFP'] : 0.00;
                $ratioIFP3                      = isset($lastDayOfTwoMonthAgoData['ratioIFP']) ? $lastDayOfTwoMonthAgoData['ratioIFP'] : 0.00;
                $quarterData['quarterRatioIFP'] = round(bcdiv(bcadd(bcadd($ratioIFP1, $ratioIFP2, 4), $ratioIFP3, 4), 3, 4), 2);
            }

            if ($quarterStat->getAdded()->format('Y-m-d') >= StatisticsManager::START_INCIDENCE_RATE_CIP) {
                $ratioCIP1                      = isset($todayData['ratioCIP']) ? $todayData['ratioCIP'] : 0.00;
                $ratioCIP2                      = isset($lastDayLastMonthData['ratioCIP']) ? $lastDayLastMonthData['ratioCIP'] : 0.00;
                $ratioCIP3                      = isset($lastDayOfTwoMonthAgoData['ratioCIP']) ? $lastDayOfTwoMonthAgoData['ratioCIP'] : 0.00;
                $quarterData['quarterRatioCIP'] = round(bcdiv(bcadd(bcadd($ratioCIP1, $ratioCIP2, 4), $ratioCIP3, 4), 3, 4), 2);
            }

            $quarterStat->setValue(json_encode($quarterData));
            $entityManager->flush(($quarterStat));
        }

        if ($quarterStat->getAdded()->format('Y-m-d') === StatisticsManager::START_INCIDENCE_RATE_IFP) {
            $quarterData['quarterRatioIFP'] = 0.00;
            $quarterStat->setValue(json_encode($quarterData));
            $entityManager->flush(($quarterStat));
        }
    }

    /**
     * @param string    $contractType
     * @param \DateTime $date
     *
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getProjectInProblemWithContractOnDate(string $contractType, \DateTime $date): array
    {
        $query = '
            SELECT
              e.id_project
            FROM echeanciers e
              INNER JOIN loans l ON e.id_loan = l.id_loan
            WHERE date_echeance <= :date
                  AND (date_echeance_reel > :date OR e.status != :repaid)
                  AND l.id_type_contract = (SELECT id_contract FROM underlying_contract WHERE label = :contractType)
                  AND l.status = :accepted
            GROUP BY e.id_project';

        $result = $this->getContainer()->get('doctrine.orm.entity_manager')
            ->getConnection()
            ->executeQuery($query, ['accepted' => Loans::STATUS_ACCEPTED, 'contractType' => $contractType, 'date' => $date->format('Y-m-d H:i:s'), 'repaid' => Echeanciers::STATUS_REPAID])
            ->fetchAll();

        $projects = [];
        foreach ($result as $project) {
            $projects[] = $project['id_project'];
        }

        return $projects;
    }

    /**
     * @param string    $contractType
     * @param \DateTime $date
     *
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getProjectInRepaymentWithContractOnDate(string $contractType, \DateTime $date): array
    {
        $query = '
            SELECT
              l.id_project
            FROM loans l
              INNER JOIN (
                           SELECT id_project, MAX(id_project_status_history) AS max_psh
                           FROM projects_status_history
                           WHERE added <= :date
                           GROUP BY id_project
                         ) AS ps ON ps.id_project = l.id_project
              INNER JOIN projects_status_history psh ON ps.max_psh = psh.id_project_status_history
              INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
            WHERE id_type_contract = (SELECT id_contract FROM underlying_contract WHERE label = :contractType) 
              AND l.status = :accepted 
              AND ps.status >= :repayment 
              AND ps.status NOT IN (:repaid)
            GROUP BY l.id_project';

        $result = $this->getContainer()->get('doctrine.orm.entity_manager')
            ->getConnection()
            ->executeQuery($query, ['repayment'    => ProjectsStatus::REMBOURSEMENT,
                                    'repaid'       => [ProjectsStatus::REMBOURSE, ProjectsStatus::REMBOURSEMENT_ANTICIPE],
                                    'contractType' => $contractType,
                                    'date'         => $date->format('Y-m-d H:i:s'),
                                    'accepted'     => Loans::STATUS_ACCEPTED
            ], ['repayment'    => \PDO::PARAM_INT,
                'repaid'       => Connection::PARAM_INT_ARRAY,
                'contractType' => \PDO::PARAM_STR,
                'date'         => \PDO::PARAM_STR
            ])->fetchAll();

        $projects = [];
        foreach ($result as $project) {
            $projects[] = $project['id_project'];
        }

        return $projects;
    }
}
