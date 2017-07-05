<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\Bids;
use Unilend\Bundle\CoreBusinessBundle\Entity\Loans;
use Unilend\Bundle\CoreBusinessBundle\Entity\Operation;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletBalanceHistory;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;

/**
 * Class WalletManager
 * @package Unilend\Bundle\CoreBusinessBundle\Service
 */
class WalletManager
{
    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var EntityManagerSimulator
     */
    private $legacyEntityManager;

    /**
     * WalletManager constructor.
     *
     * @param EntityManager          $entityManager
     * @param EntityManagerSimulator $legacyEntityManager
     */
    public function __construct(EntityManager $entityManager, EntityManagerSimulator $legacyEntityManager)
    {
        $this->entityManager       = $entityManager;
        $this->legacyEntityManager = $legacyEntityManager;
    }

    /**
     * @param Operation $operation
     */
    public function handle(Operation $operation)
    {
        $debtor = $operation->getWalletDebtor();
        if ($debtor instanceof Wallet) {
            $this->debit($operation, $debtor);
        }

        $creditor = $operation->getWalletCreditor();
        if ($creditor instanceof Wallet) {
            $this->credit($operation, $creditor);
        }
    }

    /**
     * @param Wallet $wallet
     * @param float  $amount
     * @param Bids   $bid
     *
     * @return WalletBalanceHistory
     * @throws \Exception
     */
    public function engageBalance(Wallet $wallet, $amount, Bids $bid)
    {
        $this->entityManager->getConnection()->beginTransaction();
        try {
            if (-1 === bccomp($wallet->getAvailableBalance(), $amount)) {
                //throw new \DomainException('The available balance for wallet id : ' . $wallet->getId() . ' must not be lower than zero');
            }

            $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->engageBalance($wallet, $amount);

            $this->entityManager->refresh($wallet);
            $walletBalanceHistory = $this->snap($wallet, $bid);
            $this->entityManager->getConnection()->commit();

            return $walletBalanceHistory;
        } catch (\Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            throw $e;
        }
    }

    /**
     * @param Wallet       $wallet
     * @param float        $amount
     * @param array|object $origin
     *
     * @return WalletBalanceHistory
     * @throws \Exception
     */
    public function releaseBalance(Wallet $wallet, $amount, $origin)
    {
        $this->entityManager->getConnection()->beginTransaction();
        try {
            if (-1 === bccomp($wallet->getCommittedBalance(), $amount)) {
                //throw new \DomainException('The committed balance for wallet id : ' . $wallet->getId() . ' must not be lower than zero');
            }

            $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->releaseBalance($wallet, $amount);

            $this->entityManager->refresh($wallet);
            $walletBalanceHistory = $this->snap($wallet, $origin);
            $this->entityManager->getConnection()->commit();

            return $walletBalanceHistory;
        } catch (\Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            throw $e;
        }
    }

    /**
     * @param Operation $operation
     * @param Wallet    $creditor
     */
    private function credit(Operation $operation, Wallet $creditor)
    {
        if (WalletType::DEBT_COLLECTOR !== $creditor->getIdType()->getLabel() && $creditor->getAvailableBalance() < 0) {
            //throw new \DomainException('The available balance for wallet id : ' . $creditor->getId() . ' must not be lower than zero');
        }
        $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->creditAvailableBalance($creditor, $operation->getAmount());

        $this->entityManager->refresh($creditor);
        $this->snap($creditor, $operation);
    }

    /**
     * @param Operation $operation
     * @param Wallet    $debtor
     */
    private function debit(Operation $operation, Wallet $debtor)
    {
        $this->entityManager->refresh($debtor);

        switch ($operation->getType()->getLabel()) {
            case OperationType::LENDER_LOAN :

                if (WalletType::DEBT_COLLECTOR !== $debtor->getIdType()->getLabel() && $debtor->getCommittedBalance() < 0) {
                    //throw new \DomainException('The committed balance for wallet id : ' . $debtor->getId() . '  must not be lower than zero');
                }
                $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->debitCommittedBalance($debtor, $operation->getAmount());
                break;
            default :
                if (WalletType::DEBT_COLLECTOR !== $debtor->getIdType()->getLabel() && $debtor->getAvailableBalance() < 0) {
                    //throw new \DomainException('The available balance for wallet id : ' . $debtor->getId() . '  must not be lower than zero');
                }
                $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->debitAvailableBalance($debtor, $operation->getAmount());
                break;
        }
        $this->entityManager->refresh($debtor);
        $this->snap($debtor, $operation);
    }

    /**
     * @param Wallet       $wallet
     * @param array|object $parameters
     *
     * @return WalletBalanceHistory
     */
    private function snap(Wallet $wallet, $parameters)
    {
        $walletSnap = new WalletBalanceHistory();
        $walletSnap->setIdWallet($wallet)
            ->setAvailableBalance($wallet->getAvailableBalance())
            ->setCommittedBalance($wallet->getCommittedBalance());

        if (false === is_array($parameters)) {
            $parameters = [$parameters];
        }

        foreach ($parameters as $item) {
            if ($item instanceof Operation) {
                $walletSnap->setIdOperation($item);
                $walletSnap->setLoan($item->getLoan());
            }
            if ($item instanceof Bids) {
                $walletSnap->setBid($item);
                $walletSnap->setAutobid($item->getAutobid());
            }
            if ($item instanceof Loans) {
                $walletSnap->setLoan($item);
            }
            $walletSnap->setProject($item->getProject());
        }
        $this->entityManager->persist($walletSnap);
        $this->entityManager->flush($walletSnap);

        return $walletSnap;
    }
}
