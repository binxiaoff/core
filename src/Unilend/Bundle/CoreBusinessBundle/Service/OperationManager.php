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


class OperationManager
{
    /**
     * @var EntityManager
     */
    private $entityManager;
    private $em;
    /**
     * @var WalletManager
     */
    private $walletManager;

    public function __construct(EntityManager $em, EntityManagerSimulator $entityManager, WalletManager $walletManager)
    {
        $this->entityManager = $entityManager;
        $this->em            = $em;
        $this->walletManager = $walletManager;
    }

    private function newOperation($amount, OperationType $type, Wallet $debtor = null, Wallet $creditor = null, array $parameters = [])
    {
        $this->em->getConnection()->beginTransaction();
        try {
            $operation = new Operation();
            $operation->setWalletDebtor($debtor)
                      ->setWalletCreditor($creditor)
                      ->setAmount($amount)
                      ->setType($type);

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

        if ($operation instanceof Operation) {
            // Already treated.
            return true;
        }

        $this->newOperation($amount, $operationType, null, $wallet, [$origin]);

        $this->legacyProvisionLenderWallet($wallet, $origin);

        return true;
    }

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

        $transaction->id_client = $wallet->getIdClient()->getIdClient();

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

    public function loan(Loans $loan)
    {
        $operationType  = $this->em->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::LENDER_LOAN]);
        $lenderWallet   = $this->em->getRepository('UnilendCoreBusinessBundle:AccountMatching')->findOneBy(['idLenderAccount' => $loan->getIdLender()])->getIdWallet();
        $borrowerWallet = $this->em->getRepository('UnilendCoreBusinessBundle:Clients')->getWalletByType($loan->getIdProject()->getIdCompany()->getIdClientOwner(), WalletType::BORROWER);
        $amount         = round(bcdiv($loan->getAmount(), 100, 4), 2);

        $this->newOperation($amount, $operationType, $lenderWallet, $borrowerWallet, [$loan]);
    }
}
