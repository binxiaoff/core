<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Operation;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;

class DevCorrectWrongRepaymentCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('unilend:dev_tools:wrong_repayment:correct')
            ->setDescription('add missing welcome offers');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entityManager    = $this->getContainer()->get('doctrine.orm.entity_manager');
        $operationManager = $this->getContainer()->get('unilend.service.operation_manager');

        $entityManager->getConnection()->beginTransaction();
        try {
            /** @var Operation $wrongCommissionOperation */
            $wrongCommissionOperation = $entityManager->getRepository('UnilendCoreBusinessBundle:Operation')->find(43544955);
            $operationManager->regularize($wrongCommissionOperation);

            /** @var Operation $wrongRepaymentOperation */
            $wrongRepaymentOperation = $entityManager->getRepository('UnilendCoreBusinessBundle:Operation')->find(43544952);
            $operationManager->regularize($wrongRepaymentOperation);

            $lender  = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType(6543, WalletType::LENDER);
            $project = $wrongRepaymentOperation->getProject();
            $amount  = $wrongRepaymentOperation->getAmount();
            $operationManager->repaymentCollection($lender, $project, $amount);

            $collector        = $wrongCommissionOperation->getWalletCreditor();
            $commissionLender = $wrongCommissionOperation->getAmount();
            $operationManager->payCollectionCommissionByLender($lender, $collector, $commissionLender, $project);

            $entityManager->getConnection()->commit();
        } catch (\Exception $exception) {

            $entityManager->getConnection()->rollBack();
            $output->writeln('Error occurs. transaction roll backed.');
        }

    }
}
