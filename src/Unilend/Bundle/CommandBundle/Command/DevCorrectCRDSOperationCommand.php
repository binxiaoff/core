<?php


namespace Unilend\Bundle\CommandBundle\Command;

use DateInterval;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bridge\Doctrine\DBAL\Connection;
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
        ], ['id' => 'ASC'], 10000);

        $entityManager->getConnection()->beginTransaction();
        try {
            $count = 0;
            /** @var Operation $operation */
            foreach ($operations as $index => $operation) {
                $wbhEntry = $entityManager->getRepository('UnilendCoreBusinessBundle:WalletBalanceHistory')->findOneBy(['idOperation' => $operation->getId(), 'idWallet' => $crdsWallet->getId()]);
                if (null === $wbhEntry) {
                    if ($index > 0 && $operations[$index - 1]->getAdded()->format('n') < $operation->getAdded()->format('n')) {
                        $this->createCRDSWithdrawOperation($crdsWallet, $operations[$index - 1]->getAdded());
                        $output->writeln('Withdraw created');
                    }

                    $this->createWalletBalanceHistory($crdsWallet, $operation);
                    $count ++;
                }
            }
            $entityManager->getConnection()->commit();
            $output->writeln($count . ' operations repaired and wbh lines created.');
        } catch (\Exception $exception){
            $entityManager->getConnection()->rollBack();
            throw $exception;
        }
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

        /** @var WalletBalanceHistory $lastMonthWalletHistory */
        $lastMonthWalletHistory = $entityManager->getRepository('UnilendCoreBusinessBundle:WalletBalanceHistory')->getBalanceOfTheDay($crdsWallet, $date);
        $operationType          = $entityManager->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneByLabel(OperationType::TAX_FR_CRDS_WITHDRAW);

        $withdrawalDate = $date->add(DateInterval::createFromDateString('1 day'));
        $withdrawalDate->setTime(1, 0, 0);

        $operation = new Operation();
        $operation->setWalletDebtor($crdsWallet)
            ->setAmount($lastMonthWalletHistory->getAvailableBalance())
            ->setType($operationType)
            ->setAdded($withdrawalDate);

        $entityManager->persist($operation);

        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        $balance       = bcsub($crdsWallet->getAvailableBalance(), $operation->getAmount(), 2);
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
        $entityManager->flush($operation);
    }
}
