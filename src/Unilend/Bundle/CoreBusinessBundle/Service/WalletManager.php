<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\Operation;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletBalanceHistory;

class WalletManager
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function handle(Operation $operation)
    {
        $creditor = $operation->getWalletCreditor();
        $this->credit($operation, $creditor);

        $debtor = $operation->getWalletDebtor();
        $this->debit($operation, $debtor);

        $this->snap($operation);
    }

    private function credit(Operation $operation, Wallet $creditor = null)
    {
        if ($creditor instanceof Wallet) {
            if ($operation->getType()->getLabel() === OperationType::LENDER_BID) {
                $balance = bcadd($creditor->getCommittedBalance(), $operation->getAmount(), 2);
                if ($balance < 0) {
                    new \DomainException('The balance must not be lower than zero');
                }
                $creditor->setCommittedBalance($balance);
            } else {
                $balance = bcadd($creditor->getAvailableBalance(), $operation->getAmount(), 2);
                if ($balance < 0) {
                    new \DomainException('The balance must not be lower than zero');
                }
                $creditor->setAvailableBalance($balance);
            }

            $this->entityManager->flush($creditor);
        }
    }

    private function debit(Operation $operation, Wallet $debtor = null)
    {
        if ($debtor instanceof Wallet) {
            if ($operation->getType()->getLabel() === OperationType::LENDER_LOAN) {
                $balance = bcsub($debtor->getCommittedBalance(), $operation->getAmount(), 2);
                if ($balance < 0) {
                    throw new \DomainException('The balance must not be lower than zero');
                }
                $debtor->setCommittedBalance($balance);
            } else {
                $balance = bcsub($debtor->getAvailableBalance(), $operation->getAmount(), 2);
                if ($balance < 0) {
                    throw new \DomainException('The balance must not be lower than zero');
                }
                $debtor->setAvailableBalance($balance);
            }

            $this->entityManager->flush($debtor);
        }
    }

    private function snap(Operation $operation)
    {
        $creditor = $operation->getWalletCreditor();
        if ($creditor instanceof Wallet) {
            $walletSnap = new WalletBalanceHistory();
            $walletSnap->setIdWallet($creditor)
                       ->setAvailableBalance($creditor->getAvailableBalance())
                       ->setCommittedBalance($creditor->getCommittedBalance())
                       ->setIdOperation($operation);

            $this->entityManager->persist($walletSnap);
        }

        $debtor = $operation->getWalletDebtor();
        if ($debtor instanceof Wallet && $debtor->getId() !== $creditor->getId()) {
            $walletSnap = new WalletBalanceHistory();
            $walletSnap->setIdWallet($debtor)
                       ->setAvailableBalance($debtor->getAvailableBalance())
                       ->setCommittedBalance($debtor->getCommittedBalance())
                       ->setIdOperation($operation);

            $this->entityManager->persist($walletSnap);
        }

        $this->entityManager->flush([$creditor, $debtor]);
    }
}
