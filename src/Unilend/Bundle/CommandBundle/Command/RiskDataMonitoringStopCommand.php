<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RiskDataMonitoringStopCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('unilend:risk_data_monitoring:stop')
            ->setDescription('Stops risk data monitoring for companies who have no longer any active projects');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entityManager        = $this->getContainer()->get('doctrine.orm.entity_manager');
        $finishedSiren        = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->getSirenWithProjectOrCompanyStatusNotToBeMonitored();
        $riskDataCycleManager = $this->getContainer()->get('unilend.service.risk_data_monitoring_cycle_manager');

        foreach ($finishedSiren as $siren) {
            $riskDataCycleManager->stopMonitoringForSiren($siren['siren']);
        }
    }
}
