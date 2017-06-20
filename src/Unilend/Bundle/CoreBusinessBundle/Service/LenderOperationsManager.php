<?php


namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use PHPExcel_Shared_Date;
use PHPExcel_Style_Border;
use PHPExcel_Style_Color;
use PHPExcel_Style_Conditional;
use PHPExcel_Style_NumberFormat;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Operation;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationSubType;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletBalanceHistory;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;

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
        self::OP_RECOVERY_REPAYMENT,
        OperationType::COLLECTION_COMMISSION_LENDER
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
        OperationType::COLLECTION_COMMISSION_LENDER,
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

    /**
     * LenderOperationsManager constructor.
     *
     * @param EntityManager $entityManager
     * @param Translator    $translator
     */
    public function __construct(EntityManager $entityManager, Translator $translator)
    {
        $this->entityManager = $entityManager;
        $this->translator    = $translator;
    }

    /**
     * @param Wallet     $wallet
     * @param \DateTime  $start
     * @param \DateTime  $end
     * @param int| null  $idProject
     * @param array|null $operations
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getLenderOperations(Wallet $wallet, \DateTime $start, \DateTime $end, $idProject = null, array $operations = null)
    {
        if (WalletType::LENDER !== $wallet->getIdType()->getLabel()) {
            throw new \Exception('Wallet is not a Lender wallet');
        }

        $walletBalanceHistoryRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:WalletBalanceHistory');
        $operationRepository            = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Operation');
        $taxExemptionRepository         = $this->entityManager->getRepository('UnilendCoreBusinessBundle:LenderTaxExemption');
        $walletHistory                  = $walletBalanceHistoryRepository->getLenderOperationHistory($wallet, $start, $end);
        $lenderOperations               = [];

        foreach ($walletHistory as $index => $historyLine) {
            if (
                in_array(self::OP_REPAYMENT, $operations)
                && false === empty($historyLine['id_repayment_schedule'])
            ) {
                $repaymentDetail                  = $operationRepository->getDetailByRepaymentScheduleId($historyLine['id_repayment_schedule']);
                $historyLine['label']             = self::OP_REPAYMENT;
                $historyLine['amount']            = bcsub(bcadd($repaymentDetail['capital'], $repaymentDetail['interest'], 2), $repaymentDetail['taxes'], 2);
                $historyLine['available_balance'] = $repaymentDetail['available_balance'];
                $historyLine['detail']            = [
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

                if ($repaymentDetail['taxes']) {
                    $taxLabel = $this->translator->trans('lender-operations_tax-and-social-deductions-label');
                    if ($wallet->getIdClient()->isNaturalPerson()) {
                        if ($taxExemptionRepository->isLenderExemptedInYear($wallet, substr($historyLine['date'], 0, 4))) {
                            $taxLabel = $this->translator->trans('lender-operations_social-deductions-label');
                        }
                    } else {
                        $taxLabel = $this->translator->trans('preteur-operations-vos-operations_retenues-a-la-source');
                    }
                    $historyLine['detail']['items'][] = [
                        'label' => $taxLabel,
                        'value' => -$repaymentDetail['taxes']
                    ];
                }
            }

            if (OperationSubType::CAPITAL_REPAYMENT_EARLY === $historyLine['sub_type_label']) {
                $historyLine['label'] = self::OP_EARLY_REPAYMENT;
            }

            if (OperationSubType::CAPITAL_REPAYMENT_DEBT_COLLECTION === $historyLine['sub_type_label']) {
                $historyLine['label'] = self::OP_RECOVERY_REPAYMENT;
            }

            if (self::OP_REFUSED_BID === $historyLine['label']) {
                if (empty($historyLine['amount']) && empty($historyLine['id_bid'] && empty($historyLine['id_loan']))) {
                    /** @var WalletBalanceHistory $walletBalanceHistory */
                    $walletBalanceHistory  = $walletBalanceHistoryRepository->getPreviousLineForWallet($wallet, $historyLine['id']);
                    $amount                = bcsub($walletBalanceHistory->getAvailableBalance(), $historyLine['amount'], 2);
                    $historyLine['amount'] = $amount;
                }
                $historyLine['amount'] = abs($historyLine['amount']);

                if (false === empty($historyLine['id_loan'])) {
                    $historyLine['label']  = self::OP_REFUSED_LOAN;
                    $loan                  = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Loans')->find($historyLine['id_loan']);
                    $historyLine['amount'] = bcdiv($loan->getAmount(), 100, 2);
                }
            }
            $lenderOperations[] = $historyLine;
        }

        if (null !== $idProject || null !== $operations) {
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
        switch ($filter) {
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

    /**
     * @param Wallet    $wallet
     * @param \DateTime $start
     * @param \DateTime $end
     * @param int       $idProject
     * @param array     $operationTypes
     *
     * @return \PHPExcel
     */
    public function getOperationsExcelFile(Wallet $wallet, $start, $end, $idProject, $operationTypes)
    {
        $operationRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Operation');
        $lenderOperations    = $this->getLenderOperations($wallet, $start, $end, $idProject, $operationTypes);
        $taxColumns          = [];
        $hasLoanRow          = false;

        $style = [
            'borders' => [
                'allborders' => [
                    'style' => PHPExcel_Style_Border::BORDER_THIN,
                    'color' => ['argb' => PHPExcel_Style_Color::COLOR_BLACK]
                ]
            ]
        ];

        /** @var \PHPExcel $document */
        $document    = new \PHPExcel();
        $activeSheet = $document->setActiveSheetIndex(0);
        $row         = 1;

        $activeSheet->setCellValueByColumnAndRow(0, $row, $this->translator->trans('lender-operations_operations-csv-operation-column'));
        $activeSheet->setCellValueByColumnAndRow(1, $row, $this->translator->trans('lender-operations_operations-csv-contract-column'));
        $activeSheet->setCellValueByColumnAndRow(2, $row, $this->translator->trans('lender-operations_operations-csv-project-id-column'));
        $activeSheet->setCellValueByColumnAndRow(3, $row, $this->translator->trans('lender-operations_operations-csv-project-label-column'));
        $activeSheet->setCellValueByColumnAndRow(4, $row, $this->translator->trans('lender-operations_operations-csv-operation-date-column'));
        $activeSheet->setCellValueByColumnAndRow(5, $row, $this->translator->trans('lender-operations_operations-csv-operation-amount-column'));
        $activeSheet->setCellValueByColumnAndRow(6, $row, $this->translator->trans('lender-operations_operations-csv-repaid-capital-amount-column'));
        $activeSheet->setCellValueByColumnAndRow(7, $row, $this->translator->trans('lender-operations_operations-csv-perceived-interests-amount-column'));
        $activeSheet->setCellValueByColumnAndRow(8, $row, $this->translator->trans('lender-operations_operations-csv-recovery-commission-amount-column'));

        $column = 9;
        foreach (OperationType::TAX_TYPES_FR as $label) {
            $activeSheet->setCellValueByColumnAndRow($column, $row, $this->translator->trans('lender-operations_operations-csv-' . $label));
            $taxColumns[$label] = $column;
            $column++;
        }
        $balanceColumn = $column;
        $activeSheet->setCellValueByColumnAndRow($balanceColumn, $row, $this->translator->trans('lender-operations_operations-csv-account-balance-column'));
        $row++;

        foreach ($lenderOperations as $operation) {
            $activeSheet->setCellValueByColumnAndRow(0, $row, $this->translator->trans('lender-operations_operation-label-' . $operation['label']));
            $activeSheet->setCellValueByColumnAndRow(1, $row, $operation['id_loan']);
            $activeSheet->setCellValueByColumnAndRow(2, $row, $operation['id_project']);
            $activeSheet->setCellValueByColumnAndRow(3, $row, $operation['title']);
            $activeSheet->setCellValueExplicitByColumnAndRow(4, $row, PHPExcel_Shared_Date::PHPToExcel(strtotime($operation['operationDate'])), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->getCellByColumnAndRow(4, $row)->getStyle()->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY);
            $activeSheet->setCellValueExplicitByColumnAndRow(5, $row, $operation['amount'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->getCellByColumnAndRow(5, $row)->getStyle()->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
            $this->addConditionalStyleToCell($activeSheet, 5, $row);

            if (self::OP_REPAYMENT === $operation['label']) {
                $details = $operationRepository->findBy(['idRepaymentSchedule' => $operation['id_repayment_schedule']]);
                /** @var Operation $operationDetail */
                foreach ($details as $operationDetail) {
                    if (in_array($operationDetail->getType()->getLabel(), OperationType::TAX_TYPES_FR)) {
                        $column = $taxColumns[$operationDetail->getType()->getLabel()];
                    } else {
                        switch ($operationDetail->getType()->getLabel()) {
                            case OperationType::CAPITAL_REPAYMENT:
                                $column = 6;
                                break;
                            case OperationType::GROSS_INTEREST_REPAYMENT:
                                $column = 7;
                                break;
                            case OperationType::COLLECTION_COMMISSION_LENDER:
                                $column = 8;
                                break;
                            default:
                                break;
                        }
                    }
                    $activeSheet->setCellValueExplicitByColumnAndRow($column, $row, $operationDetail->getAmount(), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                    $activeSheet->getCellByColumnAndRow($column, $row)->getStyle()->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                }
            }

            if (OperationSubType::CAPITAL_REPAYMENT_DEBT_COLLECTION === $operation['sub_type_label']) {
                $activeSheet->setCellValueExplicitByColumnAndRow(6, $row, $operation['amount'],\PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $activeSheet->getCellByColumnAndRow(6, $row)->getStyle()->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
            }

            if (OperationType::COLLECTION_COMMISSION_LENDER === $operation['label']) {
                $activeSheet->setCellValueExplicitByColumnAndRow(8, $row, $operation['amount'],\PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $activeSheet->getCellByColumnAndRow(8, $row)->getStyle()->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
            }

            if (self::OP_EARLY_REPAYMENT === $operation['label']) {
                $activeSheet->setCellValueExplicitByColumnAndRow(6, $row, $operation['amount'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $activeSheet->getCellByColumnAndRow(6, $row)->getStyle()->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
            }

            $activeSheet->setCellValueExplicitByColumnAndRow($balanceColumn, $row, $operation['available_balance'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->getCellByColumnAndRow($balanceColumn, $row)->getStyle()->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

            if (OperationType::LENDER_LOAN === $operation['label']) {
                $asteriskColumn = $balanceColumn + 1;
                $activeSheet->setCellValueByColumnAndRow($asteriskColumn, $row, '*');
                $hasLoanRow = true;
            }

            $row++;
        }

        if ($hasLoanRow) {
            $activeSheet->setCellValueByColumnAndRow(0, $row, $this->translator->trans('lender-operations_csv-export-asterisk-accepted-offer-specific-mention'));
        }

        $maxCoordinates = $activeSheet->getHighestRowAndColumn();
        $activeSheet->getStyle('A1:' . $maxCoordinates['column'] . $maxCoordinates['row'])->applyFromArray($style);
        $activeSheet->getStyle('A1:' . $maxCoordinates['column'] . '1')->getFont()->setBold(true);

        return $document;
    }

    /**
     * @param \PHPExcel_Worksheet $activeSheet
     * @param int                 $column
     * @param int                 $row
     */
    private function addConditionalStyleToCell(\PHPExcel_Worksheet $activeSheet, $column, $row)
    {
        $negativeValue = new PHPExcel_Style_Conditional();
        $negativeValue->setConditionType(PHPExcel_Style_Conditional::CONDITION_CELLIS);
        $negativeValue->setOperatorType(PHPExcel_Style_Conditional::OPERATOR_LESSTHAN);
        $negativeValue->addCondition(0);
        $negativeValue->getStyle()->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_RED);

        $positiveValue = new PHPExcel_Style_Conditional();
        $positiveValue->setConditionType(PHPExcel_Style_Conditional::CONDITION_CELLIS);
        $positiveValue->setOperatorType(PHPExcel_Style_Conditional::OPERATOR_GREATERTHANOREQUAL);
        $positiveValue->addCondition('0');
        $positiveValue->getStyle()->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_DARKGREEN);

        $conditionalStyles = $activeSheet->getCellByColumnAndRow($column, $row)->getStyle()->getConditionalStyles();
        array_push($conditionalStyles, $negativeValue);
        array_push($conditionalStyles, $positiveValue);
        $activeSheet->getCellByColumnAndRow($column, $row)->getStyle()->setConditionalStyles($conditionalStyles);
    }
}
