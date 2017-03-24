<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service;


use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\Backpayline;
use Unilend\Bundle\CoreBusinessBundle\Entity\Echeanciers;
use Unilend\Bundle\CoreBusinessBundle\Entity\EcheanciersEmprunteur;
use Unilend\Bundle\CoreBusinessBundle\Entity\Loans;
use Unilend\Bundle\CoreBusinessBundle\Entity\OffresBienvenuesDetails;
use Unilend\Bundle\CoreBusinessBundle\Entity\Operation;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\Receptions;
use Unilend\Bundle\CoreBusinessBundle\Entity\TaxType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Transfer;
use Unilend\Bundle\CoreBusinessBundle\Entity\Virements;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;

/**
 * Class OperationManager
 * @package Unilend\Bundle\CoreBusinessBundle\Service
 */
class OperationManager
{
    /**
     * @var EntityManagerSimulator
     */
    private $entityManager;
    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var WalletManager
     */
    private $walletManager;
    /**
     * @var TaxManager
     */
    private $taxManager;

    /**
     * OperationManager constructor.
     *
     * @param EntityManager          $em
     * @param EntityManagerSimulator $entityManager
     * @param WalletManager          $walletManager
     * @param TaxManager             $taxManager
     */
    public function __construct(EntityManager $em, EntityManagerSimulator $entityManager, WalletManager $walletManager, TaxManager $taxManager)
    {
        $this->entityManager   = $entityManager;
        $this->em              = $em;
        $this->walletManager   = $walletManager;
        $this->taxManager      = $taxManager;
    }

    /**
     * @param               $amount
     * @param OperationType $type
     * @param Wallet|null   $debtor
     * @param Wallet|null   $creditor
     * @param array         $parameters
     *
     * @return bool
     * @throws \Exception
     */
    private function newOperation($amount, OperationType $type, Wallet $debtor = null, Wallet $creditor = null, $parameters = [])
    {
        if (bccomp('0', $amount, 2) >= 0) {
            return true;
        }
        $this->em->getConnection()->beginTransaction();
        try {
            $operation = new Operation();
            $operation->setWalletDebtor($debtor)
                      ->setWalletCreditor($creditor)
                      ->setAmount($amount)
                      ->setType($type);

            if (false === is_array($parameters)) {
                $parameters = [$parameters];
            }

            foreach ($parameters as $item) {
                if ($item instanceof Projects) {
                    $operation->setProject($item);
                }
                if ($item instanceof Loans) {
                    $operation->setLoan($item);
                    $operation->setProject($item->getProject());
                }
                if ($item instanceof EcheanciersEmprunteur) {
                    $operation->setPaymentSchedule($item);
                    $operation->setProject($item->getIdProject());
                }
                if ($item instanceof Echeanciers) {
                    $operation->setRepaymentSchedule($item);
                    $operation->setLoan($item->getIdLoan());
                    $operation->setProject($item->getIdLoan()->getProject());
                }
                if ($item instanceof Backpayline) {
                    $operation->setBackpayline($item);
                }
                if ($item instanceof OffresBienvenuesDetails) {
                    $operation->setWelcomeOffer($item);
                }
                if ($item instanceof Virements) {
                    $operation->setWireTransferOut($item);
                    $operation->setProject($item->getProject());
                }
                if ($item instanceof Receptions) {
                    $operation->setWireTransferIn($item);
                    $operation->setProject($item->getIdProject());
                }
                if ($item instanceof Transfer) {
                    $operation->setTransfer($item);
                }
            }
            $this->em->persist($operation);

            $this->walletManager->handle($operation);

            $this->em->flush($operation);

            $this->em->getConnection()->commit();

            return true;
        } catch (\Exception $exception) {
            $this->em->getConnection()->rollBack();
            throw $exception;
        }
    }

