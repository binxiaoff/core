<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    Echeanciers, Loans, ProjectsStatus, UnderlyingContract, UnilendStats
};
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
        $missingDate = new \DateTime('Last day of March 2018');

        $this->generateMissingDataInWrongStat($missingDate);
        $this->saveRatioIFP();
        $this->saveRatioCIP();
        $this->createQuarterEntry($missingDate);
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function saveRatioIFP()
    {
        $start         = new \DateTime('First day of January 2018');
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
                $lateProjectsIFP = $this->getProjectInProblemWithContractOnDate(UnderlyingContract::CONTRACT_IFP, $lastDayOfMonth);
                $allProjectsIFP  = $this->getProjectInRepaymentWithContractOnDate(UnderlyingContract::CONTRACT_IFP, $lastDayOfMonth);

                $result['lateProjectsIFP'] = implode(',', $lateProjectsIFP);
                $result['allProjectsIFP']  = implode(',', $allProjectsIFP);
                $result['countLateIFP']    = count($lateProjectsIFP);
                $result['countAllIFP']     = count($allProjectsIFP);

                $data             = json_decode($incidenceRateStats->getValue(), true);
                $data['ratioIFP'] = 0 == $result['countAllIFP'] ? 0.00 : round(bcmul(bcdiv($result['countLateIFP'], $result['countAllIFP'], 6), 100, 4), 2);

                $incidenceRateStats->setValue(json_encode($data));

                $entityManager->flush($incidenceRateStats);
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
        $start         = new \DateTime('First day of January 2018');
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
                $lateProjectsCIP = $this->getProjectInProblemWithContractOnDate(UnderlyingContract::CONTRACT_MINIBON, $lastDayOfMonth);
                $allProjectsCIP  = $this->getProjectInRepaymentWithContractOnDate(UnderlyingContract::CONTRACT_MINIBON, $lastDayOfMonth);

                $result['lateProjectsCIP'] = implode(',', $lateProjectsCIP);
                $result['allProjectsCIP']  = implode(',', $allProjectsCIP);
                $result['countLateCIP']    = count($lateProjectsCIP);
                $result['countAllCIP']     = count($allProjectsCIP);

                $data             = json_decode($incidenceRateStats->getValue(), true);
                $data['ratioCIP'] = 0 == $result['countAllCIP'] ? 0.00 : round(bcmul(bcdiv($result['countLateCIP'], $result['countAllCIP'], 6), 100, 4), 2);

                $incidenceRateStats->setValue(json_encode($data));

                $entityManager->flush($incidenceRateStats);
            }
        }
    }

    /**
     * @param \DateTime $quarter
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function createQuarterEntry(\DateTime $quarter): void
    {
        $entityManager         = $this->getContainer()->get('doctrine.orm.entity_manager');
        $unilendStatRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:UnilendStats');
        $quarterStats          = $unilendStatRepository->findStatisticAtDate($quarter, UnilendStats::TYPE_INCIDENCE_RATE);

        if (null !== $quarterStats) {
            $incidenceRateStat = clone $quarterStats;
            $incidenceRateStat
                ->setTypeStat(UnilendStats::TYPE_QUARTER_INCIDENCE_RATE)
                ->setUpdated(new \DateTime('NOW'));

            $entityManager->persist($incidenceRateStat);
            $entityManager->flush($incidenceRateStat);

            $entityManager->refresh($incidenceRateStat);
            $this->saveQuarterIncidenceRate($incidenceRateStat);
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
            ->executeQuery($query, ['repayment'    => ProjectsStatus::STATUS_REPAYMENT,
                                    'repaid'       => [ProjectsStatus::STATUS_REPAID, ProjectsStatus::STATUS_REPAID],
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

    /**
     * @param \DateTime $missingDate
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function generateMissingDataInWrongStat(\DateTime $missingDate): void
    {
        $entityManager         = $this->getContainer()->get('doctrine.orm.entity_manager');
        $unilendStatRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:UnilendStats');

        $datePostACPRMep = new \DateTime('2018-03-07');
        $today           = new \DateTime('NOW');

        $lastCorrectStat = $unilendStatRepository->findStatisticAtDate($datePostACPRMep, UnilendStats::TYPE_INCIDENCE_RATE);
        $todayStat       = $unilendStatRepository->findStatisticAtDate($today, UnilendStats::TYPE_INCIDENCE_RATE);
        $wrongStat       = $unilendStatRepository->findStatisticAtDate($missingDate, UnilendStats::TYPE_INCIDENCE_RATE);

        $lastCorrectData = json_decode($lastCorrectStat->getValue(), true);
        $todayData       = json_decode($todayStat->getValue(), true);

        $averageData['amountIFP']   = round(bcdiv(bcadd($lastCorrectData['amountIFP'], $todayData['amountIFP'], 4), 2, 4), 2);
        $averageData['projectsIFP'] = round(bcdiv(bcadd($lastCorrectData['projectsIFP'], $todayData['projectsIFP'], 4), 2, 4), 2);
        $averageData['amountCIP']   = round(bcdiv(bcadd($lastCorrectData['amountCIP'], $todayData['amountCIP'], 4), 2, 4), 2);
        $averageData['projectsCIP'] = round(bcdiv(bcadd($lastCorrectData['projectsCIP'], $todayData['projectsCIP'], 4), 2, 4), 2);

        $wrongStat->setValue(json_encode($averageData));

        $entityManager->flush($wrongStat);
    }
}
