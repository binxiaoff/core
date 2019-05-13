<?php

namespace Unilend\Service;

use Doctrine\ORM\EntityManagerInterface;
use Unilend\Entity\{Backpayline, CloseOutNettingRepayment, Echeanciers, EcheanciersEmprunteur, Loans, OffresBienvenuesDetails, Operation, OperationSubType, OperationType,
    ProjectRepaymentTaskLog, Projects, Receptions, Sponsorship, TaxType, Transfer, Virements, Wallet, WalletType};
use Unilend\Service\Simulator\EntityManager as EntityManagerSimulator;

/**
 * Class OperationManager
 * @package Unilend\Service
 */
class OperationManager
{
    /** @var EntityManagerSimulator */
    private $entityManagerSimulator;
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var WalletManager */
    private $walletManager;
    /** @var TaxManager */
    private $taxManager;

    const OPERATION_MANAGER_EXCEPTION_CODE = 2;

    /**
     * @param EntityManagerInterface $entityManager
     * @param EntityManagerSimulator $entityManagerSimulator
     * @param WalletManager          $walletManager
     * @param TaxManager             $taxManager
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        EntityManagerSimulator $entityManagerSimulator,
        WalletManager $walletManager,
        TaxManager $taxManager
    )
    {
        $this->entityManagerSimulator = $entityManagerSimulator;
        $this->entityManager          = $entityManager;
        $this->walletManager          = $walletManager;
        $this->taxManager             = $taxManager;
    }

    /**
     * @param                       $amount
     * @param OperationType         $type
     * @param OperationSubType|null $subType
     * @param Wallet|null           $debtor
     * @param Wallet|null           $creditor
     * @param array|object          $parameters
     *
     * @return bool
     * @throws \Exception
     */
    private function newOperation($amount, OperationType $type, OperationSubType $subType = null, Wallet $debtor = null, Wallet $creditor = null, $parameters = [])
    {
        if (bccomp('0', $amount, 2) >= 0) {
            return true;
        }

        if (null === $debtor && null === $creditor) {
            throw new \InvalidArgumentException('Both the debtor and creditor wallets are null.', self::OPERATION_MANAGER_EXCEPTION_CODE);
        }

        $this->entityManager->getConnection()->beginTransaction();
        try {
            $operation = new Operation();
            $operation->setWalletDebtor($debtor)
                ->setWalletCreditor($creditor)
                ->setAmount($amount)
                ->setType($type)
                ->setSubType($subType);

            if (false === is_array($parameters)) {
                $parameters = [$parameters];
            }

            foreach ($parameters as $item) {
                if ($item instanceof Projects) {
                    $operation->setProject($item);
                }
                if ($item instanceof Loans) {
                    $operation->setLoan($item);
                    $operation->setProject($item->getTranche());
                }
                if ($item instanceof EcheanciersEmprunteur) {
                    $operation->setPaymentSchedule($item);
                    $operation->setProject($item->getIdProject());
                }
                if ($item instanceof Echeanciers) {
                    $operation->setRepaymentSchedule($item);
                    $operation->setLoan($item->getIdLoan());
                    $operation->setProject($item->getIdLoan()->getTranche());
                }
                if ($item instanceof Backpayline) {
                    $operation->setBackpayline($item);
                }
                if ($item instanceof OffresBienvenuesDetails) {
                    $operation->setWelcomeOffer($item);
                }
                if ($item instanceof Sponsorship) {
                    $operation->setSponsorship($item);
                }
                if ($item instanceof Virements) {
                    $operation->setWireTransferOut($item);
                    $operation->setProject($item->getTranche());
                }
                if ($item instanceof Receptions) {
                    $operation->setWireTransferIn($item);
                    $operation->setProject($item->getIdProject());
                }
                if ($item instanceof Transfer) {
                    $operation->setTransfer($item);
                }
                if ($item instanceof ProjectRepaymentTaskLog) {
                    $operation->setIdRepaymentTaskLog($item);
                    $operation->setProject($item->getIdTask()->getIdProject());
                    $operation->setWireTransferIn($item->getIdTask()->getIdWireTransferIn());
                }
                if ($item instanceof Operation) {
                    $operation->setProject($item->getTranche())
                        ->setBackpayline($item->getBackpayline())
                        ->setLoan($item->getLoan())
                        ->setPaymentSchedule($item->getPaymentSchedule())
                        ->setRepaymentSchedule($item->getRepaymentSchedule())
                        ->setTransfer($item->getTransfer())
                        ->setWelcomeOffer($item->getWelcomeOffer())
                        ->setWireTransferIn($item->getWireTransferIn())
                        ->setWireTransferOut($item->getWireTransferOut())
                        ->setIdRepaymentTaskLog($item->getIdRepaymentTaskLog())
                        ->setSponsorship($item->getSponsorship());
                }
            }

            $this->entityManager->persist($operation);

            $this->walletManager->handle($operation);

            $this->entityManager->flush($operation);

            $this->entityManager->getConnection()->commit();

            return true;
        } catch (\Exception $exception) {
            $this->entityManager->getConnection()->rollBack();
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
        $operationType = $this->entityManager->getRepository(OperationType::class)->findOneBy(['label' => OperationType::LENDER_PROVISION]);

        $operation = $this->entityManager->getRepository(Operation::class)->findOneBy([$originField => $origin, 'idType' => $operationType]);

        if (null === $operation) {
            $this->newOperation($amount, $operationType, null, null, $wallet, $origin);

            return true;
        } else {
            return false;
        }
    }

    /**
     * @param Loans $loan
     */
    public function loan(Loans $loan)
    {
        $operationType  = $this->entityManager->getRepository(OperationType::class)->findOneBy(['label' => OperationType::LENDER_LOAN]);
        $lenderWallet   = $loan->getWallet();
        $borrowerWallet = $this->entityManager->getRepository(Wallet::class)->getWalletByType($loan->getTranche()->getIdCompany()->getIdClientOwner(), WalletType::BORROWER);
        $amount         = round(bcdiv($loan->getAmount(), 100, 4), 2);

        $this->newOperation($amount, $operationType, null, $lenderWallet, $borrowerWallet, $loan);
    }

    /**
     * @param Loans $loan
     */
    public function refuseLoan(Loans $loan)
    {
        $lenderWallet = $loan->getWallet();
        $amount       = round(bcdiv($loan->getAmount(), 100, 4), 2);
        $this->walletManager->releaseBalance($lenderWallet, $amount, $loan);
    }

    public function withdraw(Virements $wireTransferOut)
    {
        switch ($wireTransferOut->getType()) {
            case Virements::TYPE_LENDER:
                $this->withdrawLenderWallet($wireTransferOut);
                break;
            case Virements::TYPE_BORROWER:
                $this->withdrawBorrowerWallet($wireTransferOut);
                break;
            case Virements::TYPE_UNILEND:
                $this->withdrawUnilendWallet($wireTransferOut);
                break;
            case Virements::TYPE_DEBT_COLLECTOR:
                $this->withdrawDebtCollectorWallet($wireTransferOut);
                break;
            default :
                throw new \InvalidArgumentException('Wire transfer out type ' . $wireTransferOut->getType() . ' is not supported.');
        }
    }

    /**
     * @param Virements $wireTransferOut
     *
     */
    private function withdrawLenderWallet(Virements $wireTransferOut)
    {
        $operationType = $this->entityManager->getRepository(OperationType::class)->findOneBy(['label' => OperationType::LENDER_WITHDRAW]);
        $wallet        = $this->entityManager->getRepository(Wallet::class)->getWalletByType($wireTransferOut->getClient(), WalletType::LENDER);
        $amount        = round(bcdiv($wireTransferOut->getMontant(), 100, 4), 2);

        $this->newOperation($amount, $operationType, null, $wallet, null, $wireTransferOut);
    }

    /**
     * @param Virements $wireTransferOut
     *
     * @throws \Exception
     */
    private function withdrawUnilendWallet(Virements $wireTransferOut)
    {
        $walletType    = $this->entityManager->getRepository(WalletType::class)->findOneBy(['label' => WalletType::UNILEND]);
        $wallet        = $this->entityManager->getRepository(Wallet::class)->findOneBy(['idType' => $walletType]);
        $operationType = $this->entityManager->getRepository(OperationType::class)->findOneBy(['label' => OperationType::UNILEND_WITHDRAW]);
        $amount        = round(bcdiv($wireTransferOut->getMontant(), 100, 4), 2);

        $this->newOperation($amount, $operationType, null, $wallet, null, $wireTransferOut);
    }

    /**
     * @param Virements $wireTransferOut
     *
     * @throws \Exception
     */
    private function withdrawBorrowerWallet(Virements $wireTransferOut)
    {
        $operationType = $this->entityManager->getRepository(OperationType::class)->findOneBy(['label' => OperationType::BORROWER_WITHDRAW]);
        $wallet        = $this->entityManager->getRepository(Wallet::class)->getWalletByType($wireTransferOut->getClient(), WalletType::BORROWER);
        $amount        = round(bcdiv($wireTransferOut->getMontant(), 100, 4), 2);

        $this->newOperation($amount, $operationType, null, $wallet, null, $wireTransferOut);
    }

    /**
     * @param Virements $wireTransferOut
     *
     * @throws \Exception
     */
    private function withdrawDebtCollectorWallet(Virements $wireTransferOut)
    {
        $operationType = $this->entityManager->getRepository(OperationType::class)->findOneBy(['label' => OperationType::DEBT_COLLECTOR_WITHDRAW]);
        $wallet        = $this->entityManager->getRepository(Wallet::class)->getWalletByType($wireTransferOut->getClient(), WalletType::DEBT_COLLECTOR);
        $amount        = round(bcdiv($wireTransferOut->getMontant(), 100, 4), 2);

        $this->newOperation($amount, $operationType, null, $wallet, null, $wireTransferOut);
    }

    /**
     * @param Wallet                  $wallet
     * @param OffresBienvenuesDetails $welcomeOffer
     */
    public function newWelcomeOffer(Wallet $wallet, OffresBienvenuesDetails $welcomeOffer)
    {
        $amount            = round(bcdiv($welcomeOffer->getMontant(), 100, 4), 2);
        $operationType     = $this->entityManager->getRepository(OperationType::class)->findOneBy(['label' => OperationType::UNILEND_PROMOTIONAL_OPERATION]);
        $operationSubType  = $this->entityManager->getRepository(OperationSubType::class)->findOneBy(['label' => OperationSubType::UNILEND_PROMOTIONAL_OPERATION_WELCOME_OFFER]);
        $unilendWalletType = $this->entityManager->getRepository(WalletType::class)->findOneBy(['label' => WalletType::UNILEND_PROMOTIONAL_OPERATION]);
        $unilendWallet     = $this->entityManager->getRepository(Wallet::class)->findOneBy(['idType' => $unilendWalletType]);
        $this->newOperation($amount, $operationType, $operationSubType, $unilendWallet, $wallet, $welcomeOffer);
    }

    /**
     * @param Wallet                  $wallet
     * @param OffresBienvenuesDetails $welcomeOffer
     */
    public function cancelWelcomeOffer(Wallet $wallet, OffresBienvenuesDetails $welcomeOffer)
    {
        $amount            = round(bcdiv($welcomeOffer->getMontant(), 100, 4), 2);
        $operationType     = $this->entityManager->getRepository(OperationType::class)->findOneBy(['label' => OperationType::UNILEND_PROMOTIONAL_OPERATION_CANCEL]);
        $operationSubType  = $this->entityManager->getRepository(OperationSubType::class)
            ->findOneBy(['label' => OperationSubType::UNILEND_PROMOTIONAL_OPERATION_CANCEL_WELCOME_OFFER]);
        $unilendWalletType = $this->entityManager->getRepository(WalletType::class)->findOneBy(['label' => WalletType::UNILEND_PROMOTIONAL_OPERATION]);
        $unilendWallet     = $this->entityManager->getRepository(Wallet::class)->findOneBy(['idType' => $unilendWalletType]);
        $this->newOperation($amount, $operationType, $operationSubType, $wallet, $unilendWallet, $welcomeOffer);
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
        $operationType = $this->entityManager->getRepository(OperationType::class)->findOneBy(['label' => OperationType::LENDER_PROVISION]);
        $operation     = $this->entityManager->getRepository(Operation::class)->findOneBy([
            'idWireTransferIn' => $reception,
            'idWalletCreditor' => $wallet,
            'idType'           => $operationType
        ]);
        if (null === $operation) {
            return false;
        }

        $operationType = $this->entityManager->getRepository(OperationType::class)->findOneBy(['label' => OperationType::LENDER_PROVISION_CANCEL]);
        $this->newOperation($amount, $operationType, null, $wallet, null, $reception);

        return true;
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
        $operationType = $this->entityManager->getRepository(OperationType::class)->findOneBy(['label' => OperationType::BORROWER_PROVISION_CANCEL]);
        $this->newOperation($amount, $operationType, null, $wallet, null, $reception);

        return true;
    }

    /**
     * @param Receptions $reception
     *
     * @return bool
     */
    public function provisionBorrowerWallet(Receptions $reception)
    {
        $wallet = $this->entityManager->getRepository(Wallet::class)->getWalletByType($reception->getIdClient()->getIdClient(), WalletType::BORROWER);
        if (null === $wallet) {
            return false;
        }

        $amount        = round(bcdiv($reception->getMontant(), 100, 4), 2);
        $operationType = $this->entityManager->getRepository(OperationType::class)->findOneBy(['label' => OperationType::BORROWER_PROVISION]);

        $operation = $this->entityManager->getRepository(Operation::class)->findOneBy(['idWireTransferIn' => $reception, 'idType' => $operationType]);

        if (null === $operation) {
            $this->newOperation($amount, $operationType, null, null, $wallet, $reception);
            return true;
        } else {
            return false;
        }
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
            case WalletType::TAX_FR_CONTRIBUTIONS_ADDITIONNELLES:
                $type = OperationType::TAX_FR_CONTRIBUTIONS_ADDITIONNELLES_WITHDRAW;
                break;
            case WalletType::TAX_FR_CRDS:
                $type = OperationType::TAX_FR_CRDS_WITHDRAW;
                break;
            case WalletType::TAX_FR_CSG:
                $type = OperationType::TAX_FR_CSG_WITHDRAW;
                break;
            case WalletType::TAX_FR_PRELEVEMENTS_DE_SOLIDARITE:
                $type = OperationType::TAX_FR_PRELEVEMENTS_DE_SOLIDARITE_WITHDRAW;
                break;
            case WalletType::TAX_FR_PRELEVEMENTS_OBLIGATOIRES:
                $type = OperationType::TAX_FR_PRELEVEMENTS_OBLIGATOIRES_WITHDRAW;
                break;
            case WalletType::TAX_FR_PRELEVEMENTS_SOCIAUX:
                $type = OperationType::TAX_FR_PRELEVEMENTS_SOCIAUX_WITHDRAW;
                break;
            case WalletType::TAX_FR_RETENUES_A_LA_SOURCE:
                $type = OperationType::TAX_FR_RETENUES_A_LA_SOURCE_WITHDRAW;
                break;
            default:
                throw new \InvalidArgumentException('Unsupported wallet type : ' . $wallet->getIdType()->getLabel());
                break;
        }
        $operationType = $this->entityManager->getRepository(OperationType::class)->findOneByLabel($type);

        return $this->newOperation($amount, $operationType, null, $wallet, null);
    }

