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
     * @var EntityManager
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
     * OperationManager constructor.
     *
     * @param EntityManager          $em
     * @param EntityManagerSimulator $entityManager
     * @param WalletManager          $walletManager
     */
    public function __construct(EntityManager $em, EntityManagerSimulator $entityManager, WalletManager $walletManager)
    {
        $this->entityManager = $entityManager;
        $this->em            = $em;
        $this->walletManager = $walletManager;
    }

    /**
     * @param               $amount
     * @param OperationType $type
     * @param Wallet|null   $debtor
     * @param Wallet|null   $creditor
     * @param               $parameters
     *
     * @throws \Exception
     */
    private function newOperation($amount, OperationType $type, Wallet $debtor = null, Wallet $creditor = null, $parameters)
    {
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
                }
                if ($item instanceof Prelevements) {
                    $operation->setDirectDebit($item);
                }
                if ($item instanceof Transfer) {
                    $operation->setTransfer($item);
                }
            }
            $this->em->persist($operation);

            $this->walletManager->handle($operation);

            $this->em->flush();

            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollBack();
            throw $e;
        }
    }

    /**
     * @param Wallet $wallet
     * @param        $origin
     *
     * @return bool
     */
    public function provisionLenderWallet(Wallet $wallet, $origin)
    {
        if ($origin instanceof Backpayline) {
            $type         = OperationType::LENDER_PROVISION_BY_CREDIT_CARD;
            $originField  = 'idBackpayline';
            $amountInCent = $origin->getAmount();
        } elseif ($origin instanceof Receptions) {
            $type         = OperationType::LENDER_PROVISION_BY_WIRE_TRANSFER;
            $originField  = 'idWireTransferIn';
            $amountInCent = $origin->getMontant();
        } else {
            throw new \InvalidArgumentException('The origin ' . get_class($origin) . ' is not valid');
        }

        $amount = round(bcdiv($amountInCent, 100, 4), 2);

        $operationType = $this->em->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => $type]);
        $operation     = $this->em->getRepository('UnilendCoreBusinessBundle:Operation')->findOneBy([$originField => $origin, 'idType' => $operationType]);

        if (null === $operation) {
            $this->newOperation($amount, $operationType, null, $wallet, $origin);

            $this->legacyProvisionLenderWallet($wallet, $origin);
        }

        $this->em->flush();

        return true;
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
        $transaction->ip_client       = false === empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
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
        $operationType  = $this->em->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::LENDER_LOAN_REFUSED]);
        $lenderWallet   = $this->em->getRepository('UnilendCoreBusinessBundle:AccountMatching')->findOneBy(['idLenderAccount' => $loan->getIdLender()])->getIdWallet();
        $borrowerWallet = $this->em->getRepository('UnilendCoreBusinessBundle:Clients')->getWalletByType($loan->getIdProject()->getIdCompany()->getIdClientOwner(), WalletType::BORROWER);
        $amount         = round(bcdiv($loan->getAmount(), 100, 4), 2);

        $this->newOperation($amount, $operationType, $borrowerWallet, $lenderWallet, $loan);

        $this->legacyRefuseLoan($loan, $lenderWallet);
    }

    /**
     * @param Loans  $loan
     * @param Wallet $lenderWallet
     */
    public function legacyRefuseLoan(Loans $loan, Wallet $lenderWallet)
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
     * @param null   $origin
     *
     * @return Virements
     * @throws \Exception
     */
    public function withdraw(Wallet $wallet, $amount, $origin = null)
    {
        $this->em->getConnection()->beginTransaction();
        try {
            $wireTransferOut = new Virements();

            switch ($wallet->getIdType()->getLabel()) {
                case WalletType::LENDER :
                    $operationTypeLabel = OperationType::LENDER_WITHDRAW_BY_WIRE_TRANSFER;
                    $type               = Virements::TYPE_LENDER;
                    break;
                case WalletType::BORROWER :
                    $operationTypeLabel = OperationType::BORROWER_WITHDRAW_BY_WIRE_TRANSFER;
                    $type               = Virements::TYPE_BORROWER;
                    if ($origin instanceof Projects) {
                        $wireTransferOut->setIdProject($origin);
                    }
                    break;
                case WalletType::UNILEND :
                    $operationTypeLabel = OperationType::UNILEND_WITHDRAW_BY_WIRE_TRANSFER;
                    $type               = Virements::TYPE_UNILEND;
                    break;
                default:
                    throw new \InvalidArgumentException('Unsupported wallet type : ' . $wallet->getIdType()->getLabel() . 'by withdraw');
                    break;
            }
            $wireTransferOut->setIdClient($wallet->getIdClient()->getIdClient());
            $wireTransferOut->setMontant(bcmul($amount, 100));
            $wireTransferOut->setMotif($wallet->getWireTransferPattern());
            $wireTransferOut->setType($type);
            $wireTransferOut->setStatus(Virements::STATUS_PENDING);
            $this->em->persist($wireTransferOut);

            $operationType = $this->em->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => $operationTypeLabel]);

            $this->newOperation($amount, $operationType, $wallet, null, $wireTransferOut);
            $this->em->getConnection()->commit();
            $this->em->flush();

            $this->legacyWithdraw($wallet, $wireTransferOut);

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
    private function legacyWithdraw(Wallet $wallet, Virements $wireTransferOut)
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
     * @param Wallet                  $wallet
     * @param OffresBienvenuesDetails $welcomeOffer
     */
    public function newWelcomeOffer(Wallet $wallet, OffresBienvenuesDetails $welcomeOffer)
    {
        $amount        = round(bcdiv($welcomeOffer->getMontant(), 100, 4), 2);
        $operationType = $this->em->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::UNILEND_PROMOTIONAL_OPERATION]);
        $unilendWallet = $this->em->getRepository('UnilendCoreBusinessBundle:Wallet')->findOneBy(['idType' => WalletType::UNILEND]);
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
    public function withdrawWelcomeOffer(Wallet $wallet, OffresBienvenuesDetails $welcomeOffer)
    {
        $amount        = round(bcdiv($welcomeOffer->getMontant(), 100, 4), 2);
        $operationType = $this->em->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::UNILEND_PROMOTIONAL_OPERATION_WITHDRAW]);
        $unilendWallet = $this->em->getRepository('UnilendCoreBusinessBundle:Wallet')->findOneBy(['idType' => WalletType::UNILEND]);
        $this->newOperation($amount, $operationType, $wallet, $unilendWallet, $welcomeOffer);

        $this->legacyWithdrawWelcomeOffer($wallet, $welcomeOffer);
    }

    /**
     * @param Wallet                  $wallet
     * @param OffresBienvenuesDetails $welcomeOffer
     */
    private function legacyWithdrawWelcomeOffer(Wallet $wallet, OffresBienvenuesDetails $welcomeOffer)
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
     * @param Receptions $reception
     *
     * @return bool
     */
    public function cancelProvisionLenderWallet(Receptions $reception)
    {
        $wallet = $this->em->getRepository('UnilendCoreBusinessBundle:Clients')->getWalletByType($reception->getIdClient(), WalletType::LENDER);
        if (null === $wallet) {
            return false;
        }
        $operation = $this->em->getRepository('UnilendCoreBusinessBundle:Operation')->findOneBy(['idWireTransferIn' => $reception->getIdReception()]);
        if (null === $operation) {
            return false;
        }

        $reception->setIdClient(0); // todo: make it nullable
        $reception->setStatusBo(Receptions::STATUS_PENDING);
        $reception->setRemb(0); // todo: delete the field
        $this->em->flush();

        $amount        = round(bcdiv($reception->getMontant(), 100, 4), 2);
        $operationType = $this->em->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::LENDER_PROVISION_BY_WIRE_TRANSFER_CANCEL]);
        $this->newOperation($amount, $operationType, $wallet, null, $reception);

        $this->legacyCancelProvisionLenderWallet($reception);

        return true;
    }

    /**
     * @param Receptions $reception
     */
    public function legacyCancelProvisionLenderWallet(Receptions $reception)
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
     * @param Receptions $reception
     *
     * @return bool
     */
    public function cancelProvisionBorrowerWallet(Receptions $reception)
    {
        $wallet = $this->em->getRepository('UnilendCoreBusinessBundle:Clients')->getWalletByType($reception->getIdClient(), WalletType::BORROWER);
        if (null === $wallet) {
            return false;
        }
        $operation = $this->em->getRepository('UnilendCoreBusinessBundle:Operation')->findOneBy(['idWireTransferIn' => $reception->getIdReception()]);
        if (null === $operation) {
            return false;
        }

        $reception->setIdClient(0); // todo: make it nullable and FK
        $reception->setIdProject(0); // todo: make it nullable and FK
        $reception->setStatusBo(Receptions::STATUS_PENDING);
        $reception->setRemb(0); // todo: delete the field
        $this->em->flush();

        $amount        = round(bcdiv($reception->getMontant(), 100, 4), 2);
        $operationType = $this->em->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::BORROWER_PROVISION_BY_DIRECT_DEBIT_CANCEL]);
        $this->newOperation($amount, $operationType, $wallet, null, $reception);

        $this->legacyCancelProvisionLenderWallet($reception);

        return true;
    }

    /**
     * @param Receptions $reception
     */
    public function legacyCancelProvisionBorrowWallet(Receptions $reception)
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
}
