<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RiskDataMonitoringStartCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('unilend:risk_data_monitoring:start')
            ->setDescription('Activate risk data monitoring for newly added companies with projects');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->activateMonitoringForNewSiren();
            $this->activateMonitoringForProjectsBeingReactivated();
        } catch (\Exception $exception) {
            $this->getContainer()->get('logger')->error('Could not activate/reactivate monitoring. Message: ' . $exception->getMessage(), ['file' => $exception->getFile(), 'line' => $exception->getLine()]);
        }
    }

    private function activateMonitoringForNewSiren(): void
    {
        $entityManager                  = $this->getContainer()->get('doctrine.orm.entity_manager');
        $riskDataMonitoringCycleManager = $this->getContainer()->get('unilend.service.risk_data_monitoring_cycle_manager');
        $sirenToBeActivated             = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->getNotYetMonitoredSirenWithProjects();

        foreach ($sirenToBeActivated as $siren) {
            $riskDataMonitoringCycleManager->activateMonitoringForSiren($siren['siren']);
        }
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    private function activateMonitoringForProjectsBeingReactivated(): void
    {
        $entityManager                  = $this->getContainer()->get('doctrine.orm.entity_manager');
        $riskDataMonitoringCycleManager = $this->getContainer()->get('unilend.service.risk_data_monitoring_cycle_manager');
        $sirenToBeActivated             = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->getSirenWithActiveProjectsAndNoMonitoring();

        foreach ($sirenToBeActivated as $siren) {
            $riskDataMonitoringCycleManager->activateMonitoringForSiren($siren['siren']);
        }
    }
}