    /**
     * @param float                   $amountCapital
     * @param float                   $amountInterestGross
     * @param Echeanciers             $repaymentSchedule
     * @param ProjectRepaymentTaskLog $projectRepaymentTaskLog
     */
    public function repayment($amountCapital, $amountInterestGross, Echeanciers $repaymentSchedule, ProjectRepaymentTaskLog $projectRepaymentTaskLog)
    {
        $loan           = $repaymentSchedule->getIdLoan();
        $lenderWallet   = $loan->getWallet();
        $borrowerClient = $loan->getTranche()->getIdCompany()->getIdClientOwner();
        $borrowerWallet = $this->entityManager->getRepository(Wallet::class)->getWalletByType($borrowerClient, WalletType::BORROWER);

        $this->repaymentGeneric($borrowerWallet, $lenderWallet, $amountCapital, $amountInterestGross, null, null, [$repaymentSchedule, $projectRepaymentTaskLog]);
    }

    /**
     * Todo: add a date as a parameter so that when we do the regularization, we can take into account the original tax
     *
     * @param Loans  $loan
     * @param        $amountInterestGross
     * @param        $origin
     */
    private function tax(Loans $loan, $amountInterestGross, $origin)
    {
        $walletRepository           = $this->entityManager->getRepository(Wallet::class);
        $walletTypeRepository       = $this->entityManager->getRepository(WalletType::class);
        $operationTypeRepository    = $this->entityManager->getRepository(OperationType::class);
        $operationSubTypeRepository = $this->entityManager->getRepository(OperationSubType::class);

        $underlyingContract = $loan->getUnderlyingContract();
        $taxes              = $this->taxManager->getLenderRepaymentInterestTax($loan->getWallet()->getIdClient(), $amountInterestGross, new \DateTime(), $underlyingContract);

        foreach ($taxes as $type => $tax) {
            $operationType    = null;
            $walletType       = null;
            $operationSubType = null;
            switch ($type) {
                case TaxType::TYPE_STATUTORY_CONTRIBUTIONS:
                    $operationType = OperationType::TAX_FR_PRELEVEMENTS_OBLIGATOIRES;
                    $walletType    = WalletType::TAX_FR_PRELEVEMENTS_OBLIGATOIRES;
                    break;
                case TaxType::TYPE_CSG:
                    $operationType = OperationType::TAX_FR_CSG;
                    $walletType    = WalletType::TAX_FR_CSG;
                    break;
                case TaxType::TYPE_SOCIAL_DEDUCTIONS:
                    $operationType = OperationType::TAX_FR_PRELEVEMENTS_SOCIAUX;
                    $walletType    = WalletType::TAX_FR_PRELEVEMENTS_SOCIAUX;
                    break;
                case TaxType::TYPE_ADDITIONAL_CONTRIBUTION_TO_SOCIAL_DEDUCTIONS:
                    $operationType = OperationType::TAX_FR_CONTRIBUTIONS_ADDITIONNELLES;
                    $walletType    = WalletType::TAX_FR_CONTRIBUTIONS_ADDITIONNELLES;
                    break;
                case TaxType::TYPE_SOLIDARITY_DEDUCTIONS:
                    $operationType = OperationType::TAX_FR_PRELEVEMENTS_DE_SOLIDARITE;
                    $walletType    = WalletType::TAX_FR_PRELEVEMENTS_DE_SOLIDARITE;
                    break;
                case TaxType::TYPE_CRDS:
                    $operationType = OperationType::TAX_FR_CRDS;
                    $walletType    = WalletType::TAX_FR_CRDS;
                    break;
                case TaxType::TYPE_INCOME_TAX_DEDUCTED_AT_SOURCE_PERSON:
                    $operationSubType = OperationSubType::TAX_FR_RETENUES_A_LA_SOURCE_PERSON;
                // no break
                case TaxType::TYPE_INCOME_TAX_DEDUCTED_AT_SOURCE:
                    $operationType = OperationType::TAX_FR_RETENUES_A_LA_SOURCE;
                    $walletType    = WalletType::TAX_FR_RETENUES_A_LA_SOURCE;
                    break;
                default :
                    continue;
            }

            $walletTaxType = $walletTypeRepository->findOneBy(['label' => $walletType]);
            $walletTax     = $walletRepository->findOneBy(['idType' => $walletTaxType]);
            $operationType = $operationTypeRepository->findOneBy(['label' => $operationType]);
            if ($operationSubType) {
                $operationSubType = $operationSubTypeRepository->findOneBy(['label' => $operationSubType]);
            }

            $this->newOperation($tax, $operationType, $operationSubType, $loan->getWallet(), $walletTax, $origin);
        }
    }

