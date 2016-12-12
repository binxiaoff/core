<?php


namespace Unilend\Bundle\CommandBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\StatisticsManager;
use Unilend\librairies\CacheKeys;


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
        /** @var \unilend_stats $unilendStatistics */
        $unilendStatistics = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('unilend_stats');

        $statistics = $statisticsManager->calculateStatistics();

        $unilendStatistics->type_stat = CacheKeys::UNILEND_STATISTICS;
        $unilendStatistics->value = json_encode($statistics);
        $unilendStatistics->create();
    }
}
