<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DevCorrectDirectDebitCancellationCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('unilend:dev_tools:direct_debit_cancellation:correct')
            ->setDescription('Correct the derect debit cancellation in TMA-1950');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $operationManager = $this->getContainer()->get('unilend.service.operation_manager');
        $entityManager    = $this->getContainer()->get('doctrine.orm.entity_manager');
        $wallet = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->find(46973);
        $reception = $entityManager->getRepository('UnilendCoreBusinessBundle:Receptions')->find(35217);
        $operationManager->cancelProvisionBorrowerWallet($wallet, 1943.20, $reception);
    }
}
