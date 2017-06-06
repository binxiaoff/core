<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;

class DevBorrowerCommercialGestureCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('unilend:dev_tools:borrower_commercial_gesture:create')
            ->addOption('project-id', null, InputOption::VALUE_REQUIRED, 'The project id of the relating borrower.')
            ->addOption('amount', null, InputOption::VALUE_REQUIRED, 'The amount of the gesture in euro.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $projectId = $input->getOption('project-id');
        $amount    = filter_var($input->getOption('amount'), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

        $entityManager   = $this->getContainer()->get('doctrine.orm.entity_manager');
        $project         = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($projectId);

        if (null === $project) {
            $output->writeln('Project ID: ' . $projectId . ' not found');
            return;
        }

        if ($amount <= 0) {
            $output->writeln('Invalid amount');
            return;
        }

        $operationManager = $this->getContainer()->get('unilend.service.operation_manager');
        try {
            /** @var Wallet $borrowerWallet */
            $borrowerWallet = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($project->getIdCompany()->getIdClientOwner(), WalletType::BORROWER);
            $operationManager->borrowerCommercialGesture($borrowerWallet, $amount, [$project]);
        } catch (\Exception $exception) {
            $output->writeln('error while doing the commercial gesture: ' . $exception->getMessage() . ' ' . $exception->getCode());
        }
    }
}
