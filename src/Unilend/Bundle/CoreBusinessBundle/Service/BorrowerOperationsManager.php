<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\Translator;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationSubType;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Receptions;
use Unilend\Bundle\CoreBusinessBundle\Entity\TaxType;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;

class BorrowerOperationsManager
{
    const OP_EARLY_PAYMENT                  = 'early-payment';
    const OP_MONTHLY_PAYMENT                = 'monthly-payment';
    const OP_RECOVERY_PAYMENT               = 'recovery-payment';
    const OP_MONTHLY_PAYMENT_REGULARIZATION = 'monthly-payment-regularization';
    const OP_PROJECT_CHARGE_REPAYMENT       = 'project-charge-repayment';
    const OP_LENDER_MONTHLY_REPAYMENT       = 'lender-monthly-repayment';
    const OP_LENDER_EARLY_REPAYMENT         = 'lender-early-repayment';
    const OP_LENDER_RECOVERY_REPAYMENT      = 'lender-recovery-repayment';

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
     * @param \clients  $client
     * @param \DateTime $start
     * @param \DateTime $end
     * @param array     $projectsIds
     * @param string    $borrowerOperationType
     *
     * @return array
     * @throws \Exception
     */
    public function getBorrowerOperations(\clients $client, \DateTime $start, \DateTime $end, array $projectsIds = [], $borrowerOperationType = 'all')
    {
        $wallet                         = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client->id_client, WalletType::BORROWER);
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

                switch ($operationEntity->getWireTransferIn()->getTypeRemb()) {
                    case Receptions::REPAYMENT_TYPE_EARLY:
                        $operation['label'] = self::OP_EARLY_PAYMENT;
                        break;
                    case Receptions::REPAYMENT_TYPE_NORMAL:
                        $operation['label'] = self::OP_MONTHLY_PAYMENT;
                        break;
                    case Receptions::REPAYMENT_TYPE_RECOVERY:
                        $operation['label'] = self::OP_RECOVERY_PAYMENT;
                        break;
                    case Receptions::REPAYMENT_TYPE_REGULARISATION:
                        $operation['label'] = self::OP_MONTHLY_PAYMENT_REGULARIZATION;
                        break;
                    default:
                        $this->logger->warning(
                            'Unknown "receptions.typeRemb" value (' . $operationEntity->getWireTransferIn()->getTypeRemb() . ')',
                            ['id_reception' => $operationEntity->getWireTransferIn()->getIdReception(), 'method' => __METHOD__]
                        );
                        break;
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