    /**
     * @param ProjectRepaymentTaskLog $projectRepaymentTaskLog
     */
    public function repaymentCommission(ProjectRepaymentTaskLog $projectRepaymentTaskLog)
    {
        $payment = null;
        if ($projectRepaymentTaskLog->getIdTask()->getSequence()) {
            $payment = $this->entityManager->getRepository(EcheanciersEmprunteur::class)->findOneBy([
                'idProject' => $projectRepaymentTaskLog->getIdTask()->getIdProject(),
                'ordre'     => $projectRepaymentTaskLog->getIdTask()->getSequence()
            ]);
        }

        $project        = $projectRepaymentTaskLog->getIdTask()->getIdProject();
        $borrowerWallet = $this->entityManager->getRepository(Wallet::class)->getWalletByType($project->getIdCompany()->getIdClientOwner(), WalletType::BORROWER);

        $unilendWalletType = $this->entityManager->getRepository(WalletType::class)->findOneBy(['label' => WalletType::UNILEND]);
        $unilendWallet     = $this->entityManager->getRepository(Wallet::class)->findOneBy(['idType' => $unilendWalletType]);

        $operationType    = $this->entityManager->getRepository(OperationType::class)->findOneBy(['label' => OperationType::BORROWER_COMMISSION]);
        $operationSubType = $this->entityManager->getRepository(OperationSubType::class)->findOneBy(['label' => OperationSubType::BORROWER_COMMISSION_REPAYMENT]);

        $this->newOperation($projectRepaymentTaskLog->getIdTask()->getCommissionUnilend(), $operationType, $operationSubType, $borrowerWallet, $unilendWallet,
            [$payment, $projectRepaymentTaskLog]);
    }

