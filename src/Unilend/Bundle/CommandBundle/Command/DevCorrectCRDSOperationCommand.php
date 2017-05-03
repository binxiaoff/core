<?php


namespace Unilend\Bundle\CommandBundle\Command;

use PDO;
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
        foreach ($operations as $operation) {
            $wbhEntry = $entityManager->getRepository('UnilendCoreBusinessBundle:WalletBalanceHistory')->findOneBy(['idOperation' => $operation->getId(), 'idWallet' => $crdsWallet->getId()]);

            if (null === $wbhEntry) {
                $balance = bcadd($crdsWallet->getAvailableBalance(), $operation->getAmount(), 2);
                $crdsWallet->setAvailableBalance($balance);
                $entityManager->flush($crdsWalletType);

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

                $count ++;
            }
        }

        $output->writeln($count . ' operations repaired and wbh lines created.');
    }
}
