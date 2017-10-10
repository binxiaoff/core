<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\UnilendStats;
use Unilend\Bundle\CoreBusinessBundle\Service\StatisticsManager;

class UnilendStatisticsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('unilend:statistics')
            ->setDescription('Calculate all statistics and save them in DB');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var StatisticsManager $statisticsManager */
        $statisticsManager = $this->getContainer()->get('unilend.service.statistics_manager');
        $entityManager     = $this->getContainer()->get('doctrine.orm.entity_manager');

//        $statistics = $statisticsManager->calculateStatistics();
//
//        $frontStats = new UnilendStats();
//        $frontStats->setTypeStat(UnilendStats::TYPE_STAT_FRONT_STATISTIC)
//            ->setValue(json_encode($statistics));
//
//        $entityManager->persist($frontStats);
//
//        $entityManager->flush($frontStats);

        $fpfStatistics = $statisticsManager->calculatePerformanceIndicators(new \DateTime('NOW'));

        $fpfStats = new UnilendStats();
        $fpfStats->setTypeStat(UnilendStats::TYPE_FPF_FRONT_STATISTIC)
            ->setValue(json_encode($fpfStatistics));

        $entityManager->persist($fpfStats);

        $entityManager->flush($fpfStats);
    }
}