    /**
     * @param Loans                   $loan
     * @param ProjectRepaymentTaskLog $projectRepaymentTaskLog
     *
     * @return string
     */
    public function earlyRepayment(Loans $loan, ProjectRepaymentTaskLog $projectRepaymentTaskLog)
    {
        /** @var \echeanciers $repaymentSchedule */
        $repaymentSchedule = $this->entityManagerSimulator->getRepository('echeanciers');

        $outstandingCapital = $repaymentSchedule->getOwedCapital(['id_loan' => $loan->getIdLoan()]);
        $borrowerWallet     = $this->entityManager->getRepository(Wallet::class)->getWalletByType($loan->getTranche()->getIdCompany()->getIdClientOwner(), WalletType::BORROWER);
        $lenderWallet       = $loan->getWallet();
        $operationSubType   = $this->entityManager->getRepository(OperationSubType::class)->findOneBy(['label' => OperationSubType::CAPITAL_REPAYMENT_EARLY]);

        $this->repaymentGeneric($borrowerWallet, $lenderWallet, $outstandingCapital, 0, $operationSubType, null, [$loan, $projectRepaymentTaskLog]);

        return $outstandingCapital;
    }

    /**
     * @param Projects $project
     * @param          $commission
     *
     * @throws \Exception
     */
    public function projectCommission(Projects $project, $commission)
    {
        $borrowerWallet    = $this->entityManager->getRepository(Wallet::class)->getWalletByType($project->getIdCompany()->getIdClientOwner(), WalletType::BORROWER);
        $unilendWalletType = $this->entityManager->getRepository(WalletType::class)->findOneBy(['label' => WalletType::UNILEND]);
        $unilendWallet     = $this->entityManager->getRepository(Wallet::class)->findOneBy(['idType' => $unilendWalletType]);
        $operationType     = $this->entityManager->getRepository(OperationType::class)->findOneBy(['label' => OperationType::BORROWER_COMMISSION]);
        $operationSubType  = $this->entityManager->getRepository(OperationSubType::class)->findOneBy(['label' => OperationSubType::BORROWER_COMMISSION_FUNDS]);

        $this->newOperation($commission, $operationType, $operationSubType, $borrowerWallet, $unilendWallet, $project);
    }

