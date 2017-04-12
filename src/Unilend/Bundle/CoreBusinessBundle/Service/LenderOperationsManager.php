<?php


namespace Unilend\Bundle\CoreBusinessBundle\Service;


use function bcdiv;
use function bcsub;
use Doctrine\ORM\EntityManager;
use function in_array;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletBalanceHistory;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use function var_dump;

class LenderOperationsManager
{
    const OP_REPAYMENT          = 'repayment';
    const OP_RECOVERY_REPAYMENT = 'recovery-repayment';
    const OP_EARLY_REPAYMENT    = 'early-repayment';
    const OP_BID                = 'bid';
    const OP_REFUSED_BID        = 'refused-bid';
    const OP_AUTOBID            = 'autobid';
    const OP_REFUSED_AUTOBID    = 'refused-autobid';
    const OP_REFUSED_LOAN       = 'refused-loan';
    const PROVISION_TYPES = [
        OperationType::LENDER_PROVISION,
        OperationType::LENDER_TRANSFER,
    ];

    const WITHDRAW_TYPES = [
        OperationType::LENDER_PROVISION_CANCEL,
        OperationType::LENDER_WITHDRAW
    ];

    const OFFER_TYPES = [
        self::OP_BID,
        self::OP_REFUSED_BID,
        self::OP_AUTOBID,
        self::OP_REFUSED_AUTOBID,
        OperationType::LENDER_LOAN,
        self::OP_REFUSED_LOAN
    ];

    const REPAYMENT_TYPES = [
        self::OP_REPAYMENT,
        self::OP_EARLY_REPAYMENT,
        self::OP_RECOVERY_REPAYMENT
    ];

    const ALL_TYPES = [
            OperationType::LENDER_PROVISION,
            OperationType::LENDER_PROVISION_CANCEL,
            OperationType::LENDER_TRANSFER,
            OperationType::LENDER_WITHDRAW,
            OperationType::UNILEND_PROMOTIONAL_OPERATION,
            OperationType::UNILEND_PROMOTIONAL_OPERATION_CANCEL,
            OperationType::UNILEND_LENDER_REGULARIZATION,
            self::OP_REPAYMENT,
            self::OP_EARLY_REPAYMENT,
            self::OP_RECOVERY_REPAYMENT,
            self::OP_BID,
            self::OP_REFUSED_BID,
            self::OP_AUTOBID,
            self::OP_REFUSED_AUTOBID,
            OperationType::LENDER_LOAN,
            self::OP_REFUSED_LOAN
    ];

    const FILTER_ALL                = 1;
    const FILTER_PROVISION_WITHDRAW = 2;
    const FILTER_PROVISION          = 3;
    const FILTER_WITHDRAW           = 4;
    const FILTER_OFFERS             = 5;
    const FILTER_REPAYMENT          = 6;

    /** @var  EntityManager */
    private $entityManager;
    /** @var  TranslatorInterface */
    private $translator;

    public function __construct(EntityManager $entityManager, Translator $translator)
    {
        $this->entityManager = $entityManager;
        $this->translator    = $translator;
    }

