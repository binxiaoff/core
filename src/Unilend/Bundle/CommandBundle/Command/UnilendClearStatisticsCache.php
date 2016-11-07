<?php

namespace Unilend\Bundle\CommandBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\librairies\CacheKeys;

class UnilendClearStatisticsCache extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setName('unilend:statistics:clear')
            ->setDescription('Clears the cache for all statistics');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $cachePool = $this->getContainer()->get('memcache.default');
        $cachePool->deleteItem(CacheKeys::UNILEND_STATISTICS);
    }
}