    /**
     * @param Wallet       $wallet
     * @param float        $amount
     * @param object|array $origins
     */
    public function borrowerRegularisation(Wallet $wallet, $amount, $origins = [])
    {
        $unilendWalletType = $this->entityManager->getRepository(WalletType::class)->findOneBy(['label' => WalletType::UNILEND_PROMOTIONAL_OPERATION]);
        $unilendWallet     = $this->entityManager->getRepository(Wallet::class)->findOneBy(['idType' => $unilendWalletType]);
        $operationType     = $this->entityManager->getRepository(OperationType::class)->findOneBy(['label' => OperationType::UNILEND_BORROWER_COMMERCIAL_GESTURE]);

        $this->newOperation($amount, $operationType, null, $unilendWallet, $wallet, $origins);
    }

    /**
     * @param Transfer $transfer
     * @param float    $amount
     *
     * @return bool
     */
    public function lenderTransfer(Transfer $transfer, $amount)
    {
        $debtor   = $this->entityManager->getRepository(Wallet::class)->getWalletByType($transfer->getClientOrigin(), WalletType::LENDER);
        $creditor = $this->entityManager->getRepository(Wallet::class)->getWalletByType($transfer->getClientReceiver(), WalletType::LENDER);
        if (null === $debtor || null === $creditor) {
            return false;
        }
        $operationType = $this->entityManager->getRepository(OperationType::class)->findOneBy(['label' => OperationType::LENDER_TRANSFER]);
        $this->newOperation($amount, $operationType, null, $debtor, $creditor, $transfer);

        return true;
    }

    /**
     * @param $amount
     */
    public function provisionUnilendPromotionalWallet($amount)
    {
        $unilendWalletType = $this->entityManager->getRepository(WalletType::class)->findOneBy(['label' => WalletType::UNILEND_PROMOTIONAL_OPERATION]);
        $unilendWallet     = $this->entityManager->getRepository(Wallet::class)->findOneBy(['idType' => $unilendWalletType]);

        $operationType = $this->entityManager->getRepository(OperationType::class)->findOneBy(['label' => OperationType::UNILEND_PROMOTIONAL_OPERATION_PROVISION]);
        $this->newOperation($amount, $operationType, null, null, $unilendWallet);
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
        if ($borrower->getIdType()->getLabel() !== WalletType::BORROWER) {
            return false;
        }
        if ($collector->getIdType()->getLabel() !== WalletType::DEBT_COLLECTOR) {
            return false;
        }
        $operationType = $this->entityManager->getRepository(OperationType::class)->findOneBy(['label' => OperationType::COLLECTION_COMMISSION_PROVISION]);
        $this->newOperation($commission, $operationType, null, $collector, $borrower, $reception);

        return true;
    }