    public function getLenderOperations(Wallet $wallet, \DateTime $start, \DateTime $end, $idProject = null, array $operations = null)
    {
        if (WalletType::LENDER !== $wallet->getIdType()->getLabel()) {
            //TODO throw exception our return false
        }

        $walletBalanceHistoryRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:WalletBalanceHistory');
        $operationRepository            = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Operation');
        $taxExemptionRepository         = $this->entityManager->getRepository('UnilendCoreBusinessBundle:LenderTaxExemption');
        $projectRepository              = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects');
        $walletHistory                  = $walletBalanceHistoryRepository->getLenderOperationHistory($wallet, $start, $end);
        $lenderOperations               = [];

        foreach ($walletHistory as $index => $historyLine) {
            if (
                in_array(self::OP_REPAYMENT, $operations)
                && false === empty($historyLine['id_repayment_schedule'])
            ) {
                $repaymentDetail                  = $operationRepository->getDetailByRepaymentScheduleId($historyLine['id_repayment_schedule']);
                $historyLine['label']             = 'repayment';
                $historyLine['amount']            = bcsub(bcadd($repaymentDetail['capital'], $repaymentDetail['interest'], 2), $repaymentDetail['taxes'], 2);
                $historyLine['available_balance'] = $repaymentDetail['available_balance'];
                $historyLine['detail'] = [
                    'label' => $this->translator->trans('lender-operations_operations-table-repayment-collapse-details'),
                    'items' => [
                        [
                            'label' => $this->translator->trans('lender-operations_operations-table-repaid-capital-amount-collapse-details'),
                            'value' => $repaymentDetail['capital']
                        ],
                        [
                            'label' => $this->translator->trans('lender-operations_operations-table-repaid-interests-amount-collapse-details'),
                            'value' => $repaymentDetail['interest']
                        ]
                    ]
                ];

                if (null === $repaymentDetail['taxes']) {
                    unset($repaymentDetail['taxes']);
                } else {
                    $taxLabel = $this->translator->trans('lender-operations_tax-and-social-deductions-label');
                    if ($wallet->getIdClient()->getType() == Clients::TYPE_PERSON || $wallet->getIdClient()->getType() == Clients::TYPE_PERSON_FOREIGNER) {
                        if ($taxExemptionRepository->isLenderExemptedInYear($wallet, substr($historyLine['date'], 0, 4))) {
                            $taxLabel  = $this->translator->trans('lender-operations_social-deductions-label');
                        }
                    } else {
                        $taxLabel  = $this->translator->trans('preteur-operations-vos-operations_retenues-a-la-source');
                    }
                    $historyLine['detail']['items'][] = [
                        'label' => $taxLabel,
                        'value' => -$repaymentDetail['taxes']
                    ];
                }
            }

            if (
                (in_array(self::OP_EARLY_REPAYMENT, $operations)  || in_array(self::OP_RECOVERY_REPAYMENT, $operations))
                && OperationType::CAPITAL_REPAYMENT === $historyLine['label']
                && empty($historyLine['id_repayment_schedule'])
            ) {
                $project = $projectRepository->find($historyLine['id_project']);

                if (\projects_status::REMBOURSEMENT_ANTICIPE === $project->getStatus()) {
                    $historyLine['label'] = 'early-repayment';
                } else {
                    $recoveryDetail = $operationRepository->getLenderRecoveryRepaymentDetailByDate($wallet, $historyLine['operationDate']);
                    $historyLine['label']             = 'recovery-repayment';
                    $historyLine['amount']            = bcsub($recoveryDetail['capital'], $recoveryDetail['commission'], 2);
                    $historyLine['available_balance'] = $recoveryDetail['available_balance'];
                    $historyLine['detail'] = [
                        'label' => $this->translator->trans('lender-operations_operations-table-repayment-collapse-details'),
                        'items' => [
                            [
                                'label' => $this->translator->trans('lender-operations_operations-table-repaid-capital-amount-collapse-details'),
                                'value' => $recoveryDetail['capital']
                            ],
                            [
                                'label' => $this->translator->trans('lender-operations_operations-table-recovery-commission'),
                                'value' => -$recoveryDetail['commission']
                            ]
                        ]
                    ];
                }
            }

            if (OperationType::COLLECTION_COMMISSION_LENDER === $historyLine['label']) {
                continue;
            }
             if (self::OP_REFUSED_BID === $historyLine['label']) {
                if (empty($historyLine['amount']) && empty($historyLine['id_bid'] && empty($historyLine['id_loan']))) {
                    /** @var WalletBalanceHistory $walletBalanceHistory */
                    $walletBalanceHistory  = $walletBalanceHistoryRepository->getPreviousLIneForWallet($wallet, $historyLine['id']);
                    $amount                = bcsub($walletBalanceHistory->getAvailableBalance(), $historyLine['amount'], 2);
                    $historyLine['amount'] = $amount;
                }
                 $historyLine['amount'] = abs($historyLine['amount']);

                if (false === empty($historyLine['id_loan'])) {
                    $historyLine['label'] = self::OP_REFUSED_LOAN;
                    $loan = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Loans')->find($historyLine['id_loan']);
                    $historyLine['amount'] = bcdiv($loan->getAmount(), 100, 2);
                }
             }
            $lenderOperations[] = $historyLine;
        }


        if (null !== $idProject || null !== $operations){
            return $this->filterLenderOperations($lenderOperations, $idProject, $operations);
        }

        return $lenderOperations;
    }

    /**
     * @param int $filter
     *
     * @return array
     */
    public function getOperationsAccordingToFilter($filter)
    {
        switch($filter) {
            case self::FILTER_ALL:
                return self::ALL_TYPES;
            case self:: FILTER_PROVISION_WITHDRAW;
                return array_merge(self::PROVISION_TYPES, self::WITHDRAW_TYPES);
            case self::FILTER_PROVISION:
                return self::PROVISION_TYPES;
            case self::FILTER_PROVISION_WITHDRAW:
                return self::PROVISION_TYPES;
            case self::FILTER_OFFERS:
                return self::OFFER_TYPES;
            case self::FILTER_REPAYMENT:
                return self::REPAYMENT_TYPES;
            default:
                return [];
        }
    }

    /**
     * @param array $lenderOperationsToFilter
     * @param int   $idProject
     * @param array $operations
     *
     * @return array
     */
    private function filterLenderOperations(array $lenderOperationsToFilter, $idProject, array $operations)
    {
        $filteredLenderOperations = [];
        foreach ($lenderOperationsToFilter as $operation) {
            if (
                in_array($operation['label'], $operations)
                && (null === $idProject || $operation['id_project'] == $idProject)
            ) {
                $filteredLenderOperations[] = $operation;
            }

        }

        return $filteredLenderOperations;
    }

}
