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
use Unilend\Bundle\CoreBusinessBundle\Entity\Prelevements;
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
     * @var BorrowerManager
     */
    private $borrowerManager;

    /**
     * OperationManager constructor.
     *
     * @param EntityManager          $em
     * @param EntityManagerSimulator $entityManager
     * @param WalletManager          $walletManager
     * @param TaxManager             $taxManager
     * @param BorrowerManager        $borrowerManager
     */
    public function __construct(EntityManager $em, EntityManagerSimulator $entityManager, WalletManager $walletManager, TaxManager $taxManager, BorrowerManager $borrowerManager)
    {
        $this->entityManager   = $entityManager;
        $this->em              = $em;
        $this->walletManager   = $walletManager;
        $this->taxManager      = $taxManager;
        $this->borrowerManager = $borrowerManager;
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
                    $operation->setProject($item->getIdProject());
                }
                if ($item instanceof EcheanciersEmprunteur) {
                    $operation->setPaymentSchedule($item);
                    $operation->setProject($item->getIdProject());
                }
                if ($item instanceof Echeanciers) {
                    $operation->setRepaymentSchedule($item);
                    $operation->setLoan($item->getIdLoan());
                    $operation->setProject($item->getIdLoan()->getIdProject());
                }
                if ($item instanceof Backpayline) {
                    $operation->setBackpayline($item);
                }
                if ($item instanceof OffresBienvenuesDetails) {
                    $operation->setWelcomeOffer($item);
                }
                if ($item instanceof Virements) {
                    $operation->setWireTransferOut($item);
                    if ($item->getIdProject() instanceof Projects) {
                        $operation->setProject($item->getIdProject());
                    }
                }
                if ($item instanceof Receptions) {
                    $operation->setWireTransferIn($item);
                    if ($item->getIdProject() instanceof Projects) {
                        $operation->setProject($item->getIdProject());
                    }
                }
                if ($item instanceof Transfer) {
                    $operation->setTransfer($item);
                }
            }
            $this->em->persist($operation);

            $this->walletManager->handle($operation);

            $this->em->flush();

            $this->em->getConnection()->commit();

            return true;
        } catch (\Exception $e) {
            $this->em->getConnection()->rollBack();
            throw $e;
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
            $this->em->flush();

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
        $borrowerWallet = $this->em->getRepository('UnilendCoreBusinessBundle:Clients')->getWalletByType($loan->getIdProject()->getIdCompany()->getIdClientOwner(), WalletType::BORROWER);
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
     * @param Wallet $wallet
     * @param        $amount
     *
     * @return Virements
     * @throws \Exception
     */
    public function withdrawLenderWallet(Wallet $wallet, $amount)
    {
        $this->em->getConnection()->beginTransaction();
        try {
            $wireTransferOut = new Virements();
            $wireTransferOut->setIdClient($wallet->getIdClient()->getIdClient());
            $wireTransferOut->setMontant(bcmul($amount, 100));
            $wireTransferOut->setMotif($wallet->getWireTransferPattern());
            $wireTransferOut->setType(Virements::TYPE_LENDER);
            $wireTransferOut->setStatus(Virements::STATUS_PENDING);
            $this->em->persist($wireTransferOut);

            $operationType = $this->em->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::LENDER_WITHDRAW]);
            $this->newOperation($amount, $operationType, $wallet, null, $wireTransferOut);
            $this->em->flush();
            $this->legacyWithdrawLenderWallet($wallet, $wireTransferOut);
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
        $transaction->ip_client        = '';
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

        $this->em->flush();
    }

    /**
     * @param float $amount
     *
     * @return boolean
     * @throws \Exception
     */
    public function withdrawUnilendWallet($amount)
    {
        $this->em->getConnection()->beginTransaction();
        try {
            $walletType = $this->em->getRepository('UnilendCoreBusinessBundle:WalletType')->findOneBy(['label' => WalletType::UNILEND]);
            $wallet     = $this->em->getRepository('UnilendCoreBusinessBundle:Wallet')->findOneBy(['idType' => $walletType]);

            $wireTransferOut = new Virements();
            $wireTransferOut->setMontant(bcmul($amount, 100));
            $wireTransferOut->setMotif('UNILEND_' . date('dmY'));
            $wireTransferOut->setType(Virements::TYPE_UNILEND);
            $wireTransferOut->setStatus(Virements::STATUS_PENDING);
            $this->em->persist($wireTransferOut);

            $operationType = $this->em->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::UNILEND_WITHDRAW]);
            $this->newOperation($amount, $operationType, $wallet, null, $wireTransferOut);
            $this->em->flush();
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
        $this->em->flush();

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
     * @param Wallet $wallet
     * @param        $amount
     * @param null   $origin
     *
     * @return Virements
     * @throws \Exception
     */
    public function withdrawBorrowerWallet(Wallet $wallet, $amount, $origin = null)
    {
        $this->em->getConnection()->beginTransaction();
        try {
            $wireTransferOut = new Virements();
            if ($origin instanceof Projects) {
                $wireTransferOut->setIdProject($origin);
                $wireTransferOut->setMotif($this->borrowerManager->getBorrowerBankTransferLabel($origin));
            }
            $wireTransferOut->setIdClient($wallet->getIdClient()->getIdClient());
            $wireTransferOut->setMontant(bcmul($amount, 100));
            $wireTransferOut->setType(Virements::TYPE_BORROWER);
            $wireTransferOut->setStatus(Virements::STATUS_PENDING);
            $this->em->persist($wireTransferOut);

            $operationType = $this->em->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::BORROWER_WITHDRAW]);
            $this->newOperation($amount, $operationType, $wallet, null, $wireTransferOut);
            $this->em->flush();
            $this->em->getConnection()->commit();
            return $wireTransferOut;
        } catch (\Exception $e) {
            $this->em->getConnection()->rollBack();
            throw $e;
        }
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
        $operationType     = $this->em->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::UNILEND_PROMOTIONAL_OPERATION_CANNCEL]);
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
        $wallet = $this->em->getRepository('UnilendCoreBusinessBundle:Clients')->getWalletByType($reception->getIdClient()->getIdClient(), WalletType::BORROWER);
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
            case WalletType::TAX_CONTRIBUTIONS_ADDITIONNELLES:
                $type = OperationType::TAX_CONTRIBUTIONS_ADDITIONNELLES_WITHDRAW;
                break;
            case WalletType::TAX_CRDS:
                $type = OperationType::TAX_CRDS_WITHDRAW;
                break;
            case WalletType::TAX_CSG:
                $type = OperationType::TAX_CSG_WITHDRAW;
                break;
            case WalletType::TAX_PRELEVEMENTS_DE_SOLIDARITE:
                $type = OperationType::TAX_PRELEVEMENTS_DE_SOLIDARITE_WITHDRAW;
                break;
            case WalletType::TAX_PRELEVEMENTS_OBLIGATOIRES:
                $type = OperationType::TAX_PRELEVEMENTS_OBLIGATOIRES_WITHDRAW;
                break;
            case WalletType::TAX_PRELEVEMENTS_SOCIAUX:
                $type = OperationType::TAX_PRELEVEMENTS_SOCIAUX_WITHDRAW;
                break;
            case WalletType::TAX_RETENUES_A_LA_SOURCE:
                $type = OperationType::TAX_RETENUES_A_LA_SOURCE_WITHDRAW;
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
        $idLender            = $loan->getIdLender();
        $accountMatching     = $this->em->getRepository('UnilendCoreBusinessBundle:AccountMatching')->findOneBy(['idLenderAccount' => $idLender]);
        $lenderWallet        = $accountMatching->getIdWallet();
        $borrowerClientId    = $loan->getIdProject()->getIdCompany()->getIdClientOwner();
        $borrowerWallet      = $this->em->getRepository('UnilendCoreBusinessBundle:Clients')->getWalletByType($borrowerClientId, WalletType::BORROWER);
        $amountInterestGross = round(bcdiv(bcsub($repaymentSchedule->getInterets(), $repaymentSchedule->getInteretsRembourses()), 100, 4), 2);
        $amountCapital       = round(bcdiv(bcsub($repaymentSchedule->getCapital(), $repaymentSchedule->getCapitalRembourse()), 100, 4), 2);
        $operationTypeRepo   = $this->em->getRepository('UnilendCoreBusinessBundle:OperationType');
        $walletTypeRepo      = $this->em->getRepository('UnilendCoreBusinessBundle:WalletType');
        $walletRepo          = $this->em->getRepository('UnilendCoreBusinessBundle:Wallet');

        $operationTypeCapital = $operationTypeRepo->findOneBy(['label' => OperationType::CAPITAL_REPAYMENT]);
        $this->newOperation($amountCapital, $operationTypeCapital, $borrowerWallet, $lenderWallet, $repaymentSchedule);

        $operationTypeInterestGross = $operationTypeRepo->findOneBy(['label' => OperationType::GROSS_INTEREST_REPAYMENT]);
        $this->newOperation($amountInterestGross, $operationTypeInterestGross, $borrowerWallet, $lenderWallet, $repaymentSchedule);

        $underlyingContract = $repaymentSchedule->getIdLoan()->getIdTypeContract();
        $taxes              = $this->taxManager->getLenderRepaymentInterestTax($lenderWallet->getIdClient(), $amountInterestGross, new \DateTime(), $underlyingContract);

        foreach ($taxes as $type => $tax) {
            $operationType = '';
            $walletType    = '';
            switch ($type) {
                case TaxType::TYPE_INCOME_TAX :
                    $operationType = OperationType::TAX_PRELEVEMENTS_OBLIGATOIRES;
                    $walletType    = WalletType::TAX_PRELEVEMENTS_OBLIGATOIRES;
                    break;
                case TaxType::TYPE_CSG :
                    $operationType = OperationType::TAX_CSG;
                    $walletType    = WalletType::TAX_CSG;
                    break;
                case TaxType::TYPE_SOCIAL_DEDUCTIONS :
                    $operationType = OperationType::TAX_PRELEVEMENTS_SOCIAUX;
                    $walletType    = WalletType::TAX_PRELEVEMENTS_SOCIAUX;
                    break;
                case TaxType::TYPE_ADDITIONAL_CONTRIBUTION_TO_SOCIAL_DEDUCTIONS :
                    $operationType = OperationType::TAX_CONTRIBUTIONS_ADDITIONNELLES;
                    $walletType    = WalletType::TAX_CONTRIBUTIONS_ADDITIONNELLES;
                    break;
                case TaxType::TYPE_SOLIDARITY_DEDUCTIONS :
                    $operationType = OperationType::TAX_PRELEVEMENTS_DE_SOLIDARITE;
                    $walletType    = WalletType::TAX_PRELEVEMENTS_DE_SOLIDARITE;
                    break;
                case TaxType::TYPE_CRDS :
                    $operationType = OperationType::TAX_CRDS;
                    $walletType    = WalletType::TAX_CRDS;
                    break;
                case TaxType::TYPE_INCOME_TAX_DEDUCTED_AT_SOURCE :
                    $operationType = OperationType::TAX_RETENUES_A_LA_SOURCE;
                    $walletType    = WalletType::TAX_RETENUES_A_LA_SOURCE;
                    break;
                default :
                    continue;
            }

            $walletTaxType = $walletTypeRepo->findOneBy(['label' => $walletType]);
            $walletTax     = $walletRepo->findOneBy(['idType' => $walletTaxType]);
            $operationType = $operationTypeRepo->findOneBy(['label' => $operationType]);

            $this->newOperation($tax, $operationType, $lenderWallet, $walletTax, $repaymentSchedule);
        }
    }

    /**
     * @param EcheanciersEmprunteur $paymentSchedule
     */
    public function repaymentCommission(EcheanciersEmprunteur $paymentSchedule)
    {
        $borrowerWallet    = $this->em->getRepository('UnilendCoreBusinessBundle:Clients')->getWalletByType($paymentSchedule->getIdProject()->getIdCompany()->getIdClientOwner(), WalletType::BORROWER);
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
        $borrowerWallet     = $this->em->getRepository('UnilendCoreBusinessBundle:Clients')->getWalletByType($loan->getIdProject()->getIdCompany()->getIdClientOwner(), WalletType::BORROWER);
        $accountMatching    = $this->em->getRepository('UnilendCoreBusinessBundle:AccountMatching')->findOneBy(['idLenderAccount' => $loan->getIdLender()]);
        $lenderWallet       = $accountMatching->getIdWallet();
        $operationType      = $this->em->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::CAPITAL_REPAYMENT]);

        $this->newOperation($outstandingCapital, $operationType, $borrowerWallet, $lenderWallet, $loan);

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
        $transaction->id_project       = $loan->getIdProject()->getIdProject();
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
     *
     * @throws \Exception
     */
    public function projectCommission(Projects $project)
    {
        $borrowerWallet    = $this->em->getRepository('UnilendCoreBusinessBundle:Clients')->getWalletByType($project->getIdCompany()->getIdClientOwner(), WalletType::BORROWER);
        $unilendWalletType = $this->em->getRepository('UnilendCoreBusinessBundle:WalletType')->findOneBy(['label' => WalletType::UNILEND]);
        $unilendWallet     = $this->em->getRepository('UnilendCoreBusinessBundle:Wallet')->findOneBy(['idType' => $unilendWalletType]);
        $amountProject     = $project->getAmount();
        $commissionRate    = $this->em->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Part unilend']);
        $commission        = round(bcmul($amountProject, $commissionRate->getValue(), 4), 2);
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
        $debtor   = $this->em->getRepository('UnilendCoreBusinessBundle:Clients')->getWalletByType($transfer->getIdClientOrigin(), WalletType::LENDER);
        $creditor = $this->em->getRepository('UnilendCoreBusinessBundle:Clients')->getWalletByType($transfer->getIdClientReceiver(), WalletType::LENDER);
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
    public function provisionUnilendWallet($amount)
    {
        $unilendWalletType = $this->em->getRepository('UnilendCoreBusinessBundle:WalletType')->findOneBy(['label' => WalletType::UNILEND]);
        $unilendWallet     = $this->em->getRepository('UnilendCoreBusinessBundle:Wallet')->findOneBy(['idType' => $unilendWalletType]);

        $operationType = $this->em->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::UNILEND_PROVISION]);
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
     * @param Wallet     $wallet
     * @param Receptions $reception
     *
     * @return bool
     */
    public function provisionCollection(Wallet $wallet, Receptions $reception)
    {
        $this->legacyProvisionCollection($wallet, $reception);

        if ($wallet->getIdType()->getLabel() !== WalletType::BORROWER) {
            return false;
        }

        $amount = round(bcdiv($reception->getMontant(), 100, 4), 2);

        $operationType = $this->em->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::BORROWER_PROVISION]);
        $this->newOperation($amount, $operationType, null, $wallet, $reception);

        return true;
    }

    public function legacyProvisionCollection(Wallet $wallet, Receptions $reception)
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

    public function repaymentCollection(Wallet $wallet, $amount, Projects $project)
    {
        $this->legacyRepaymentCollection($wallet, $amount, $project);

        if ($wallet->getIdType()->getLabel() !== WalletType::LENDER) {
            return false;
        }

        $operationType = $this->em->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::CAPITAL_REPAYMENT]);
        $this->newOperation($amount, $operationType, null, $wallet, $project);

        return true;
    }

    public function legacyRepaymentCollection(Wallet $wallet, $amount, Projects $project)
    {
        /** @var \transactions $transaction */
        $transaction = $this->entityManager->getRepository('transactions');
        /** @var \wallets_lines $walletLine */
        $walletLine = $this->entityManager->getRepository('wallets_lines');

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
}
