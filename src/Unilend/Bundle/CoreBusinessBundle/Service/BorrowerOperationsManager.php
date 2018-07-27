<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\Translator;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    Clients, OperationSubType, OperationType, Receptions, TaxType, Wallet, WalletType
};

class BorrowerOperationsManager
{
    const OP_PROJECT_CHARGE_REPAYMENT       = 'project-charge-repayment';
    const OP_LENDER_MONTHLY_REPAYMENT       = 'lender-monthly-repayment';
    const OP_LENDER_EARLY_REPAYMENT         = 'lender-early-repayment';
    const OP_LENDER_RECOVERY_REPAYMENT      = 'lender-recovery-repayment';
    const OP_BORROWER_DIRECT_DEBIT          = 'monthly-payment-direct-debit';
    const OP_WIRE_TRANSFER_IN               = 'wire-transfer-in';

    /** @var EntityManager */
    private $entityManager;
    /** @var Translator */
    private $translator;
    /** @var LoggerInterface */
    private $logger;

    /**
     * BorrowerOperationsManager constructor.
     *
     * @param EntityManager   $entityManager
     * @param Translator      $translator
     * @param LoggerInterface $logger
     */
    public function __construct(EntityManager $entityManager, Translator $translator, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->translator    = $translator;
        $this->logger        = $logger;
    }

    /**
     * @param Wallet    $wallet
     * @param \DateTime $start
     * @param \DateTime $end
     * @param array     $projectsIds
     * @param string    $borrowerOperationType
     *
     * @return array
     * @throws \Exception
     */
    public function getBorrowerOperations(Wallet $wallet, \DateTime $start, \DateTime $end, array $projectsIds = [], $borrowerOperationType = 'all')
    {
        $walletBalanceHistoryRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:WalletBalanceHistory');
        $operationRepository            = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Operation');
        $walletHistory                  = $walletBalanceHistoryRepository->getBorrowerWalletOperations($wallet, $start, $end, $projectsIds);
        $borrowerOperations             = [];

        $vatTax = $this->entityManager->getRepository('UnilendCoreBusinessBundle:TaxType')->find(TaxType::TYPE_VAT);
        if (null === $vatTax) {
            throw new \Exception('The VAT rate is not defined.');
        }
        $vatTaxRate = round(bcdiv($vatTax->getRate(), 100, 5), 4);

        foreach ($walletHistory as $index => $operation) {
            if (in_array($operation['label'], [OperationType::CAPITAL_REPAYMENT, OperationType::GROSS_INTEREST_REPAYMENT])) {
                $operation['label'] = self::OP_LENDER_MONTHLY_REPAYMENT;
            }

            if (OperationSubType::CAPITAL_REPAYMENT_EARLY === $operation['label']) {
                $operation['label'] = self::OP_LENDER_EARLY_REPAYMENT;
            }

            if (in_array($operation['label'], [OperationSubType::BORROWER_COMMISSION_FUNDS, OperationSubType::BORROWER_COMMISSION_REPAYMENT])) {
                $operation['netCommission'] = round(bcdiv($operation['amount'], bcadd(1, $vatTaxRate, 5), 4), 2);
                $operation['vat']           = round(bcsub($operation['amount'], $operation['netCommission'], 4), 2);
            }

            if (OperationType::BORROWER_PROVISION === $operation['label']) {
                $operationEntity = $operationRepository->find($operation['id']);

                if (null !== $operationEntity->getWireTransferIn()) {
                    if (Receptions::WIRE_TRANSFER_STATUS_RECEIVED === $operationEntity->getWireTransferIn()->getStatusVirement()) {
                        $operation['label'] = self::OP_WIRE_TRANSFER_IN;
                    } elseif (Receptions::DIRECT_DEBIT_STATUS_SENT === $operationEntity->getWireTransferIn()->getStatusPrelevement()) {
                        $operation['label'] = self::OP_BORROWER_DIRECT_DEBIT;
                    } else {
                        $this->logger->error('Unable to define the type of reception for reception ' . $operationEntity->getWireTransferIn()->getIdReception(), [
                            'class'        => __CLASS__,
                            'function'     => __FUNCTION__,
                            'id_reception' => $operationEntity->getWireTransferIn()->getIdReception()
                        ]);
                    }
                }
            }

            if (OperationType::BORROWER_WITHDRAW === $operation['label']) {
                $operationEntity = $operationRepository->find($operation['id']);
                if (
                    null !== $operationEntity->getWireTransferOut()->getBankAccount()
                    && $operationEntity->getWireTransferOut()->getBankAccount()->getIdClient() !== $operationEntity->getWalletDebtor()->getIdClient()
                ) {
                    $thirdPartyClient                 = $operationEntity->getWireTransferOut()->getBankAccount()->getIdClient();
                    $thirdPartyCompany                = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findOneBy(['idClientOwner' => $thirdPartyClient->getIdClient()]);
                    $operation['third_party_company'] = $thirdPartyCompany->getName();
                }
            }
            if ($borrowerOperationType === 'all' || $borrowerOperationType === $operation['label']) {
                $borrowerOperations[] = $operation;
            }
        }

        return $borrowerOperations;
    }
}
