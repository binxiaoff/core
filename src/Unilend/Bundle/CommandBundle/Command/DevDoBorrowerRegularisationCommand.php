<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\BankAccount;
use Unilend\Bundle\CoreBusinessBundle\Entity\Virements;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;

class DevDoBorrowerRegularisationCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('unilend:dev_tools:borrower_regularization:create')
            ->addOption('project-id', null, InputOption::VALUE_REQUIRED, 'The project id of the relating borrower.')
            ->addOption('amount', null, InputOption::VALUE_REQUIRED, 'The amount of the regularization in euro.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $projectId = $input->getOption('project-id');
        $amount    = filter_var($input->getOption('amount'), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

        $entityManager   = $this->getContainer()->get('doctrine.orm.entity_manager');
        $borrowerManager = $this->getContainer()->get('unilend.service.borrower_manager');
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
        $entityManager->getConnection()->beginTransaction();
        try {
            /** @var Wallet $borrowerWallet */
            $borrowerWallet = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($project->getIdCompany()->getIdClientOwner(), WalletType::BORROWER);
            $operationManager->borrowerRegularisation($borrowerWallet, $amount, $project);
            /** @var BankAccount $bankAccount */
            $bankAccount = $entityManager->getRepository('UnilendCoreBusinessBundle:BankAccount')->getClientValidatedBankAccount($borrowerWallet->getIdClient());

            $wireTransferOut = new Virements();
            $wireTransferOut->setProject($project)
                            ->setMotif($borrowerManager->getBorrowerBankTransferLabel($project))
                            ->setClient($borrowerWallet->getIdClient())
                            ->setMontant(bcmul($amount, 100))
                            ->setType(Virements::TYPE_BORROWER)
                            ->setStatus(Virements::STATUS_PENDING)
                            ->setBankAccount($bankAccount);
            $entityManager->persist($wireTransferOut);
            $entityManager->flush($wireTransferOut);

            $output->writeln('Created wire transfer out ID: ' . $wireTransferOut->getIdVirement());

            $operationManager->withdrawBorrowerWallet($borrowerWallet, $wireTransferOut, -1 * $amount);
            $entityManager->getConnection()->commit();

            $output->writeln('regularization done. Remains to do: update the factures table and generate the correct invoice pdf. -Insert a line in the table regularization_operation_history');
        } catch (\Exception $exception) {
            $entityManager->getConnection()->rollBack();
            $output->writeln('error while doing the regularisation: ' . $exception->getMessage() . ' ' . $exception->getCode());
        }
    }
}