    /**
     * @param Wallet       $debtor
     * @param Wallet       $collector
     * @param float        $fee
     * @param array|object $origins
     *
     * @throws \Exception
     */
    public function payDebtCollectionFee(Wallet $debtor, Wallet $collector, $fee, $origins = [])
    {
        switch ($debtor->getIdType()->getLabel()) {
            case WalletType::UNILEND:
                $operationTypeLabel = OperationType::COLLECTION_COMMISSION_UNILEND;
                break;
            case WalletType::BORROWER:
                $operationTypeLabel = OperationType::COLLECTION_COMMISSION_BORROWER;
                break;
            case WalletType::LENDER:
                $operationTypeLabel = OperationType::COLLECTION_COMMISSION_LENDER;
                break;
            default:
                throw new \Exception('The wallet type ' . $debtor->getIdType()->getLabel() . 'is not supported');
        }

        $operationType = $this->entityManager->getRepository(OperationType::class)->findOneBy(['label' => $operationTypeLabel]);
        $this->newOperation($fee, $operationType, null, $debtor, $collector, $origins);
    }

    /**
     * repay to Unilend the project charge already paid in advance.
     *
     * @param Wallet       $borrower
     * @param float        $charge
     * @param array|object $origins
     */
    public function repayProjectChargeByBorrower(Wallet $borrower, $charge, $origins = [])
    {
        $operationType     = $this->entityManager->getRepository(OperationType::class)->findOneBy(['label' => OperationType::BORROWER_PROJECT_CHARGE_REPAYMENT]);
        $unilendWalletType = $this->entityManager->getRepository(WalletType::class)->findOneBy(['label' => WalletType::UNILEND]);
        $unilendWallet     = $this->entityManager->getRepository(Wallet::class)->findOneBy(['idType' => $unilendWalletType]);
        $this->newOperation($charge, $operationType, null, $borrower, $unilendWallet, $origins);
    }

    /**
     * Simple version which does not support interest repayment.
     *
     * @param Wallet                  $lender
     * @param Projects                $project
     * @param                         $amount
     * @param ProjectRepaymentTaskLog $projectRepaymentTaskLog
     *
     * @return bool
     */
    public function repaymentCollection(Wallet $lender, Projects $project, $amount, ProjectRepaymentTaskLog $projectRepaymentTaskLog)
    {
        $borrower = $this->entityManager->getRepository(Wallet::class)->getWalletByType($project->getIdCompany()->getIdClientOwner(), WalletType::BORROWER);
        if (null === $borrower) {
            return false;
        }
        $operationSubType = $this->entityManager->getRepository(OperationSubType::class)->findOneBy(['label' => OperationSubType::CAPITAL_REPAYMENT_DEBT_COLLECTION]);

        return $this->repaymentGeneric($borrower, $lender, $amount, 0, $operationSubType, null, [$project, $projectRepaymentTaskLog]);
    }

    /**
     * @param float                    $capital
     * @param float                    $interest
     * @param CloseOutNettingRepayment $closeOutNettingRepayment
     * @param ProjectRepaymentTaskLog  $projectRepaymentTaskLog
     *
     * @return bool
     */
    public function closeOutNettingRepayment($capital, $interest, CloseOutNettingRepayment $closeOutNettingRepayment, ProjectRepaymentTaskLog $projectRepaymentTaskLog)
    {
        $project         = $projectRepaymentTaskLog->getIdTask()->getIdProject();
        $borrower        = $this->entityManager->getRepository(Wallet::class)->getWalletByType($project->getIdCompany()->getIdClientOwner(), WalletType::BORROWER);
        $lender          = $closeOutNettingRepayment->getIdLoan()->getWallet();
        $capitalSubType  = $this->entityManager->getRepository(OperationSubType::class)->findOneBy(['label' => OperationSubType::CAPITAL_REPAYMENT_DEBT_COLLECTION]);
        $interestSubType = $this->entityManager->getRepository(OperationSubType::class)->findOneBy(['label' => OperationSubType::GROSS_INTEREST_REPAYMENT_DEBT_COLLECTION]);

        return $this->repaymentGeneric($borrower, $lender, $capital, $interest, $capitalSubType, $interestSubType, [$project, $closeOutNettingRepayment->getIdLoan(), $projectRepaymentTaskLog]);
    }

