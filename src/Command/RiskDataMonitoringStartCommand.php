<?php

namespace Unilend\Command;

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
        $riskDataMonitoringCycleManager = $this->getContainer()->get('unilend.service.risk_data_monitoring_cycle_manager');

        try {
            $riskDataMonitoringCycleManager->activateMonitoringForNewSiren();
            $riskDataMonitoringCycleManager->reactivateMonitoring();
        } catch (\Exception $exception) {
            $this->getContainer()->get('logger')->error('Could not activate/reactivate monitoring. Message: ' . $exception->getMessage(), [
                'file'     => $exception->getFile(),
                'line'     => $exception->getLine(),
                'class'    => __CLASS__,
                'function' => __FUNCTION__
            ]);
        }
    }
}
