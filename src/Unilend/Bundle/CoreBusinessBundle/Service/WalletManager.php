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
            $this->snap($debtor, $operation);
        }

        $creditor = $operation->getWalletCreditor();
        if ($creditor instanceof Wallet) {
            $this->credit($operation, $creditor);
            $this->snap($creditor, $operation);
        }
    }

    /**
     * @param Wallet $wallet
     * @param float  $amount
     * @param Bids   $bid
     *
     * @throws \Exception
     */
    public function engageBalance(Wallet $wallet, $amount, Bids $bid)
    {
        $this->entityManager->getConnection()->beginTransaction();
        try {
            if (-1 === bccomp($wallet->getAvailableBalance(), $amount)) {
                //throw new \DomainException('The available balance for wallet id : ' . $wallet->getId() . ' must not be lower than zero');
            }

            $availableBalance = bcsub($wallet->getAvailableBalance(), $amount, 2);
            $committedBalance = bcadd($wallet->getCommittedBalance(), $amount, 2);
            $wallet->setAvailableBalance($availableBalance);
            $wallet->setCommittedBalance($committedBalance);
            $this->entityManager->flush($wallet);

            $this->snap($wallet, $bid);
            $this->entityManager->getConnection()->commit();
        } catch (\Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            throw $e;
        }
    }

    /**
     * @param Wallet $wallet
     * @param        $amount
     * @param        $origin
     *
     * @throws \Exception
     */
    public function releaseBalance(Wallet $wallet, $amount, $origin)
    {
        $this->entityManager->getConnection()->beginTransaction();
        try {
            if (-1 === bccomp($wallet->getCommittedBalance(), $amount)) {
                //throw new \DomainException('The committed balance for wallet id : ' . $wallet->getId() . ' must not be lower than zero');
            }

            $availableBalance = bcadd($wallet->getAvailableBalance(), $amount, 2);
            $committedBalance = bcsub($wallet->getCommittedBalance(), $amount, 2);
            $wallet->setAvailableBalance($availableBalance);
            $wallet->setCommittedBalance($committedBalance);
            $this->entityManager->flush($wallet);

            $this->snap($wallet, $origin);
            $this->entityManager->getConnection()->commit();
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
        if ($creditor instanceof Wallet) {
            $balance = bcadd($creditor->getAvailableBalance(), $operation->getAmount(), 2);
            if (WalletType::DEBT_COLLECTOR !== $creditor->getIdType()->getLabel() && $balance < 0) {
                //throw new \DomainException('The available balance for wallet id : ' . $creditor->getId() . ' must not be lower than zero');
            }
            $creditor->setAvailableBalance($balance);

            $this->entityManager->flush($creditor);
        }
    }

    /**
     * @param Operation $operation
     * @param Wallet    $debtor
     */
    private function debit(Operation $operation, Wallet $debtor)
    {
        if ($debtor instanceof Wallet) {
            switch ($operation->getType()->getLabel()) {
                case OperationType::LENDER_LOAN :
                    $balance = bcsub($debtor->getCommittedBalance(), $operation->getAmount(), 2);
                    if (WalletType::DEBT_COLLECTOR !== $debtor->getIdType()->getLabel() && $balance < 0) {
                        //throw new \DomainException('The committed balance for wallet id : ' . $debtor->getId() . '  must not be lower than zero');
                    }
                    $debtor->setCommittedBalance($balance);
                    break;
                default :
                    $balance = bcsub($debtor->getAvailableBalance(), $operation->getAmount(), 2);
                    if (WalletType::DEBT_COLLECTOR !== $debtor->getIdType()->getLabel() && $balance < 0) {
                        //throw new \DomainException('The available balance for wallet id : ' . $debtor->getId() . '  must not be lower than zero');
                    }
                    $debtor->setAvailableBalance($balance);
                    break;
            }
            $this->entityManager->flush($debtor);
        }
    }

    /**
     * @param Wallet       $wallet
     * @param array|object $parameters
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
    }
}