    /**
     * @param Wallet                 $wallet
     * @param Receptions|Backpayline $origin
     *
     * @return bool
     */
    public function provisionLenderWallet(Wallet $wallet, $origin)
    {
        if ($origin instanceof Backpayline) {
            $originField  = 'idBackpayline';
            $amountInCent = $origin->getAmount();
        } elseif ($origin instanceof Receptions) {
            $originField  = 'idWireTransferIn';
            $amountInCent = $origin->getMontant();
        } else {
            throw new \InvalidArgumentException('The origin ' . get_class($origin) . ' is not valid');
        }
        $amount        = round(bcdiv($amountInCent, 100, 4), 2);
        $operationType = $this->em->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::LENDER_PROVISION]);

        $operation = null;
        if ($origin instanceof Backpayline) { // Do it only for payline, because the reception can have an operation and then it can be cancelled.
            $operation = $this->em->getRepository('UnilendCoreBusinessBundle:Operation')->findOneBy([$originField => $origin, 'idType' => $operationType]);
        }

        if (null === $operation) {
            $this->newOperation($amount, $operationType, null, $wallet, $origin);

            $this->legacyProvisionLenderWallet($wallet, $origin);
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param Wallet $wallet
     * @param        $origin
     */
    private function legacyProvisionLenderWallet(Wallet $wallet, $origin)
    {
        /** @var \transactions $transaction */
        $transaction = $this->entityManager->getRepository('transactions');
        /** @var \wallets_lines $walletLine */
        $walletLine = $this->entityManager->getRepository('wallets_lines');
        /** @var \lenders_accounts $lenderAccount */
        $lenderAccount = $this->entityManager->getRepository('lenders_accounts');
        /** @var \bank_lines $bankLine */
        $bankLine = $this->entityManager->getRepository('bank_lines');

        $transaction->id_client        = $wallet->getIdClient()->getIdClient();
        $transaction->id_langue        = 'fr';
        $transaction->date_transaction = date('Y-m-d h:i:s');
        $transaction->status           = \transactions::STATUS_VALID;
        if ($origin instanceof Backpayline) {
            $amountInCent                = $origin->getAmount();
            $transactionType             = \transactions_types::TYPE_LENDER_CREDIT_CARD_CREDIT;
            $transaction->id_backpayline = $origin->getIdBackpayline();
        } else {
            /** @var Receptions $origin */
            $amountInCent             = $origin->getMontant();
            $transactionType          = \transactions_types::TYPE_LENDER_BANK_TRANSFER_CREDIT;
            $transaction->id_virement = $origin->getIdReception();
        }
        $transaction->type_transaction = $transactionType;
        $transaction->montant          = $amountInCent;
        $transaction->ip_client        = false === empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        $transaction->create();

        $lenderAccount->get($wallet->getIdClient()->getIdClient(), 'id_client_owner');

        $walletLine->id_lender                = $lenderAccount->id_lender_account;
        $walletLine->type_financial_operation = \wallets_lines::TYPE_MONEY_SUPPLY;
        $walletLine->id_transaction           = $transaction->id_transaction;
        $walletLine->status                   = \wallets_lines::STATUS_VALID;
        $walletLine->type                     = \wallets_lines::PHYSICAL;
        $walletLine->amount                   = $amountInCent;
        $walletLine->create();

        $bankLine->id_wallet_line    = $walletLine->id_wallet_line;
        $bankLine->id_lender_account = $lenderAccount->id_lender_account;
        $bankLine->status            = 1;
        $bankLine->amount            = $amountInCent;
        $bankLine->create();
    }

    /**
     * @param Loans $loan
     */
    public function loan(Loans $loan)
    {
        $operationType  = $this->em->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::LENDER_LOAN]);
        $lenderWallet   = $this->em->getRepository('UnilendCoreBusinessBundle:AccountMatching')->findOneBy(['idLenderAccount' => $loan->getIdLender()])->getIdWallet();
        $borrowerWallet = $this->em->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($loan->getProject()->getIdCompany()->getIdClientOwner(), WalletType::BORROWER);
        $amount         = round(bcdiv($loan->getAmount(), 100, 4), 2);

        $this->newOperation($amount, $operationType, $lenderWallet, $borrowerWallet, $loan);
    }

    /**
     * @param Loans $loan
     */
    public function refuseLoan(Loans $loan)
    {
        $lenderWallet = $this->em->getRepository('UnilendCoreBusinessBundle:AccountMatching')->findOneBy(['idLenderAccount' => $loan->getIdLender()])->getIdWallet();
        $amount       = round(bcdiv($loan->getAmount(), 100, 4), 2);
        $this->walletManager->releaseBalance($lenderWallet, $amount, $loan);
        $this->legacyRefuseLoan($loan, $lenderWallet);
    }

    /**
     * @param Loans  $loan
     * @param Wallet $lenderWallet
     */
    private function legacyRefuseLoan(Loans $loan, Wallet $lenderWallet)
    {
        /** @var \transactions $transaction */
        $transaction = $this->entityManager->getRepository('transactions');
        /** @var \wallets_lines $walletLine */
        $walletLine = $this->entityManager->getRepository('wallets_lines');

        $transaction->id_client        = $lenderWallet->getIdClient()->getIdClient();
        $transaction->montant          = $loan->getAmount();
        $transaction->id_langue        = 'fr';
        $transaction->id_loan_remb     = $loan->getIdLoan();
        $transaction->date_transaction = date('Y-m-d H:i:s');
        $transaction->status           = \transactions::STATUS_VALID;
        $transaction->ip_client        = $_SERVER['REMOTE_ADDR'];
        $transaction->type_transaction = \transactions_types::TYPE_LENDER_LOAN;
        $transaction->create();

        $accountMatching = $this->em->getRepository('UnilendCoreBusinessBundle:AccountMatching')->findOneBy(['idWallet' => $lenderWallet->getId()]);
        $lenderAccount   = $accountMatching->getIdLenderAccount();

        $walletLine->id_lender                = $lenderAccount->getIdLenderAccount();
        $walletLine->type_financial_operation = 20;
        $walletLine->id_transaction           = $transaction->id_transaction;
        $walletLine->status                   = 1;
        $walletLine->type                     = 2;
        $walletLine->amount                   = $loan->getAmount();
        $walletLine->create();
    }

    /**
     * @param Wallet    $wallet
     * @param Virements $wireTransferOut
     *
     * @return bool
     * @throws \Exception
     */
    public function withdrawLenderWallet(Wallet $wallet, Virements $wireTransferOut)
    {
        $this->em->getConnection()->beginTransaction();
        try {
            $operationType = $this->em->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::LENDER_WITHDRAW]);
            $amount        = round(bcdiv($wireTransferOut->getMontant(), 100, 4), 2);

            $this->newOperation($amount, $operationType, $wallet, null, $wireTransferOut);
            $this->legacyWithdrawLenderWallet($wallet, $wireTransferOut);
            $this->em->getConnection()->commit();

            return true;
        } catch (\Exception $e) {
            $this->em->getConnection()->rollBack();
            throw $e;
        }
    }

    /**
     * @param Wallet    $wallet
     * @param Virements $wireTransferOut
     */
    private function legacyWithdrawLenderWallet(Wallet $wallet, Virements $wireTransferOut)
    {
        /** @var \transactions $transaction */
        $transaction = $this->entityManager->getRepository('transactions');
        /** @var \wallets_lines $walletLine */
        $walletLine = $this->entityManager->getRepository('wallets_lines');
        /** @var \bank_lines $bankLine */
        $bankLine = $this->entityManager->getRepository('bank_lines');

        $amount                        = $wireTransferOut->getMontant();
        $transaction->id_client        = $wallet->getIdClient()->getIdClient();
        $transaction->montant          = -$amount;
        $transaction->id_langue        = 'fr';
        $transaction->date_transaction = date('Y-m-d H:i:s');
        $transaction->status           = \transactions::STATUS_VALID;
        $transaction->ip_client        = empty($_SERVER['REMOTE_ADDR']) ? '' : $_SERVER['REMOTE_ADDR'];
        $transaction->type_transaction = \transactions_types::TYPE_LENDER_WITHDRAWAL;
        $transaction->create();

        $accountMatching = $this->em->getRepository('UnilendCoreBusinessBundle:AccountMatching')->findOneBy(['idWallet' => $wallet->getId()]);
        $lenderAccount   = $accountMatching->getIdLenderAccount();

        $walletLine->id_lender                = $lenderAccount->getIdLenderAccount();
        $walletLine->type_financial_operation = \wallets_lines::TYPE_MONEY_SUPPLY;
        $walletLine->id_transaction           = $transaction->id_transaction;
        $walletLine->status                   = \wallets_lines::STATUS_VALID;
        $walletLine->type                     = 1;
        $walletLine->amount                   = $transaction->montant;
        $walletLine->create();

        $bankLine->id_wallet_line    = $walletLine->id_wallet_line;
        $bankLine->id_lender_account = $walletLine->id_lender;
        $bankLine->status            = 1;
        $bankLine->amount            = $transaction->montant;
        $bankLine->create();

        $wireTransferOut->setIdTransaction($transaction->id_transaction);

        $this->em->flush($wireTransferOut);
    }

    /**
     * @param Virements $wireTransferOut
     *
     * @return bool
     * @throws \Exception
     */
    public function withdrawUnilendWallet(Virements $wireTransferOut)
    {
        $this->em->getConnection()->beginTransaction();
        try {
            $walletType    = $this->em->getRepository('UnilendCoreBusinessBundle:WalletType')->findOneBy(['label' => WalletType::UNILEND]);
            $wallet        = $this->em->getRepository('UnilendCoreBusinessBundle:Wallet')->findOneBy(['idType' => $walletType]);
            $operationType = $this->em->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::UNILEND_WITHDRAW]);
            $amount        = round(bcdiv($wireTransferOut->getMontant(), 100, 4), 2);

            $this->newOperation($amount, $operationType, $wallet, null, $wireTransferOut);
            $this->legacyWithdrawUnilendWallet($wireTransferOut);
            $this->em->getConnection()->commit();

            return true;
        } catch (\Exception $e) {
            $this->em->getConnection()->rollBack();
            throw $e;
        }
    }

    /**
     * @param Virements $wireTransferOut
     */
    private function legacyWithdrawUnilendWallet(Virements $wireTransferOut)
    {
        /** @var \transactions $transaction */
        $transaction = $this->entityManager->getRepository('transactions');
        /** @var \bank_unilend $bankUnilend */
        $bankUnilend = $this->entityManager->getRepository('bank_unilend');
        /** @var \platform_account_unilend $accountUnilend */
        $accountUnilend = $this->entityManager->getRepository('platform_account_unilend');

        $total = $wireTransferOut->getMontant();

        $transaction->id_client        = 0;
        $transaction->montant          = $total;
        $transaction->id_langue        = 'fr';
        $transaction->date_transaction = date('Y-m-d H:i:s');
        $transaction->status           = \transactions::STATUS_VALID;
        $transaction->type_transaction = \transactions_types::TYPE_UNILEND_BANK_TRANSFER;
        $transaction->create();

        $wireTransferOut->setIdTransaction($transaction->id_transaction);
        $this->em->flush($wireTransferOut);

        $bankUnilend->id_transaction         = $transaction->id_transaction;
        $bankUnilend->id_echeance_emprunteur = 0;
        $bankUnilend->id_project             = 0;
        $bankUnilend->montant                = '-' . $total;
        $bankUnilend->type                   = \bank_unilend::TYPE_DEBIT_UNILEND;
        $bankUnilend->status                 = 3;
        $bankUnilend->create();

        $accountUnilend->id_transaction = $transaction->id_transaction;
        $accountUnilend->type           = \platform_account_unilend::TYPE_WITHDRAW;
        $accountUnilend->amount         = -$total;
        $accountUnilend->create();
    }

    /**
     * @param Wallet    $wallet
     * @param Virements $wireTransferOut
     * @param           $partUnilend
     *
     * @return Virements
     * @throws \Exception
     */
    public function withdrawBorrowerWallet(Wallet $wallet, Virements $wireTransferOut, $partUnilend)
    {
        $this->em->getConnection()->beginTransaction();
        try {
            $operationType = $this->em->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::BORROWER_WITHDRAW]);
            $amount        = round(bcdiv($wireTransferOut->getMontant(), 100, 4), 2);

            $this->newOperation($amount, $operationType, $wallet, null, $wireTransferOut);
            $this->legacyWithdrawBorrowerWallet($wallet, $wireTransferOut, $partUnilend);
            $this->em->getConnection()->commit();
            return $wireTransferOut;
        } catch (\Exception $e) {
            $this->em->getConnection()->rollBack();
            throw $e;
        }
    }

    /**
     * @param Wallet    $wallet
     * @param Virements $wireTransferOut
     * @param           $partUnilend
     */
    private function legacyWithdrawBorrowerWallet(Wallet $wallet, Virements $wireTransferOut, $partUnilend)
    {
        /** @var \transactions $transaction */
        $transaction = $this->entityManager->getRepository('transactions');
        /** @var \bank_unilend $bankUnilend */
        $bankUnilend = $this->entityManager->getRepository('bank_unilend');
        /** @var \platform_account_unilend $accountUnilend */
        $accountUnilend = $this->entityManager->getRepository('platform_account_unilend');

        $transaction->id_client        = $wallet->getIdClient()->getIdClient();
        $transaction->montant          = -$wireTransferOut->getMontant();
        $transaction->montant_unilend  = bcmul($partUnilend, 100);
        $transaction->id_langue        = 'fr';
        $transaction->id_project       = $wireTransferOut->getProject()->getIdProject();
        $transaction->date_transaction = date('Y-m-d H:i:s');
        $transaction->status           = \transactions::STATUS_VALID;
        $transaction->ip_client        = $_SERVER['REMOTE_ADDR'];
        $transaction->type_transaction = \transactions_types::TYPE_BORROWER_BANK_TRANSFER_CREDIT;
        $transaction->create();

        $bankUnilend->id_transaction = $transaction->id_transaction;
        $bankUnilend->id_project     = $wireTransferOut->getProject()->getIdProject();
        $bankUnilend->montant        = bcmul($partUnilend, 100);
        $bankUnilend->create();

        $accountUnilend->id_transaction = $transaction->id_transaction;
        $accountUnilend->id_project     = $wireTransferOut->getProject()->getIdProject();
        $accountUnilend->amount         = bcmul($partUnilend, 100);
        $accountUnilend->type           = \platform_account_unilend::TYPE_COMMISSION_PROJECT;
        $accountUnilend->create();

        $wireTransferOut->setIdTransaction($transaction->id_transaction);
        $this->em->flush($wireTransferOut);
    }

    /**
     * @param Wallet                  $wallet
     * @param OffresBienvenuesDetails $welcomeOffer
     */
    public function newWelcomeOffer(Wallet $wallet, OffresBienvenuesDetails $welcomeOffer)
    {
        $amount            = round(bcdiv($welcomeOffer->getMontant(), 100, 4), 2);
        $operationType     = $this->em->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::UNILEND_PROMOTIONAL_OPERATION]);
        $unilendWalletType = $this->em->getRepository('UnilendCoreBusinessBundle:WalletType')->findOneBy(['label' => WalletType::UNILEND_PROMOTIONAL_OPERATION]);
        $unilendWallet     = $this->em->getRepository('UnilendCoreBusinessBundle:Wallet')->findOneBy(['idType' => $unilendWalletType]);
        $this->newOperation($amount, $operationType, $unilendWallet, $wallet, $welcomeOffer);

        $this->legacyNewWelcomeOffer($wallet, $welcomeOffer);
    }

    /**
     * @param Wallet                  $wallet
     * @param OffresBienvenuesDetails $welcomeOffer
     */
    private function legacyNewWelcomeOffer(Wallet $wallet, OffresBienvenuesDetails $welcomeOffer)
    {
        /** @var \transactions $transaction */
        $transaction = $this->entityManager->getRepository('transactions');
        /** @var \wallets_lines $walletLine */
        $walletLine = $this->entityManager->getRepository('wallets_lines');
        /** @var \bank_unilend $unilendBank */
        $unilendBank = $this->entityManager->getRepository('bank_unilend');

        $transaction->id_client                 = $wallet->getIdClient()->getIdClient();
        $transaction->montant                   = $welcomeOffer->getMontant();
        $transaction->id_offre_bienvenue_detail = $welcomeOffer->getIdOffreBienvenueDetail();
        $transaction->id_langue                 = 'fr';
        $transaction->date_transaction          = date('Y-m-d H:i:s');
        $transaction->status                    = \transactions::STATUS_VALID;
        $transaction->type_transaction          = \transactions_types::TYPE_WELCOME_OFFER;
        $transaction->create();

        $accountMatching = $this->em->getRepository('UnilendCoreBusinessBundle:AccountMatching')->findOneBy(['idWallet' => $wallet->getId()]);
        $lenderAccount   = $accountMatching->getIdLenderAccount();

        $walletLine->id_lender                = $lenderAccount->getIdLenderAccount();
        $walletLine->type_financial_operation = \wallets_lines::TYPE_MONEY_SUPPLY;
        $walletLine->id_transaction           = $transaction->id_transaction;
        $walletLine->status                   = \wallets_lines::STATUS_VALID;
        $walletLine->type                     = \wallets_lines::PHYSICAL;
        $walletLine->amount                   = $welcomeOffer->getMontant();
        $walletLine->create();

        $unilendBank->id_transaction = $transaction->id_transaction;
        $unilendBank->montant        = '-' . $welcomeOffer->getMontant();
        $unilendBank->type           = \bank_unilend::TYPE_UNILEND_WELCOME_OFFER_PATRONAGE;
        $unilendBank->create();
    }

    /**
     * @param Wallet                  $wallet
     * @param OffresBienvenuesDetails $welcomeOffer
     */
    public function cancelWelcomeOffer(Wallet $wallet, OffresBienvenuesDetails $welcomeOffer)
    {
        $amount            = round(bcdiv($welcomeOffer->getMontant(), 100, 4), 2);
        $operationType     = $this->em->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::UNILEND_PROMOTIONAL_OPERATION_CANCEL]);
        $unilendWalletType = $this->em->getRepository('UnilendCoreBusinessBundle:WalletType')->findOneBy(['label' => WalletType::UNILEND_PROMOTIONAL_OPERATION]);
        $unilendWallet     = $this->em->getRepository('UnilendCoreBusinessBundle:Wallet')->findOneBy(['idType' => $unilendWalletType]);
        $this->newOperation($amount, $operationType, $wallet, $unilendWallet, $welcomeOffer);

        $this->legacyCancelWelcomeOffer($wallet, $welcomeOffer);
    }

    /**
     * @param Wallet                  $wallet
     * @param OffresBienvenuesDetails $welcomeOffer
     */
    private function legacyCancelWelcomeOffer(Wallet $wallet, OffresBienvenuesDetails $welcomeOffer)
    {
        /** @var \transactions $transaction */
        $transaction = $this->entityManager->getRepository('transactions');
        /** @var \wallets_lines $walletLine */
        $walletLine = $this->entityManager->getRepository('wallets_lines');
        /** @var \bank_unilend $unilendBank */
        $unilendBank = $this->entityManager->getRepository('bank_unilend');

        $transaction->id_client                 = $wallet->getIdClient()->getIdClient();
        $transaction->montant                   = -$welcomeOffer->getMontant();
        $transaction->id_offre_bienvenue_detail = $welcomeOffer->getIdOffreBienvenueDetail();
        $transaction->id_langue                 = 'fr';
        $transaction->date_transaction          = date('Y-m-d H:i:s');
        $transaction->status                    = \transactions::STATUS_VALID;
        $transaction->type_transaction          = \transactions_types::TYPE_WELCOME_OFFER_CANCELLATION;
        $transaction->create();

        $accountMatching = $this->em->getRepository('UnilendCoreBusinessBundle:AccountMatching')->findOneBy(['idWallet' => $wallet->getId()]);
        $lenderAccount   = $accountMatching->getIdLenderAccount();

        $walletLine->id_lender                = $lenderAccount->getIdLenderAccount();
        $walletLine->type_financial_operation = \wallets_lines::TYPE_MONEY_SUPPLY;
        $walletLine->id_transaction           = $transaction->id_transaction;
        $walletLine->status                   = 1;
        $walletLine->type                     = 1;
        $walletLine->amount                   = -$welcomeOffer->getMontant();
        $walletLine->create();

        $unilendBank->id_transaction = $transaction->id_transaction;
        $unilendBank->montant        = abs($welcomeOffer->getMontant());
        $unilendBank->type           = \bank_unilend::TYPE_UNILEND_WELCOME_OFFER_PATRONAGE;
        $unilendBank->create();
    }

    /**
     * @param Wallet     $wallet
     * @param float      $amount
     * @param Receptions $reception
     *
     * @return bool
     */
    public function cancelProvisionLenderWallet(Wallet $wallet, $amount, Receptions $reception)
    {
        $operationType = $this->em->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::LENDER_PROVISION]);
        $operation     = $this->em->getRepository('UnilendCoreBusinessBundle:Operation')->findOneBy(['idWireTransferIn' => $reception, 'idWalletCreditor' => $wallet, 'idType' => $operationType]);
        if (null === $operation) {
            return false;
        }

        $operationType = $this->em->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::LENDER_PROVISION_CANCEL]);
        $this->newOperation($amount, $operationType, $wallet, null, $reception);

        $this->legacyCancelProvisionLenderWallet($reception);

        return true;
    }

    /**
     * @param Receptions $reception
     */
    private function legacyCancelProvisionLenderWallet(Receptions $reception)
    {
        /** @var \transactions $transaction */
        $transaction = $this->entityManager->getRepository('transactions');
        /** @var \wallets_lines $walletLine */
        $walletLine = $this->entityManager->getRepository('wallets_lines');
        /** @var \bank_unilend $unilendBank */
        $bankLine = $this->entityManager->getRepository('bank_lines');

        $transaction->get($reception->getIdReception(), 'status = ' . \transactions::STATUS_VALID . ' AND id_virement');
        $walletLine->get($transaction->id_transaction, 'id_transaction');
        $bankLine->delete($walletLine->id_wallet_line, 'id_wallet_line');
        $walletLine->delete($transaction->id_transaction, 'id_transaction');

        $transaction->status = \transactions::STATUS_CANCELED;
        $transaction->update();
    }

    /**
     * @param Wallet     $wallet
     * @param float      $amount
     * @param Receptions $reception
     *
     * @return bool
     */
    public function cancelProvisionBorrowerWallet(Wallet $wallet, $amount, Receptions $reception)
    {
        $operationType = $this->em->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::BORROWER_PROVISION]);
        $operation     = $this->em->getRepository('UnilendCoreBusinessBundle:Operation')->findOneBy([
            'idWireTransferIn' => $reception->getIdReception(),
            'idWalletCreditor' => $wallet,
            'idType'           => $operationType
        ]);
        if (null === $operation) {
            return false;
        }

        $operationType = $this->em->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::BORROWER_PROVISION_CANCEL]);
        $this->newOperation($amount, $operationType, $wallet, null, $reception);

        $this->legacyCancelProvisionBorrowWallet($reception);

        return true;
    }

    /**
     * @param Receptions $reception
     */
    private function legacyCancelProvisionBorrowWallet(Receptions $reception)
    {
        /** @var \transactions $transaction */
        $transaction = $this->entityManager->getRepository('transactions');
        /** @var \bank_unilend $unilendBank */
        $bankUnilend = $this->entityManager->getRepository('bank_unilend');

        $transaction->get($reception->getIdReception(), 'status = ' . \transactions::STATUS_VALID . ' AND type_transaction = ' . \transactions_types::TYPE_BORROWER_REPAYMENT . ' AND id_prelevement');
        $bankUnilend->delete($transaction->id_transaction, 'id_transaction');
        $transaction->status  = \transactions::STATUS_CANCELED;
        $transaction->id_user = $_SESSION['user']['id_user'];
        $transaction->update();
    }

    /**
     * @param Wallet     $wallet
     * @param float      $amount
     * @param Receptions $reception
     *
     * @return bool
     */
    public function rejectProvisionBorrowerWallet(Wallet $wallet, $amount, Receptions $reception)
    {
        $operationType = $this->em->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::BORROWER_PROVISION]);
        $operation     = $this->em->getRepository('UnilendCoreBusinessBundle:Operation')->findOneBy([
            'idWireTransferIn' => $reception->getIdReception(),
            'idWalletCreditor' => $wallet,
            'idType'           => $operationType
        ]);
        if (null === $operation) {
            return false;
        }

        $operationType = $this->em->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::BORROWER_PROVISION_CANCEL]);
        $this->newOperation($amount, $operationType, $wallet, null, $reception);

        $this->legacyRejectProvisionBorrowerWallet($reception);

        return true;
    }

    /**
     * @param Receptions $reception
     */
    private function legacyRejectProvisionBorrowerWallet(Receptions $reception)
    {
        /** @var \transactions $transaction */
        $transaction = $this->entityManager->getRepository('transactions');
        /** @var \bank_unilend $unilendBank */
        $bankUnilend = $this->entityManager->getRepository('bank_unilend');

        $transaction->id_prelevement   = $reception->getIdReception();
        $transaction->id_client        = $reception->getIdClient()->getIdClient();
        $transaction->montant          = -$reception->getMontant();
        $transaction->id_langue        = 'fr';
        $transaction->date_transaction = date('Y-m-d H:i:s');
        $transaction->status           = \transactions::STATUS_VALID;
        $transaction->type_transaction = \transactions_types::TYPE_BORROWER_REPAYMENT_REJECTION;
        $transaction->ip_client        = $_SERVER['REMOTE_ADDR'];
        $transaction->id_user          = isset($_SESSION['user']['id_user']) ? $_SESSION['user']['id_user'] : '';
        $transaction->create();

        $bankUnilend->id_transaction = $transaction->id_transaction;
        $bankUnilend->id_project     = $reception->getIdProject()->getIdProject();
        $bankUnilend->montant        = -$reception->getMontant();
        $bankUnilend->type           = 1;
        $bankUnilend->create();
    }

    /**
     * @param Receptions $reception
     *
     * @return bool
     */
    public function provisionBorrowerWallet(Receptions $reception)
    {
        $wallet = $this->em->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($reception->getIdClient()->getIdClient(), WalletType::BORROWER);
        if (null === $wallet) {
            return false;
        }

        $amount        = round(bcdiv($reception->getMontant(), 100, 4), 2);
        $operationType = $this->em->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::BORROWER_PROVISION]);
        $this->newOperation($amount, $operationType, null, $wallet, $reception);

        return true;
    }

    /**
     * @param Wallet $wallet
     * @param float  $amount
     *
     * @return bool|Virements
     */
    public function withdrawTaxWallet(Wallet $wallet, $amount)
    {
        switch ($wallet->getIdType()->getLabel()) {
            case WalletType::TAX_FR_ADDITIONAL_CONTRIBUTIONS:
                $type = OperationType::TAX_FR_ADDITIONAL_CONTRIBUTIONS_WITHDRAW;
                break;
            case WalletType::TAX_FR_CRDS:
                $type = OperationType::TAX_FR_CRDS_WITHDRAW;
                break;
            case WalletType::TAX_FR_CSG:
                $type = OperationType::TAX_FR_CSG_WITHDRAW;
                break;
            case WalletType::TAX_FR_SOLIDARITY_DEDUCTIONS:
                $type = OperationType::TAX_FR_SOLIDARITY_DEDUCTIONS_WITHDRAW;
                break;
            case WalletType::TAX_FR_STATUTORY_CONTRIBUTIONS:
                $type = OperationType::TAX_FR_STATUTORY_CONTRIBUTIONS_WITHDRAW;
                break;
            case WalletType::TAX_FR_SOCIAL_DEDUCTIONS:
                $type = OperationType::TAX_FR_SOCIAL_DEDUCTIONS_WITHDRAW;
                break;
            case WalletType::TAX_FR_INCOME_TAX_DEDUCTED_AT_SOURCE:
                $type = OperationType::TAX_FR_INCOME_TAX_DEDUCTED_AT_SOURCE_WITHDRAW;
                break;
            default:
                throw new \InvalidArgumentException('Unsupported wallet type : ' . $wallet->getIdType()->getLabel());
                break;
        }
        $operationType = $this->em->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneByLabel($type);

        return $this->newOperation($amount, $operationType, $wallet);
    }

    /**
     * @param Echeanciers $repaymentSchedule
     */
    public function repayment(Echeanciers $repaymentSchedule)
    {
        $loan                = $repaymentSchedule->getIdLoan();
        $accountMatching     = $this->em->getRepository('UnilendCoreBusinessBundle:AccountMatching')->findOneBy(['idLenderAccount' => $loan->getIdLender()]);
        $lenderWallet        = $accountMatching->getIdWallet();
        $borrowerClientId    = $loan->getProject()->getIdCompany()->getIdClientOwner();
        $borrowerWallet      = $this->em->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($borrowerClientId, WalletType::BORROWER);
        $amountInterestGross = round(bcdiv(bcsub($repaymentSchedule->getInterets(), $repaymentSchedule->getInteretsRembourses()), 100, 4), 2);
        $amountCapital       = round(bcdiv(bcsub($repaymentSchedule->getCapital(), $repaymentSchedule->getCapitalRembourse()), 100, 4), 2);

        $this->repaymentGeneric($borrowerWallet, $lenderWallet, $amountCapital, $amountInterestGross, $repaymentSchedule);
    }

    public function tax(Wallet $lender, Loans $loan, $amountInterestGross, $origin)
    {
        $walletRepo        = $this->em->getRepository('UnilendCoreBusinessBundle:Wallet');
        $walletTypeRepo    = $this->em->getRepository('UnilendCoreBusinessBundle:WalletType');
        $operationTypeRepo = $this->em->getRepository('UnilendCoreBusinessBundle:OperationType');

        $underlyingContract = $loan->getIdTypeContract();
        $taxes              = $this->taxManager->getLenderRepaymentInterestTax($lender->getIdClient(), $amountInterestGross, new \DateTime(), $underlyingContract);

        foreach ($taxes as $type => $tax) {
            $operationType = '';
            $walletType    = '';
            switch ($type) {
                case TaxType::TYPE_INCOME_TAX :
                    $operationType = OperationType::TAX_FR_STATUTORY_CONTRIBUTIONS;
                    $walletType    = WalletType::TAX_FR_STATUTORY_CONTRIBUTIONS;
                    break;
                case TaxType::TYPE_CSG :
                    $operationType = OperationType::TAX_FR_CSG;
                    $walletType    = WalletType::TAX_FR_CSG;
                    break;
                case TaxType::TYPE_SOCIAL_DEDUCTIONS :
                    $operationType = OperationType::TAX_FR_SOCIAL_DEDUCTIONS;
                    $walletType    = WalletType::TAX_FR_SOCIAL_DEDUCTIONS;
                    break;
                case TaxType::TYPE_ADDITIONAL_CONTRIBUTION_TO_SOCIAL_DEDUCTIONS :
                    $operationType = OperationType::TAX_FR_ADDITIONAL_CONTRIBUTIONS;
                    $walletType    = WalletType::TAX_FR_ADDITIONAL_CONTRIBUTIONS;
                    break;
                case TaxType::TYPE_SOLIDARITY_DEDUCTIONS :
                    $operationType = OperationType::TAX_FR_SOLIDARITY_DEDUCTIONS;
                    $walletType    = WalletType::TAX_FR_SOLIDARITY_DEDUCTIONS;
                    break;
                case TaxType::TYPE_CRDS :
                    $operationType = OperationType::TAX_FR_CRDS;
                    $walletType    = WalletType::TAX_FR_CRDS;
                    break;
                case TaxType::TYPE_INCOME_TAX_DEDUCTED_AT_SOURCE :
                    $operationType = OperationType::TAX_FR_INCOME_TAX_DEDUCTED_AT_SOURCE;
                    $walletType    = WalletType::TAX_FR_INCOME_TAX_DEDUCTED_AT_SOURCE;
                    break;
                default :
                    continue;
            }

            $walletTaxType = $walletTypeRepo->findOneBy(['label' => $walletType]);
            $walletTax     = $walletRepo->findOneBy(['idType' => $walletTaxType]);
            $operationType = $operationTypeRepo->findOneBy(['label' => $operationType]);

            $this->newOperation($tax, $operationType, $lender, $walletTax, $origin);
        }
    }

    /**
     * @param EcheanciersEmprunteur $paymentSchedule
     */
    public function repaymentCommission(EcheanciersEmprunteur $paymentSchedule)
    {
        $borrowerWallet    = $this->em->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($paymentSchedule->getIdProject()->getIdCompany()->getIdClientOwner(), WalletType::BORROWER);
        $unilendWalletType = $this->em->getRepository('UnilendCoreBusinessBundle:WalletType')->findOneBy(['label' => WalletType::UNILEND]);
        $unilendWallet     = $this->em->getRepository('UnilendCoreBusinessBundle:Wallet')->findOneBy(['idType' => $unilendWalletType]);
        $amount            = round(bcdiv(bcadd($paymentSchedule->getCommission(), $paymentSchedule->getTva(), 2), 100, 4), 2);

        $operationType = $this->em->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::BORROWER_COMMISSION]);

        $this->newOperation($amount, $operationType, $borrowerWallet, $unilendWallet, $paymentSchedule);
    }

    /**
     * @param Loans $loan
     *
     * @return string
     */
    public function earlyRepayment(Loans $loan)
    {
        /** @var \echeanciers $repaymentSchedule */
        $repaymentSchedule = $this->entityManager->getRepository('echeanciers');

        $outstandingCapital = $repaymentSchedule->getOwedCapital(['id_loan' => $loan->getIdLoan()]);
        $borrowerWallet     = $this->em->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($loan->getProject()->getIdCompany()->getIdClientOwner(), WalletType::BORROWER);
        $accountMatching    = $this->em->getRepository('UnilendCoreBusinessBundle:AccountMatching')->findOneBy(['idLenderAccount' => $loan->getIdLender()]);
        $lenderWallet       = $accountMatching->getIdWallet();

        $this->repaymentGeneric($borrowerWallet, $lenderWallet, $outstandingCapital, 0, $loan);

        $this->legacyEarlyRepayment($loan, $lenderWallet, $outstandingCapital);

        return $outstandingCapital;
    }

    /**
     * @param Loans  $loan
     * @param Wallet $lenderWallet
     * @param        $amount
     */
    private function legacyEarlyRepayment(Loans $loan, Wallet $lenderWallet, $amount)
    {
        /** @var \transactions $transaction */
        $transaction = $this->entityManager->getRepository('transactions');
        /** @var \wallets_lines $walletLine */
        $walletLine = $this->entityManager->getRepository('wallets_lines');

        $transaction->id_client        = $lenderWallet->getIdClient()->getIdClient();
        $transaction->montant          = bcmul($amount, 100);
        $transaction->id_echeancier    = 0; // pas d'id_echeance car multiple
        $transaction->id_loan_remb     = $loan->getIdLoan();
        $transaction->id_project       = $loan->getProject()->getIdProject();
        $transaction->id_langue        = 'fr';
        $transaction->date_transaction = date('Y-m-d H:i:s');
        $transaction->status           = \transactions::STATUS_VALID;
        $transaction->ip_client        = $_SERVER['REMOTE_ADDR'];
        $transaction->type_transaction = \transactions_types::TYPE_LENDER_ANTICIPATED_REPAYMENT;
        $transaction->create();

        $walletLine->id_lender                = $loan->getIdLender();
        $walletLine->type_financial_operation = 40;
        $walletLine->id_loan                  = $loan->getIdLoan();
        $walletLine->id_transaction           = $transaction->id_transaction;
        $walletLine->status                   = 1; // non utilisé
        $walletLine->type                     = 2; // transaction virtuelle
        $walletLine->amount                   = bcmul($amount, 100);
        $walletLine->create();
    }

    /**
     * @param Projects $project
     * @param          $commission
     */
    public function projectCommission(Projects $project, $commission)
    {
        $borrowerWallet    = $this->em->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($project->getIdCompany()->getIdClientOwner(), WalletType::BORROWER);
        $unilendWalletType = $this->em->getRepository('UnilendCoreBusinessBundle:WalletType')->findOneBy(['label' => WalletType::UNILEND]);
        $unilendWallet     = $this->em->getRepository('UnilendCoreBusinessBundle:Wallet')->findOneBy(['idType' => $unilendWalletType]);
        $operationType     = $this->em->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::BORROWER_COMMISSION]);
        $this->newOperation($commission, $operationType, $borrowerWallet, $unilendWallet, $project);
    }

    /**
     * @param Transfer $transfer
     * @param float    $amount
     *
     * @return bool
     */
    public function lenderTransfer(Transfer $transfer, $amount)
    {
        $debtor   = $this->em->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($transfer->getIdClientOrigin(), WalletType::LENDER);
        $creditor = $this->em->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($transfer->getIdClientReceiver(), WalletType::LENDER);
        if (null === $debtor || null === $creditor) {
            return false;
        }
        $operationType = $this->em->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::LENDER_TRANSFER]);
        $this->newOperation($amount, $operationType, $debtor, $creditor, $transfer);

        $this->legacyLenderTransfer($transfer, $amount);

        return true;
    }

    /**
     * @param Transfer $transfer
     * @param          $amount
     */
    private function legacyLenderTransfer(Transfer $transfer, $amount)
    {
        /** @var \transactions $transaction */
        $transaction = $this->entityManager->getRepository('transactions');

        $transaction->id_client        = $transfer->getIdClientOrigin();
        $transaction->montant          = -$amount * 100;
        $transaction->status           = \transactions::STATUS_VALID;
        $transaction->type_transaction = \transactions_types::TYPE_LENDER_BALANCE_TRANSFER;
        $transaction->date_transaction = date('Y-m-d h:i:s');
        $transaction->id_langue        = 'fr';
        $transaction->id_transfer      = $transfer->getIdTransfer();
        $transaction->create();

        $transaction->unsetData();

        $transaction->id_client        = $transfer->getIdClientReceiver();
        $transaction->montant          = $amount * 100;
        $transaction->status           = \transactions::STATUS_VALID;
        $transaction->type_transaction = \transactions_types::TYPE_LENDER_BALANCE_TRANSFER;
        $transaction->date_transaction = date('Y-m-d h:i:s');
        $transaction->id_langue        = 'fr';
        $transaction->id_transfer      = $transfer->getIdTransfer();
        $transaction->create();
    }

    /**
     * @param $amount
     */
    public function provisionUnilendPromotionalWallet($amount)
    {
        $unilendWalletType = $this->em->getRepository('UnilendCoreBusinessBundle:WalletType')->findOneBy(['label' => WalletType::UNILEND_PROMOTIONAL_OPERATION]);
        $unilendWallet     = $this->em->getRepository('UnilendCoreBusinessBundle:Wallet')->findOneBy(['idType' => $unilendWalletType]);

        $operationType = $this->em->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::UNILEND_PROMOTIONAL_OPERATION_PROVISION]);
        $this->newOperation($amount, $operationType, null, $unilendWallet);

        $this->legacyProvisionUnilendWallet($amount);
    }

    /**
     * @param $amount
     */
    private function legacyProvisionUnilendWallet($amount)
    {
        /** @var \transactions $transaction */
        $transaction = $this->entityManager->getRepository('transactions');
        /** @var \bank_unilend $unilendBank */
        $bankUnilend = $this->entityManager->getRepository('bank_unilend');

        $transaction->id_prelevement   = 0;
        $transaction->id_client        = 0;
        $transaction->montant          = $amount * 100;
        $transaction->id_langue        = 'fr';
        $transaction->date_transaction = date('Y-m-d H:i:s');
        $transaction->status           = \transactions::STATUS_VALID;
        $transaction->type_transaction = \transactions_types::TYPE_UNILEND_WELCOME_OFFER_BANK_TRANSFER;
        $transaction->ip_client        = '';
        $transaction->create();

        $bankUnilend->id_transaction = $transaction->id_transaction;
        $bankUnilend->id_project     = 0;
        $bankUnilend->montant        = $amount * 100;
        $bankUnilend->type           = 4; // Unilend welcome offer
        $bankUnilend->create();
    }

    /**
     * @param Wallet     $collector
     * @param Wallet     $borrower
     * @param Receptions $reception
     * @param float      $commission
     *
     * @return bool
     */
    public function provisionCollection(Wallet $collector, Wallet $borrower, Receptions $reception, $commission)
    {
        $this->legacyProvisionCollection($borrower, $reception);
        if ($borrower->getIdType()->getLabel() !== WalletType::BORROWER) {
            return false;
        }
        if ($collector->getIdType()->getLabel() !== WalletType::DEBT_COLLECTOR) {
            return false;
        }
        $amount        = round(bcdiv($reception->getMontant(), 100, 4), 2);
        $operationType = $this->em->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::COLLECTION_COMMISSION_PROVISION]);
        $this->newOperation($commission, $operationType, $collector, $borrower, $reception);
        $operationType = $this->em->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::BORROWER_PROVISION]);
        $this->newOperation($amount, $operationType, null, $borrower, $reception);

        return true;
    }

    /**
     * @param Wallet     $wallet
     * @param Receptions $reception
     */
    private function legacyProvisionCollection(Wallet $wallet, Receptions $reception)
    {
        /** @var \transactions $transaction */
        $transaction = $this->entityManager->getRepository('transactions');

        $transaction->id_virement      = $reception->getIdReception();
        $transaction->id_client        = $wallet->getIdClient()->getIdClient();
        $transaction->id_project       = $reception->getIdProject()->getIdProject();
        $transaction->montant          = $reception->getMontant();
        $transaction->id_langue        = 'fr';
        $transaction->date_transaction = date('Y-m-d H:i:s');
        $transaction->status           = \transactions::STATUS_VALID;
        $transaction->type_transaction = \transactions_types::TYPE_RECOVERY_BANK_TRANSFER;
        $transaction->create();
    }

    /**
     * @param Wallet   $lender
     * @param Wallet   $collector
     * @param          $commission
     * @param Projects $project
     */
    public function payCollectionCommissionByLender(Wallet $lender, Wallet $collector, $commission, Projects $project)
    {
        $operationType = $this->em->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::COLLECTION_COMMISSION_LENDER]);
        $this->newOperation($commission, $operationType, $lender, $collector, $project);
    }

    /**
     * @param Wallet   $borrower
     * @param Wallet   $collector
     * @param          $commission
     * @param Projects $project
     */
    public function payCollectionCommissionByBorrower(Wallet $borrower, Wallet $collector, $commission, Projects $project)
    {
        $operationType = $this->em->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::COLLECTION_COMMISSION_BORROWER]);
        $this->newOperation($commission, $operationType, $borrower, $collector, $project);
    }

    /**
     * Simple version which does not support interest repayment.
     *
     * @param Wallet   $lender
     * @param Projects $project
     * @param          $amount
     * @param          $commission
     *
     * @return bool
     */
    public function repaymentCollection(Wallet $lender, Projects $project, $amount, $commission)
    {
        $this->legacyRepaymentCollection($lender, $amount, $commission, $project);

        $borrower = $this->em->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($project->getIdCompany()->getIdClientOwner(), WalletType::BORROWER);
        if (null === $borrower) {
            return false;
        }

        return $this->repaymentGeneric($borrower, $lender, $amount, 0, $project);
    }

    /**
     * @param Wallet   $wallet
     * @param          $amount
     * @param          $commission
     * @param Projects $project
     */
    public function legacyRepaymentCollection(Wallet $wallet, $amount, $commission, Projects $project)
    {
        /** @var \transactions $transaction */
        $transaction = $this->entityManager->getRepository('transactions');
        /** @var \wallets_lines $walletLine */
        $walletLine = $this->entityManager->getRepository('wallets_lines');

        $amount = bcsub($amount, $commission, 2);

        $transaction->id_project       = $project->getIdProject();
        $transaction->id_client        = $wallet->getIdClient()->getIdClient();
        $transaction->montant          = bcmul($amount, 100);
        $transaction->id_langue        = 'fr';
        $transaction->date_transaction = date('Y-m-d H:i:s');
        $transaction->status           = \transactions::STATUS_VALID;
        $transaction->type_transaction = \transactions_types::TYPE_LENDER_RECOVERY_REPAYMENT;
        $transaction->create();

        $accountMatching = $this->em->getRepository('UnilendCoreBusinessBundle:AccountMatching')->findOneBy(['idWallet' => $wallet->getId()]);
        $lenderAccount   = $accountMatching->getIdLenderAccount();

        $walletLine->id_lender                = $lenderAccount->getIdLenderAccount();
        $walletLine->type_financial_operation = \wallets_lines::TYPE_REPAYMENT;
        $walletLine->id_transaction           = $transaction->id_transaction;
        $walletLine->status                   = \wallets_lines::STATUS_VALID;
        $walletLine->type                     = \wallets_lines::VIRTUAL;
        $walletLine->amount                   = $transaction->montant;
        $walletLine->create();
    }

    /**
     * @param Wallet       $borrower
     * @param Wallet       $lender
     * @param              $capital
     * @param              $interest
     * @param array|object $origins
     *
     * @return bool
     */
    private function repaymentGeneric(Wallet $borrower, Wallet $lender, $capital, $interest, $origins = [])
    {
        if ($borrower->getIdType()->getLabel() !== WalletType::BORROWER) {
            return false;
        }
        if ($lender->getIdType()->getLabel() !== WalletType::LENDER) {
            return false;
        }

        if (false === is_array($origins)) {
            $origins = [$origins];
        }

        if ($capital > 0) {
            $operationType = $this->em->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::CAPITAL_REPAYMENT]);
            $this->newOperation($capital, $operationType, $borrower, $lender, $origins);
        }

        if ($interest > 0) {
            $operationType = $this->em->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::GROSS_INTEREST_REPAYMENT]);
            $this->newOperation($interest, $operationType, $borrower, $lender, $origins);
            $loan = null;
            foreach ($origins as $item) {
                if ($item instanceof Echeanciers) {
                    $loan = $item->getIdLoan();
                }
                if ($item instanceof Loans) {
                    $loan = $item;
                }
            }
            if ($loan instanceof Loans) {
                $this->tax($lender, $loan, $interest, $origins);
            }
        }
        return true;
    }
}
