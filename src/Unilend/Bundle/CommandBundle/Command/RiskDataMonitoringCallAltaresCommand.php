<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RiskDataMonitoringCallAltaresCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('unilend:risk_data_monitoring:altares:call')
            ->setDescription('Call altares webservice to get recent events');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $altaresManager = $this->getContainer()->get('unilend.service.risk_data_altares_manager');
        $altaresManager->saveMonitoringEvents();
    }
}
