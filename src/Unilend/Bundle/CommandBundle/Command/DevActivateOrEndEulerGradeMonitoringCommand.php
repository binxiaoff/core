<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\CompanyRating;

class DevActivateOrEndEulerGradeMonitoringCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('unilend:dev_tools:euler_grade_monitoring')
            ->addOption('action', null, InputOption::VALUE_REQUIRED, 'Start or end')
            ->addOption('siren', null, InputOption::VALUE_REQUIRED, 'Siren for which action should be executed');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $action = $input->getOption('action');
        $siren  = filter_var($input->getOption('siren'), FILTER_SANITIZE_STRING);

        if (false === in_array($action, ['start', 'end'])) {
            $output->writeln('Action ' . $action . ' is not valid');
            return;
        }

        if (empty($siren)) {
            $output->writeln('Siren ' . $siren . ' is missing');
            return;
        }

        if (1 !== preg_match('/^[0-9]*$/', $siren)) {
            $output->writeln('Siren format is not valid');
            return;
        }

        if (null === $this->getContainer()->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Companies')->findOneBy(['siren' => $siren])) {
            $output->writeln('Siren  ' . $siren . ' is unknown to database');
            return;
        }

        $riskDataMonitoringManager = $this->getContainer()->get('unilend.service.risk_data_monitoring_manager');
        $eulerHermesManager = $this->getContainer()->get('unilend.service.ws_client.euler_manager');

        if ('start' === $action) {
            $eulerHermesManager->startLongTermMonitoring($siren, 'fr');
            $riskDataMonitoringManager->startMonitoringPeriod($siren, CompanyRating::TYPE_EULER_HERMES_GRADE);
        } else {
            //TODO
        }
    }
}
