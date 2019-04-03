<?php

namespace Unilend\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\RiskDataMonitoring\{AltaresManager, EulerHermesManager, MonitoringManager};

class RiskDataMonitoringStartForProviderAndSirenCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('unilend:risk_data_monitoring:start_long_term')
            ->setDescription('Activate long term risk data monitoring by provider and siren')
            ->addArgument(
                'provider',
                InputArgument::REQUIRED,
                'provider for which monitoring should be activated'
            )
            ->addArgument(
                'siren',
                InputArgument::REQUIRED,
                'siren for which monitoring should be activated'
            )
            ->addArgument(
                'short_term',
                InputArgument::OPTIONAL,
                'Should short only term monitoring be activated'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $provider = $input->getArgument('provider');
        $siren    = $input->getArgument('siren');
        $shortTerm = $input->getArgument('short_term');

        if (false === in_array($provider, MonitoringManager::PROVIDERS)) {
            $output->writeln('<error>Provider is not supported. It can be one of the following: ' . implode(',', MonitoringManager::PROVIDERS) . '</error>');

            return;
        }

        if (1 !== preg_match('/^[0-9]*$/', $siren)) {
            $output->writeln('<error>Siren is not valid</error>');

            return;
        }

        $monitoringManager = $this->getContainer()->get('unilend.service.risk_data_monitoring_manager');

        if ($monitoringManager->isSirenMonitored($siren, $provider)) {
            $output->writeln('<error>Siren is already monitored for that provider</error>');

            return;
        }

        switch ($provider) {
            case AltaresManager::PROVIDER_NAME:
                $altaresManager = $this->getContainer()->get('unilend.service.risk_data_altares_manager');
                $altaresManager->activateMonitoring($siren);
                break;
            case EulerHermesManager::PROVIDER_NAME:
                $eulerHermesManager = $this->getContainer()->get('unilend.service.risk_data_euler_hermes_manager');
                if (false === empty($shortTerm)) {
                    $eulerHermesManager->activateMonitoring($siren);
                } else {
                    $eulerHermesManager->activateLongTermMonitoring($siren);
                }
                break;
            default:
                break;
        }
    }
}
