<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\UnilendStats;
use Unilend\Bundle\CoreBusinessBundle\Service\StatisticsManager;

class UnilendStatisticsCommand extends ContainerAwareCommand
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('unilend:statistics')
            ->setDescription('Calculate all statistics and save them in DB');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     * @throws \Psr\Cache\InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        try {
            $this->saveFrontStatistics();
            $this->savePerformanceIndicators();
            $this->saveIncidenceRate();
        } catch (\Exception $exception) {
            $this->getContainer()->get('logger')->error('Could not calculate unilend statistics. Exception: ' . $exception->getMessage(), [
                'exceptionFile' => $exception->getFile(),
                'exceptionLine' => $exception->getLine(),
                'class'         => __CLASS__,
                'function'      => __METHOD__
            ]);
        }
    }

    /**
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function saveFrontStatistics(): void
    {
        /** @var StatisticsManager $statisticsManager */
        $statisticsManager = $this->getContainer()->get('unilend.service.statistics_manager');
        $entityManager     = $this->getContainer()->get('doctrine.orm.entity_manager');

        $statistics = $statisticsManager->calculateStatistics();
        $frontStats = new UnilendStats();
        $frontStats
            ->setTypeStat(UnilendStats::TYPE_STAT_FRONT_STATISTIC)
            ->setValue(json_encode($statistics));

        $entityManager->persist($frontStats);
        $entityManager->flush($frontStats);
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function savePerformanceIndicators(): void
    {
        $statisticsManager = $this->getContainer()->get('unilend.service.statistics_manager');
        $entityManager     = $this->getContainer()->get('doctrine.orm.entity_manager');

        $fpfStatistics = $statisticsManager->calculatePerformanceIndicators(new \DateTime('NOW'));
        $fpfStats      = new UnilendStats();
        $fpfStats
            ->setTypeStat(UnilendStats::TYPE_FPF_FRONT_STATISTIC)
            ->setValue(json_encode($fpfStatistics));

        $entityManager->persist($fpfStats);
        $entityManager->flush($fpfStats);
    }

    /**
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function saveIncidenceRate(): void
    {
        $statisticsManager = $this->getContainer()->get('unilend.service.statistics_manager');
        $entityManager     = $this->getContainer()->get('doctrine.orm.entity_manager');

        $incidenceRate      = $statisticsManager->calculateIncidenceRate();
        $incidenceRateStats = new UnilendStats();
        $incidenceRateStats
            ->setTypeStat(UnilendStats::TYPE_INCIDENCE_RATE)
            ->setValue(json_encode($incidenceRate));

        $entityManager->persist($incidenceRateStats);
        $entityManager->flush($incidenceRateStats);
    }
}
