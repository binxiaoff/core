<?php


namespace Unilend\Bundle\CommandBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Psr\Log\LoggerInterface;
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
            ->setDescription('Calculate the more lengthy statistics and save them in cache for 48h')
            ->setHelp(<<<EOF
        Should be calculated every day, cache duration is 48h to prevent lacking data if task is not executed for any reason
EOF
    );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var StatisticsManager $statisticsManager */
        $statisticsManager = $this->getContainer()->get('unilend.service.statistics_manager');
        $cachePool         = $this->getContainer()->get('memcache.default');
        $regulatoryData    = $statisticsManager->calculateRegulatoryData();
        $cachedItem        = $cachePool->getItem(CacheKeys::REGULATORY_TABLE);
        $cachedItem->set($regulatoryData)->expiresAfter(2 * CacheKeys::DAY);
        $cachePool->save($cachedItem);
    }

}
