<?php


namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Operation;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletBalanceHistory;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;


class DevCorrectCRDSOperationCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('unilend:dev_tools:crds')
            ->setDescription('Add the missing CRDS wallet balance history and correct concerned operations');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entityManager  = $this->getContainer()->get('doctrine.orm.entity_manager');
        $crdsWalletType = $entityManager->getRepository('UnilendCoreBusinessBundle:WalletType')->findOneBy(['label' => WalletType::TAX_FR_CRDS]);
        /** @var Wallet $crdsWallet */
        $crdsWallet    = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->findOneBy(['idType' => $crdsWalletType]);
        $operationType = $entityManager->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::TAX_FR_CRDS]);

        $operations = $entityManager->getRepository('UnilendCoreBusinessBundle:Operation')->findBy([
            'idType'           => $operationType,
            'idWalletCreditor' => null
        ], ['id' => 'ASC']);

        $count = 0;
        /** @var Operation $operation */
        foreach ($operations as $index => $operation) {
            $wbhEntry = $entityManager->getRepository('UnilendCoreBusinessBundle:WalletBalanceHistory')->findOneBy(['idOperation' => $operation->getId(), 'idWallet' => $crdsWallet->getId()]);
            if (null === $wbhEntry) {
                if ($index > 0 && $operations[$index - 1]->getAdded()->format('m') < $operation->getAdded()->format('m')) {
                    $this->createCRDSWithdrawOperation($crdsWallet, $operations[$index - 1]->getAdded());
                }

                $this->createWalletBalanceHistory($crdsWallet, $operation);
                $count ++;
            }
        }

        $output->writeln($count . ' operations repaired and wbh lines created.');
    }

    private function createWalletBalanceHistory(Wallet $crdsWallet, Operation $operation)
    {
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        $balance       = bcadd($crdsWallet->getAvailableBalance(), $operation->getAmount(), 2);
        $crdsWallet->setAvailableBalance($balance);
        $entityManager->flush($crdsWallet);

        $walletSnap = new WalletBalanceHistory();
        $walletSnap->setIdWallet($crdsWallet)
            ->setAvailableBalance($crdsWallet->getAvailableBalance())
            ->setCommittedBalance($crdsWallet->getCommittedBalance())
            ->setIdOperation($operation)
            ->setLoan($operation->getLoan())
            ->setProject($operation->getProject())
            ->setAdded($operation->getAdded());

        $entityManager->persist($walletSnap);
        $entityManager->flush($walletSnap);

        $operation->setWalletCreditor($crdsWallet);
        $entityManager->flush($operation);
    }

    private function createCRDSWithdrawOperation(Wallet $crdsWallet, \DateTime $date)
    {
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        $operationsManager = $this->getContainer()->get('unilend.service.operation_manager');

        /** @var WalletBalanceHistory $lastMonthWalletHistory */
        $lastMonthWalletHistory = $entityManager->getRepository('UnilendCoreBusinessBundle:WalletBalanceHistory')->getBalanceOfTheDay($crdsWallet, $date);
        $operationsManager->withdrawTaxWallet($crdsWallet, $lastMonthWalletHistory->getAvailableBalance());
    }
}