    /**
     * @param Wallet           $borrower
     * @param Wallet           $lender
     * @param                  $capital
     * @param                  $interest
     * @param OperationSubType $capitalOperationSubType
     * @param OperationSubType $interestOperationSubType
     * @param array|object     $origins
     *
     * @return bool
     */
    private function repaymentGeneric(
        Wallet $borrower,
        Wallet $lender,
        $capital,
        $interest,
        OperationSubType $capitalOperationSubType = null,
        OperationSubType $interestOperationSubType = null,
        $origins = []
    )
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
            $operationType = $this->entityManager->getRepository(OperationType::class)->findOneBy(['label' => OperationType::CAPITAL_REPAYMENT]);
            $this->newOperation($capital, $operationType, $capitalOperationSubType, $borrower, $lender, $origins);
        }

        if ($interest > 0) {
            $operationType = $this->entityManager->getRepository(OperationType::class)->findOneBy(['label' => OperationType::GROSS_INTEREST_REPAYMENT]);
            $this->newOperation($interest, $operationType, $interestOperationSubType, $borrower, $lender, $origins);
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
                $this->tax($loan, $interest, $origins);
            }
        }

        return true;
    }

    /**
     * @param Wallet $wallet
     * @param float  $amount
     * @param array  $origins
     *
     * @return bool
     */
    public function borrowerCommercialGesture(Wallet $wallet, $amount, $origins = [])
    {
        $unilendPromotionWalletType = $this->entityManager->getRepository(WalletType::class)->findOneBy(['label' => WalletType::UNILEND_PROMOTIONAL_OPERATION]);
        $unilendWallet              = $this->entityManager->getRepository(Wallet::class)->findOneBy(['idType' => $unilendPromotionWalletType]);
        $operationType              = $this->entityManager->getRepository(OperationType::class)->findOneBy(['label' => OperationType::UNILEND_BORROWER_COMMERCIAL_GESTURE]);

        return $this->newOperation($amount, $operationType, null, $unilendWallet, $wallet, $origins);
    }

    /**
     * @param Operation    $operation
     * @param float|null   $amount
     * @param array|object $origins
     *
     * @return bool
     * @throws \Exception
     */
    public function regularize(Operation $operation, ?float $amount = null, $origins = [])
    {
        switch ($operation->getType()->getLabel()) {
            case OperationType::BORROWER_COMMISSION:
                $operationTypeLabel = OperationType::BORROWER_COMMISSION_REGULARIZATION;
                break;
            case OperationType::CAPITAL_REPAYMENT:
                $operationTypeLabel = OperationType::CAPITAL_REPAYMENT_REGULARIZATION;
                break;
            case OperationType::GROSS_INTEREST_REPAYMENT:
                $operationTypeLabel = OperationType::GROSS_INTEREST_REPAYMENT_REGULARIZATION;
                break;
            case OperationType::COLLECTION_COMMISSION_LENDER:
                $operationTypeLabel = OperationType::COLLECTION_COMMISSION_LENDER_REGULARIZATION;
                break;
            case OperationType::COLLECTION_COMMISSION_BORROWER:
                $operationTypeLabel = OperationType::COLLECTION_COMMISSION_BORROWER_REGULARIZATION;
                break;
            case OperationType::TAX_FR_CONTRIBUTIONS_ADDITIONNELLES:
                $operationTypeLabel = OperationType::TAX_FR_CONTRIBUTIONS_ADDITIONNELLES_REGULARIZATION;
                break;
            case OperationType::TAX_FR_CRDS:
                $operationTypeLabel = OperationType::TAX_FR_CRDS_REGULARIZATION;
                break;
            case OperationType::TAX_FR_CSG:
                $operationTypeLabel = OperationType::TAX_FR_CSG_REGULARIZATION;
                break;
            case OperationType::TAX_FR_PRELEVEMENTS_DE_SOLIDARITE:
                $operationTypeLabel = OperationType::TAX_FR_PRELEVEMENTS_DE_SOLIDARITE_REGULARIZATION;
                break;
            case OperationType::TAX_FR_PRELEVEMENTS_OBLIGATOIRES:
                $operationTypeLabel = OperationType::TAX_FR_PRELEVEMENTS_OBLIGATOIRES_REGULARIZATION;
                break;
            case OperationType::TAX_FR_PRELEVEMENTS_SOCIAUX:
                $operationTypeLabel = OperationType::TAX_FR_PRELEVEMENTS_SOCIAUX_REGULARIZATION;
                break;
            case OperationType::TAX_FR_RETENUES_A_LA_SOURCE:
                $operationTypeLabel = OperationType::TAX_FR_RETENUES_A_LA_SOURCE_REGULARIZATION;
                break;
            default:
                throw new \Exception('The operation type ' . $operation->getType()->getLabel() . ' is not supported');
        }

        $operationSubTypeLabel = null;
        if ($operation->getSubType()) {
            switch ($operation->getSubType()->getLabel()) {
                case OperationSubType::CAPITAL_REPAYMENT_EARLY:
                    $operationSubTypeLabel = OperationSubType::CAPITAL_REPAYMENT_EARLY_REGULARIZATION;
                    break;
                case OperationSubType::CAPITAL_REPAYMENT_DEBT_COLLECTION:
                    $operationSubTypeLabel = OperationSubType::CAPITAL_REPAYMENT_DEBT_COLLECTION_REGULARIZATION;
                    break;
                case OperationSubType::GROSS_INTEREST_REPAYMENT_DEBT_COLLECTION:
                    $operationSubTypeLabel = OperationSubType::GROSS_INTEREST_REPAYMENT_DEBT_COLLECTION_REGULARIZATION;
                    break;
                case OperationSubType::BORROWER_COMMISSION_FUNDS:
                    $operationSubTypeLabel = OperationSubType::BORROWER_COMMISSION_FUNDS_REGULARIZATION;
                    break;
                case OperationSubType::BORROWER_COMMISSION_REPAYMENT:
                    $operationSubTypeLabel = OperationSubType::BORROWER_COMMISSION_REPAYMENT_REGULARIZATION;
                    break;
                case OperationSubType::TAX_FR_RETENUES_A_LA_SOURCE_PERSON:
                    $operationSubTypeLabel = OperationSubType::TAX_FR_RETENUES_A_LA_SOURCE_PERSON_REGULARIZATION;
                    break;
                default:
                    throw new \Exception('The operation type ' . $operation->getSubType()->getLabel() . ' is not supported');
            }
        }

        if (null === $amount) {
            $amount = $operation->getAmount();
        }

        // Switch the creditor and debtor as it is a regularization.
        $creditor         = $operation->getWalletDebtor();
        $debtor           = $operation->getWalletCreditor();
        $operationType    = $this->entityManager->getRepository(OperationType::class)->findOneBy(['label' => $operationTypeLabel]);
        $operationSubType = $this->entityManager->getRepository(OperationSubType::class)->findOneBy(['label' => $operationSubTypeLabel]);

        if (false === is_array($origins)) {
            $origins = [$origins];
        }
        // $operation must be the first element in the list.
        array_unshift($origins, $operation);

        return $this->newOperation($amount, $operationType, $operationSubType, $debtor, $creditor, $origins);
    }

    /**
     * @param Wallet      $wallet
     * @param Sponsorship $sponsorship
     */
    public function newSponsorReward(Wallet $wallet, Sponsorship $sponsorship)
    {
        $amount            = $sponsorship->getIdCampaign()->getAmountSponsor();
        $operationType     = $this->entityManager->getRepository(OperationType::class)->findOneBy(['label' => OperationType::UNILEND_PROMOTIONAL_OPERATION]);
        $operationSubType  = $this->entityManager->getRepository(OperationSubType::class)
            ->findOneBy(['label' => OperationSubType::UNILEND_PROMOTIONAL_OPERATION_SPONSORSHIP_REWARD_SPONSOR]);
        $unilendWalletType = $this->entityManager->getRepository(WalletType::class)->findOneBy(['label' => WalletType::UNILEND_PROMOTIONAL_OPERATION]);
        $unilendWallet     = $this->entityManager->getRepository(Wallet::class)->findOneBy(['idType' => $unilendWalletType]);
        $this->newOperation($amount, $operationType, $operationSubType, $unilendWallet, $wallet, $sponsorship);
    }

    /**
     * @param Wallet      $wallet
     * @param Sponsorship $sponsorship
     */
    public function cancelSponsorReward(Wallet $wallet, Sponsorship $sponsorship)
    {
        $amount            = $sponsorship->getIdCampaign()->getAmountSponsor();
        $operationType     = $this->entityManager->getRepository(OperationType::class)->findOneBy(['label' => OperationType::UNILEND_PROMOTIONAL_OPERATION_CANCEL]);
        $operationSubType  = $this->entityManager->getRepository(OperationSubType::class)
            ->findOneBy(['label' => OperationSubType::UNILEND_PROMOTIONAL_OPERATION_CANCEL_SPONSORSHIP_REWARD_SPONSOR]);
        $unilendWalletType = $this->entityManager->getRepository(WalletType::class)->findOneBy(['label' => WalletType::UNILEND_PROMOTIONAL_OPERATION]);
        $unilendWallet     = $this->entityManager->getRepository(Wallet::class)->findOneBy(['idType' => $unilendWalletType]);
        $this->newOperation($amount, $operationType, $operationSubType, $wallet, $unilendWallet, $sponsorship);
    }

    /**
     * @param Wallet      $wallet
     * @param Sponsorship $sponsorship
     */
    public function newSponseeReward(Wallet $wallet, Sponsorship $sponsorship)
    {
        $amount            = $sponsorship->getIdCampaign()->getAmountSponsee();
        $operationType     = $this->entityManager->getRepository(OperationType::class)->findOneBy(['label' => OperationType::UNILEND_PROMOTIONAL_OPERATION]);
        $operationSubType  = $this->entityManager->getRepository(OperationSubType::class)
            ->findOneBy(['label' => OperationSubType::UNILEND_PROMOTIONAL_OPERATION_SPONSORSHIP_REWARD_SPONSEE]);
        $unilendWalletType = $this->entityManager->getRepository(WalletType::class)->findOneBy(['label' => WalletType::UNILEND_PROMOTIONAL_OPERATION]);
        $unilendWallet     = $this->entityManager->getRepository(Wallet::class)->findOneBy(['idType' => $unilendWalletType]);
        $this->newOperation($amount, $operationType, $operationSubType, $unilendWallet, $wallet, $sponsorship);
    }

    /**
     * @param Wallet      $wallet
     * @param Sponsorship $sponsorship
     */
    public function cancelSponseeReward(Wallet $wallet, Sponsorship $sponsorship)
    {
        $amount            = $sponsorship->getIdCampaign()->getAmountSponsee();
        $operationType     = $this->entityManager->getRepository(OperationType::class)->findOneBy(['label' => OperationType::UNILEND_PROMOTIONAL_OPERATION_CANCEL]);
        $operationSubType  = $this->entityManager->getRepository(OperationSubType::class)
            ->findOneBy(['label' => OperationSubType::UNILEND_PROMOTIONAL_OPERATION_CANCEL_SPONSORSHIP_REWARD_SPONSEE]);
        $unilendWalletType = $this->entityManager->getRepository(WalletType::class)->findOneBy(['label' => WalletType::UNILEND_PROMOTIONAL_OPERATION]);
        $unilendWallet     = $this->entityManager->getRepository(Wallet::class)->findOneBy(['idType' => $unilendWalletType]);
        $this->newOperation($amount, $operationType, $operationSubType, $wallet, $unilendWallet, $sponsorship);
    }
}
