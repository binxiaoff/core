<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\Bids;
use Unilend\Bundle\CoreBusinessBundle\Entity\Operation;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletBalanceHistory;
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
        $this->entityManager = $entityManager;
        $this->legacyEntityManager = $legacyEntityManager;
    }

    /**
     * @param Operation $operation
     */
    public function handle(Operation $operation)
    {
        $creditor = $operation->getWalletCreditor();
        $this->credit($operation, $creditor);

        $debtor = $operation->getWalletDebtor();
        $this->debit($operation, $debtor);

        if ($creditor instanceof Wallet) {
            $this->snap($creditor, [$operation]);
        }

        if ($debtor instanceof Wallet) {
            $this->snap($debtor, [$operation]);
        }
    }

    /**
     * @param Wallet $wallet
     * @param        $amount
     * @param        $origin
     *
     * @throws \Exception
     */
    public function engageBalance(Wallet $wallet, $amount, $origin)
    {
        $this->entityManager->getConnection()->beginTransaction();
        try {
            if (-1 === bccomp($wallet->getAvailableBalance(), $amount)) {
                new \DomainException('The available balance must not be lower than zero');
            }

            $availableBalance = bcsub($wallet->getAvailableBalance(), $amount, 2);
            $committedBalance = bcadd($wallet->getCommittedBalance(), $amount, 2);
            $wallet->setAvailableBalance($availableBalance);
            $wallet->setCommittedBalance($committedBalance);

            $this->snap($wallet, [$origin]);

            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();

            $this->legacyCommitBalance($wallet->getIdClient()->getIdClient(), $amount, $origin);

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
                new \DomainException('The committed balance must not be lower than zero');
            }

            $availableBalance = bcadd($wallet->getAvailableBalance(), $amount, 2);
            $committedBalance = bcsub($wallet->getCommittedBalance(), $amount, 2);
            $wallet->setAvailableBalance($availableBalance);
            $wallet->setCommittedBalance($committedBalance);

            $this->snap($wallet, [$origin]);

            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();

            $this->legacyReleaseBalance($wallet->getIdClient()->getIdClient(), $amount, $origin);

        } catch (\Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            throw $e;
        }
    }

    /**
     * @param      $clientId
     * @param      $amount
     * @param Bids $bid
     */
    private function legacyCommitBalance($clientId, $amount, Bids $bid)
    {
        /** @var \transactions $transaction */
        $transaction = $this->legacyEntityManager->getRepository('transactions');
        /** @var \wallets_lines $walletLine */
        $walletLine = $this->legacyEntityManager->getRepository('wallets_lines');

        $amountInCent = bcmul($amount, 100);

        $transaction->id_client        = $clientId;
        $transaction->montant          = -$amountInCent;
        $transaction->id_langue        = 'fr';
        $transaction->date_transaction = date('Y-m-d H:i:s');
        $transaction->status           = \transactions::STATUS_VALID;
        $transaction->id_project       = $bid->getIdProject();
        $transaction->type_transaction = \transactions_types::TYPE_LENDER_LOAN;
        $transaction->ip_client        = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        $transaction->create();

        $walletLine->id_lender                = $bid->getIdLenderAccount();
        $walletLine->type_financial_operation = \wallets_lines::TYPE_BID;
        $walletLine->id_transaction           = $transaction->id_transaction;
        $walletLine->status                   = \wallets_lines::STATUS_VALID;
        $walletLine->type                     = \wallets_lines::VIRTUAL;
        $walletLine->amount                   = -$amountInCent;
        $walletLine->id_project               = $bid->getIdProject();
        $walletLine->create();

        $bid->setIdLenderWalletLine($walletLine->id_wallet_line);
    }

    /**
     * @param      $clientId
     * @param      $amount
     * @param Bids $bid
     */
    private function legacyReleaseBalance($clientId, $amount, Bids $bid)
    {
        /** @var \transactions $transaction */
        $transaction = $this->legacyEntityManager->getRepository('transactions');
        /** @var \wallets_lines $walletLine */
        $walletLine = $this->legacyEntityManager->getRepository('wallets_lines');

        $amountInCent = bcmul($amount, 100);

        $transaction->id_client        = $clientId;
        $transaction->montant          = $amountInCent;
        $transaction->id_langue        = 'fr';
        $transaction->date_transaction = date('Y-m-d H:i:s');
        $transaction->status           = \transactions::STATUS_VALID;
        $transaction->id_project       = $bid->getIdProject();
        $transaction->ip_client        = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        $transaction->id_bid_remb      = $bid->getIdBid();
        $transaction->type_transaction = \transactions_types::TYPE_LENDER_LOAN;
        $transaction->create();

        $walletLine->id_lender                = $bid->getIdLenderAccount();
        $walletLine->type_financial_operation = \wallets_lines::TYPE_BID;
        $walletLine->id_transaction           = $transaction->id_transaction;
        $walletLine->status                   = \wallets_lines::STATUS_VALID;
        $walletLine->type                     = \wallets_lines::VIRTUAL;
        $walletLine->id_bid_remb              = $bid->getIdBid();
        $walletLine->amount                   = $amountInCent;
        $walletLine->id_project               = $bid->getIdProject();
        $walletLine->create();
    }

    /**
     * @param Operation   $operation
     * @param Wallet|null $creditor
     */
    private function credit(Operation $operation, Wallet $creditor = null)
    {
        if ($creditor instanceof Wallet) {
            if ($operation->getType()->getLabel() === OperationType::LENDER_LOAN) {
                $balance = bcadd($creditor->getCommittedBalance(), $operation->getAmount(), 2);
                if ($balance < 0) {
                    new \DomainException('The committed balance must not be lower than zero');
                }
                $creditor->setCommittedBalance($balance);
            } else {
                $balance = bcadd($creditor->getAvailableBalance(), $operation->getAmount(), 2);
                if ($balance < 0) {
                    new \DomainException('The available balance must not be lower than zero');
                }
                $creditor->setAvailableBalance($balance);
            }

            $this->entityManager->flush($creditor);
        }
    }

    /**
     * @param Operation   $operation
     * @param Wallet|null $debtor
     */
    private function debit(Operation $operation, Wallet $debtor = null)
    {
        if ($debtor instanceof Wallet) {
            if ($operation->getType()->getLabel() === OperationType::LENDER_LOAN) {
                $balance = bcsub($debtor->getCommittedBalance(), $operation->getAmount(), 2);
                if ($balance < 0) {
                    throw new \DomainException('The committed balance must not be lower than zero');
                }
                $debtor->setCommittedBalance($balance);
            } else {
                $balance = bcsub($debtor->getAvailableBalance(), $operation->getAmount(), 2);
                if ($balance < 0) {
                    throw new \DomainException('The available balance must not be lower than zero');
                }
                $debtor->setAvailableBalance($balance);
            }

            $this->entityManager->flush($debtor);
        }
    }

    /**
     * @param Wallet $wallet
     * @param array  $parameters
     */
    private function snap(Wallet $wallet, array $parameters)
    {
        $walletSnap = new WalletBalanceHistory();
        $walletSnap->setIdWallet($wallet)
                   ->setAvailableBalance($wallet->getAvailableBalance())
                   ->setCommittedBalance($wallet->getCommittedBalance());
        foreach ($parameters as $item) {
            if ($item instanceof Operation) {
                $walletSnap->setIdOperation($item);
            }
            if ($item instanceof Bids) {
                $walletSnap->setBid($item);
            }
        }
        $this->entityManager->persist($walletSnap);
        $this->entityManager->flush();
    }
}
