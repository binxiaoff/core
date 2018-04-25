<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Box\Spout\Common\Type;
use Box\Spout\Writer\{
    AbstractWriter, Style\Border, Style\BorderBuilder, Style\Color, Style\StyleBuilder, WriterFactory, XLSX\Writer
};
use Doctrine\ORM\EntityManager;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Routing\{
    Generator\UrlGeneratorInterface, RouterInterface
};
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    CompanyStatus, Loans, Notifications, Operation, OperationSubType, OperationType, Projects, ProjectsStatus, UnderlyingContract, Wallet, WalletType
};

class LenderOperationsManager
{
    const OP_REPAYMENT                         = 'repayment';
    const OP_REPAYMENT_REGULARIZATION          = 'repayment_regularization';
    const OP_RECOVERY_REPAYMENT                = 'recovery-repayment';
    const OP_RECOVERY_REPAYMENT_REGULARIZATION = 'recovery-repayment-regularization';
    const OP_EARLY_REPAYMENT                   = 'early-repayment';
    const OP_BID                               = 'bid';
    const OP_REFUSED_BID                       = 'refused-bid';
    const OP_AUTOBID                           = 'autobid';
    const OP_REFUSED_AUTOBID                   = 'refused-autobid';
    const OP_REFUSED_LOAN                      = 'refused-loan';

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
        self::OP_RECOVERY_REPAYMENT_REGULARIZATION,
        OperationType::COLLECTION_COMMISSION_LENDER,
        self::OP_REPAYMENT_REGULARIZATION
    ];

    const ALL_TYPES = [
        OperationType::LENDER_PROVISION,
        OperationType::LENDER_PROVISION_CANCEL,
        OperationType::LENDER_TRANSFER,
        OperationType::LENDER_WITHDRAW,
        OperationSubType::UNILEND_PROMOTIONAL_OPERATION_SPONSORSHIP_REWARD_SPONSOR,
        OperationSubType::UNILEND_PROMOTIONAL_OPERATION_CANCEL_SPONSORSHIP_REWARD_SPONSOR,
        OperationSubType::UNILEND_PROMOTIONAL_OPERATION_SPONSORSHIP_REWARD_SPONSEE,
        OperationSubType::UNILEND_PROMOTIONAL_OPERATION_CANCEL_SPONSORSHIP_REWARD_SPONSEE,
        OperationSubType::UNILEND_PROMOTIONAL_OPERATION_WELCOME_OFFER,
        OperationSubType::UNILEND_PROMOTIONAL_OPERATION_CANCEL_WELCOME_OFFER,
        OperationType::UNILEND_LENDER_REGULARIZATION,
        self::OP_REPAYMENT,
        self::OP_EARLY_REPAYMENT,
        self::OP_RECOVERY_REPAYMENT,
        self::OP_RECOVERY_REPAYMENT_REGULARIZATION,
        OperationType::COLLECTION_COMMISSION_LENDER,
        OperationType::COLLECTION_COMMISSION_LENDER_REGULARIZATION,
        self::OP_REPAYMENT_REGULARIZATION,
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

    const LOAN_STATUS_DISPLAY_IN_PROGRESS     = 'in-progress';
    const LOAN_STATUS_DISPLAY_LATE            = 'late';
    const LOANS_STATUS_DISPLAY_AMICABLE_DC    = 'amicable-dc';
    const LOANS_STATUS_DISPLAY_LITIGATION_DC  = 'litigation-dc';
    const LOAN_STATUS_DISPLAY_COMPLETED       = 'completed';
    const LOAN_STATUS_DISPLAY_PROCEEDING      = 'proceeding';
    const LOAN_STATUS_DISPLAY_LOSS            = 'loss';

    const LOAN_STATUS_AGGREGATE = [
        'repayment'      => [self::LOAN_STATUS_DISPLAY_IN_PROGRESS],
        'repaid'         => [self::LOAN_STATUS_DISPLAY_COMPLETED],
        'late-repayment' => [self::LOAN_STATUS_DISPLAY_LATE],
        'incidents'      => [
            self::LOAN_STATUS_DISPLAY_PROCEEDING,
            self::LOANS_STATUS_DISPLAY_AMICABLE_DC,
            self::LOANS_STATUS_DISPLAY_LITIGATION_DC
        ],
        'loss'           => [self::LOAN_STATUS_DISPLAY_LOSS]
    ];

    const LOAN_STATUS_FILTER = [
        'repayment'      => [ProjectsStatus::REMBOURSEMENT],
        'repaid'         => [ProjectsStatus::REMBOURSE, ProjectsStatus::REMBOURSEMENT_ANTICIPE],
        'late-repayment' => [ProjectsStatus::PROBLEME],
        'incidents'      => [ProjectsStatus::PROBLEME],
        'loss'           => [ProjectsStatus::LOSS]
    ];

    /** @var EntityManager */
    private $entityManager;
    /** @var TranslatorInterface */
    private $translator;
    /** @var RouterInterface */
    private $router;
    /** @var Packages */
    private $assetPackage;

    /**
     * @param EntityManager $entityManager
     * @param TranslatorInterface    $translator
     * @param RouterInterface $router
     * @param Packages $assetPackage
     */
    public function __construct(EntityManager $entityManager, TranslatorInterface $translator, RouterInterface $router, Packages $assetPackage)
    {
        $this->entityManager = $entityManager;
        $this->translator    = $translator;
        $this->router        = $router;
        $this->assetPackage  = $assetPackage;
    }

    /**
     * @param Wallet     $wallet
     * @param \DateTime  $start
     * @param \DateTime  $end
     * @param int|null   $idProject
     * @param array|null $operations
     *
     * @return array
     * @throws \Exception
     */
    public function getLenderOperations(Wallet $wallet, \DateTime $start, \DateTime $end, $idProject = null, array $operations = null)
    {
        if (WalletType::LENDER !== $wallet->getIdType()->getLabel()) {
            throw new \Exception('Wallet is not a Lender wallet');
        }

        $walletBalanceHistoryRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:WalletBalanceHistory');
        $operationRepository            = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Operation');
        $walletHistory                  = $walletBalanceHistoryRepository->getLenderOperationHistory($wallet, $start, $end);
        $lenderOperations               = [];
        $previousHistoryLineIndex       = null;

        foreach ($walletHistory as $index => $historyLine) {
            if (
                in_array(self::OP_REPAYMENT, $operations)
                && false === empty($historyLine['id_repayment_task_log'])
                && $historyLine['id'] !== $historyLine['id_repayment_task_log']
            ) {
                $type = self::OP_REPAYMENT;

                if (false !== strpos($historyLine['label'], '_regularization')) {
                    $type = self::OP_REPAYMENT_REGULARIZATION;
                }
                $repaymentDetails = $operationRepository->getDetailByLoanAndRepaymentLog($historyLine['id_loan'], $wallet->getId(), $historyLine['id_repayment_task_log']);
                $historyLine      = $this->formatRepaymentOperation($wallet, $repaymentDetails, $historyLine, $type);
            }

            if (OperationSubType::CAPITAL_REPAYMENT_EARLY === $historyLine['sub_type_label']) {
                $historyLine['label'] = self::OP_EARLY_REPAYMENT;
            }

            if (OperationSubType::CAPITAL_REPAYMENT_DEBT_COLLECTION === $historyLine['sub_type_label']) {
                $historyLine['label'] = self::OP_RECOVERY_REPAYMENT;
            }

            if (OperationSubType::CAPITAL_REPAYMENT_DEBT_COLLECTION_REGULARIZATION === $historyLine['sub_type_label']) {
                $historyLine['label'] = self::OP_RECOVERY_REPAYMENT_REGULARIZATION;
            }

            if (in_array($historyLine['label'], [OperationType::UNILEND_PROMOTIONAL_OPERATION, OperationType::UNILEND_PROMOTIONAL_OPERATION_CANCEL])) {
                $historyLine['label'] = $historyLine['sub_type_label'];
            }

            if (self::OP_REFUSED_BID === $historyLine['label']) {
                if (empty($historyLine['amount']) && empty($historyLine['id_bid']) && empty($historyLine['id_loan'])) {
                    $walletBalanceHistory  = $walletBalanceHistoryRepository->getPreviousLineForWallet($wallet, $historyLine['id']);
                    $amount                = bcsub($walletBalanceHistory->getAvailableBalance(), $historyLine['amount'], 2);
                    $historyLine['amount'] = $amount;
                }

                if (false === empty($historyLine['id_loan'])) {
                    $historyLine['label']  = self::OP_REFUSED_LOAN;
                    $loan                  = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Loans')->find($historyLine['id_loan']);
                    $historyLine['amount'] = bcdiv($loan->getAmount(), 100, 2);
                }
            }

            $lenderOperations[$index] = $historyLine;
        }

        if (null !== $idProject || null !== $operations) {
            return $this->filterLenderOperations($lenderOperations, $idProject, $operations);
        }

        return $lenderOperations;
    }

    /**
     * @param Wallet     $wallet
     * @param bool|array $repaymentDetail
     * @param array      $historyLine
     * @param string     $type
     *
     * @return array
     */
    private function formatRepaymentOperation(Wallet $wallet, $repaymentDetail, array $historyLine, $type)
    {
        $taxExemptionRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:LenderTaxExemption');

        if (is_array($repaymentDetail)) {
            $historyLine['label']  = $type;
            $amount                = bcsub(bcadd($repaymentDetail['capital'], $repaymentDetail['interest'], 2), $repaymentDetail['taxes'], 2);
            $historyLine['amount'] = self::OP_REPAYMENT === $type ? $amount : -$amount;
            if (self::OP_REPAYMENT === $type) {
                $calculatedAvailableBalance = bcadd($historyLine['available_balance'], bcsub($repaymentDetail['interest'], $repaymentDetail['taxes'], 2), 2);
            } else {
                $calculatedAvailableBalance = bcsub($historyLine['available_balance'], bcsub($repaymentDetail['interest'], $repaymentDetail['taxes'], 2), 2);
            }

            $historyLine['available_balance'] = null === $repaymentDetail['available_balance'] ? $calculatedAvailableBalance : $repaymentDetail['available_balance'];

            $historyLine['detail'] = [
                'label' => $this->translator->trans('lender-operations_operations-table-repayment-collapse-details'),
                'items' => [
                    [
                        'label' => $this->translator->trans('lender-operations_operations-table-repaid-capital-amount-collapse-details'),
                        'value' => self::OP_REPAYMENT === $type ? $repaymentDetail['capital'] : -$repaymentDetail['capital']
                    ],
                    [
                        'label' => $this->translator->trans('lender-operations_operations-table-repaid-interests-amount-collapse-details'),
                        'value' => self::OP_REPAYMENT === $type ? $repaymentDetail['interest'] : -$repaymentDetail['interest']
                    ]
                ]
            ];

            if ($repaymentDetail['taxes']) {
                $taxLabel = $this->translator->trans('lender-operations_tax-and-social-deductions-label');
                if ($wallet->getIdClient()->isNaturalPerson()) {
                    if ($taxExemptionRepository->isLenderExemptedInYear($wallet, substr($historyLine['operationDate'], 0, 4))) {
                        $taxLabel = $this->translator->trans('lender-operations_social-deductions-label');
                    }
                } else {
                    $taxLabel = $this->translator->trans('preteur-operations-vos-operations_retenues-a-la-source');
                }
                $historyLine['detail']['items'][] = [
                    'label' => $taxLabel,
                    'value' => self::OP_REPAYMENT === $type ? -$repaymentDetail['taxes'] : $repaymentDetail['taxes']
                ];
            }
        } else {
            $historyLine['label'] = $type;
        }

        return $historyLine;
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
            case self::FILTER_WITHDRAW:
                return self::WITHDRAW_TYPES;
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
     * @param string    $fileName
     *
     * @return AbstractWriter
     */
    public function getOperationsExcelFile(Wallet $wallet, $start, $end, $idProject, $operationTypes, $fileName)
    {
        $operationRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Operation');
        $lenderOperations    = $this->getLenderOperations($wallet, $start, $end, $idProject, $operationTypes);
        $taxColumns          = [];
        $hasLoans            = false;

        $border = (new BorderBuilder())
            ->setBorderBottom(Color::BLACK, Border::WIDTH_THIN, Border::STYLE_SOLID)
            ->setBorderLeft(Color::BLACK, Border::WIDTH_THIN, Border::STYLE_SOLID)
            ->setBorderRight(Color::BLACK, Border::WIDTH_THIN, Border::STYLE_SOLID)
            ->setBorderTop(Color::BLACK, Border::WIDTH_THIN, Border::STYLE_SOLID)
            ->build();

        $defaultStyle = (new StyleBuilder())
            ->setFontName('Arial')
            ->setFontSize(11)
            ->setBorder($border)
            ->build();

        /** @var Writer $writer */
        $writer = WriterFactory::create(Type::XLSX);
        $writer->setShouldUseInlineStrings(false);
        $writer->openToBrowser($fileName);

        $header = [
            $this->translator->trans('lender-operations_operations-csv-operation-column'),
            $this->translator->trans('lender-operations_operations-csv-contract-column'),
            $this->translator->trans('lender-operations_operations-csv-project-id-column'),
            $this->translator->trans('lender-operations_operations-csv-project-label-column'),
            $this->translator->trans('lender-operations_operations-csv-operation-date-column'),
            $this->translator->trans('lender-operations_operations-csv-operation-amount-column'),
            $this->translator->trans('lender-operations_operations-csv-repaid-capital-amount-column'),
            $this->translator->trans('lender-operations_operations-csv-perceived-interests-amount-column'),
            $this->translator->trans('lender-operations_operations-csv-recovery-commission-amount-column')
        ];

        $column = 9;
        foreach (OperationType::TAX_TYPES_FR as $label) {
            $header[]           = $this->translator->trans('lender-operations_operations-csv-' . $label);
            $taxColumns[$label] = $column++;
        }
        $balanceColumn = $column;
        $header[] = $this->translator->trans('lender-operations_operations-csv-account-balance-column');

        foreach ($lenderOperations as $operation) {
            if (OperationType::LENDER_LOAN === $operation['label']) {
                $header[] = '';
                $hasLoans = true;
                break;
            }
        }

        $writer->addRowWithStyle($header, $defaultStyle);

        foreach ($lenderOperations as $operation) {
            $row = [
                $this->translator->trans('lender-operations_operation-label-' . $operation['label']),
                $operation['id_loan'],
                $operation['id_project'],
                $operation['title'],
                \DateTime::createFromFormat('Y-m-d H:i:s', $operation['operationDate'])->format('d/m/Y'),
                (float) $operation['amount']
            ];
            $row = $row + array_fill(count($row), count($header) - count($row), '');

            if (self::OP_REPAYMENT === $operation['label']) {
                $details = $operationRepository->findBy([
                    'idRepaymentTaskLog'  => $operation['id_repayment_task_log'],
                    'idRepaymentSchedule' => $operation['id_repayment_schedule']
                ]);
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
                    $row[$column] = (float) $operationDetail->getAmount();
                }
            }

            if (self::OP_REPAYMENT_REGULARIZATION === $operation['label']) {
                $details = $operationRepository->findBy([
                    'idRepaymentTaskLog'  => $operation['id_repayment_task_log'],
                    'idRepaymentSchedule' => $operation['id_repayment_schedule']
                ]);
                /** @var Operation $operationDetail */
                foreach ($details as $operationDetail) {
                    switch ($operationDetail->getType()->getLabel()) {
                        case OperationType::CAPITAL_REPAYMENT_REGULARIZATION:
                            $column = 6;
                            break;
                        case OperationType::GROSS_INTEREST_REPAYMENT_REGULARIZATION:
                            $column = 7;
                            break;
                        case OperationType::COLLECTION_COMMISSION_LENDER:
                            $column = 8;
                            break;
                        case OperationType::TAX_FR_CONTRIBUTIONS_ADDITIONNELLES_REGULARIZATION:
                            $column = $taxColumns[OperationType::TAX_FR_CONTRIBUTIONS_ADDITIONNELLES];
                            break;
                        case OperationType::TAX_FR_CRDS_REGULARIZATION:
                            $column = $taxColumns[OperationType::TAX_FR_CRDS];
                            break;
                        case OperationType::TAX_FR_CSG_REGULARIZATION:
                            $column = $taxColumns[OperationType::TAX_FR_CSG];
                            break;
                        case OperationType::TAX_FR_PRELEVEMENTS_DE_SOLIDARITE_REGULARIZATION:
                            $column = $taxColumns[OperationType::TAX_FR_PRELEVEMENTS_DE_SOLIDARITE];
                            break;
                        case OperationType::TAX_FR_PRELEVEMENTS_OBLIGATOIRES_REGULARIZATION:
                            $column = $taxColumns[OperationType::TAX_FR_PRELEVEMENTS_OBLIGATOIRES];
                            break;
                        case OperationType::TAX_FR_PRELEVEMENTS_SOCIAUX_REGULARIZATION:
                            $column = $taxColumns[OperationType::TAX_FR_PRELEVEMENTS_SOCIAUX];
                            break;
                        case OperationType::TAX_FR_RETENUES_A_LA_SOURCE_REGULARIZATION:
                            $column = $taxColumns[OperationType::TAX_FR_RETENUES_A_LA_SOURCE];
                            break;
                        default:
                            break;
                    }
                    $row[$column] = (float) $operationDetail->getAmount();
                }
            }

            if (
                OperationSubType::CAPITAL_REPAYMENT_DEBT_COLLECTION === $operation['sub_type_label']
                || self::OP_EARLY_REPAYMENT === $operation['label']
            ) {
                $row[6] = (float) $operation['amount'];
            }

            if (OperationType::COLLECTION_COMMISSION_LENDER === $operation['label']) {
                $row[8] = (float) $operation['amount'];
            }

            $row[$balanceColumn] = (float) $operation['available_balance'];

            if (OperationType::LENDER_LOAN === $operation['label']) {
                $row[$balanceColumn + 1] = '*';
            }

            $writer->addRowWithStyle($row, $defaultStyle);
        }

        if ($hasLoans) {
            $writer->addRow([$this->translator->trans('lender-operations_csv-export-asterisk-accepted-offer-specific-mention')]);
        }

        return $writer;
    }

    /**
     * @param Projects    $project
     *
     * @return array
     */
    public function getLenderLoanStatusToDisplay(Projects $project)
    {
        switch ($project->getStatus()) {
            case ProjectsStatus::PROBLEME:
                switch ($project->getIdCompany()->getIdStatus()->getLabel()) {
                    case CompanyStatus::STATUS_PRECAUTIONARY_PROCESS:
                    case CompanyStatus::STATUS_RECEIVERSHIP:
                    case CompanyStatus::STATUS_COMPULSORY_LIQUIDATION:
                        $statusToDisplay = self::LOAN_STATUS_DISPLAY_PROCEEDING;
                        $loanStatusLabel = $this->translator->trans('lender-operations_detailed-loan-status-label-'  .str_replace('_', '-', $project->getIdCompany()->getIdStatus()->getLabel()));
                        break;
                    case CompanyStatus::STATUS_IN_BONIS:
                    default:
                        if (0 === $project->getDebtCollectionMissions()->count()) {
                            $statusToDisplay = self::LOAN_STATUS_DISPLAY_LATE;
                        } elseif(0 < $project->getLitigationDebtCollectionMissions()->count()) {
                            $statusToDisplay = self::LOANS_STATUS_DISPLAY_LITIGATION_DC;
                        } else {
                            $statusToDisplay = self::LOANS_STATUS_DISPLAY_AMICABLE_DC;
                        }
                        $loanStatusLabel = $this->translator->trans('lender-operations_detailed-loan-status-label-' . $statusToDisplay);
                        break;
                }
                break;
            case ProjectsStatus::LOSS:
                $statusToDisplay = self::LOAN_STATUS_DISPLAY_LOSS;
                $loanStatusLabel = $this->translator->trans('lender-operations_detailed-loan-status-label-lost');
                break;
            case ProjectsStatus::REMBOURSE:
                $statusToDisplay = self::LOAN_STATUS_DISPLAY_COMPLETED;
                if (null === $project->getCloseOutNettingDate()) {
                    $loanStatusLabel = $this->translator->trans('lender-operations_detailed-loan-status-label-repaid');
                } else {
                    $loanStatusLabel = $this->translator->trans('lender-operations_detailed-loan-status-label-collected');
                }
                break;
            case ProjectsStatus::REMBOURSEMENT_ANTICIPE:
                $statusToDisplay = self::LOAN_STATUS_DISPLAY_COMPLETED;
                $loanStatusLabel = $this->translator->trans('lender-operations_detailed-loan-status-label-early-r');
                break;
            case ProjectsStatus::REMBOURSEMENT:
            default:
                $statusToDisplay = self::LOAN_STATUS_DISPLAY_IN_PROGRESS;
                $loanStatusLabel = $this->translator->trans('lender-operations_detailed-loan-status-label-' . $statusToDisplay);
                break;
        }

        return ['status' => $statusToDisplay, 'statusLabel' => $loanStatusLabel];
    }

    /**
     * @param Wallet      $wallet
     * @param array       $lenderLoans
     * @param string|null $statusFilter
     *
     * @return array
     */
    public function formatLenderLoansData(Wallet $wallet, array $lenderLoans, ?string $statusFilter = null): array
    {
        $projectRepository       = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects');
        $notificationsRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Notifications');
        $loansRepository         = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Loans');

        $projectsInDept     = $projectRepository->getProjectsInDebt();
        $loanStatus         = array_fill_keys(array_keys(self::LOAN_STATUS_FILTER), 0);
        $lenderProjectLoans = [];

        foreach ($lenderLoans as $projectLoans) {
            if ($projectLoans['project_status'] >= ProjectsStatus::REMBOURSEMENT) {
                $loanData       = [];
                $project        = $projectRepository->find($projectLoans['id_project']);
                $loanStatusInfo = $this->getLenderLoanStatusToDisplay($project);

                if (false === empty($statusFilter) && false === in_array($loanStatusInfo['status'], self::LOAN_STATUS_AGGREGATE[$statusFilter])) {
                    continue;
                }

                $startDateTime     = new \DateTime(date('Y-m-d'));
                $endDateTime       = new \DateTime($projectLoans['fin']);
                $remainingDuration = $startDateTime->diff($endDateTime);

                $loanData['id']                       = $projectLoans['id_project'];
                $loanData['url']                      = $this->router->generate('project_detail', ['projectSlug' => $projectLoans['slug']], UrlGeneratorInterface::ABSOLUTE_PATH);
                $loanData['name']                     = $projectLoans['title'];
                $loanData['rate']                     = round($projectLoans['rate'], 1);
                $loanData['risk']                     = $projectLoans['risk'];
                $loanData['amount']                   = round($projectLoans['amount']);
                $loanData['start_date']               = \DateTime::createFromFormat('Y-m-d', $projectLoans['debut']);
                $loanData['end_date']                 = \DateTime::createFromFormat('Y-m-d', $projectLoans['fin']);
                $loanData['next_payment_date']        = \DateTime::createFromFormat('Y-m-d', $projectLoans['next_echeance']);
                $loanData['monthly_repayment_amount'] = $projectLoans['monthly_repayment_amount'];
                $loanData['duration']                 = $remainingDuration->y * 12 + $remainingDuration->m + ($remainingDuration->d > 0 ? 1 : 0);
                $loanData['final_repayment_date']     = \DateTime::createFromFormat('Y-m-d H:i:s', $projectLoans['final_repayment_date']);
                $loanData['remaining_capital_amount'] = $projectLoans['remaining_capital'];
                $loanData['project_status']           = $projectLoans['project_status'];
                $loanData['loanStatus']               = $loanStatusInfo['status'];
                $loanData['loanStatusLabel']          = $loanStatusInfo['statusLabel'];
                $loanData['isCloseOutNetting']        = $project->getCloseOutNettingDate() instanceof \DateTime;

                switch ($loanData['loanStatus']) {
                    case self::LOAN_STATUS_DISPLAY_PROCEEDING:
                    case self::LOANS_STATUS_DISPLAY_LITIGATION_DC:
                    case self::LOANS_STATUS_DISPLAY_AMICABLE_DC:
                        ++$loanStatus['incidents'];
                        break;
                    case self::LOAN_STATUS_DISPLAY_LATE:
                        ++$loanStatus['late-repayment'];
                        break;
                    case self::LOAN_STATUS_DISPLAY_COMPLETED:
                        ++$loanStatus['repaid'];
                        break;
                    case self::LOAN_STATUS_DISPLAY_IN_PROGRESS:
                        ++$loanStatus['repayment'];
                        break;
                    case self::LOAN_STATUS_DISPLAY_LOSS:
                        ++$loanStatus['loss'];
                }
                try {
                    $loanData['activity'] = [
                        'unread_count' => $notificationsRepository->countUnreadNotificationsForClient($wallet->getId(), $projectLoans['id_project'], [Notifications::TYPE_LOAN_ACCEPTED])
                    ];
                } catch (\Exception $exception) {
                    unset($exception);
                    $loanData['activity'] = [
                        'unread_count' => 0
                    ];
                }

                /** @var Loans[] $projectLoansDetails */
                $projectLoansDetails = $loansRepository->findBy([
                    'idLender'  => $wallet->getId(),
                    'idProject' => $project
                ]);
                $loans               = [];
                $loanData['count']   = [
                    'bond'        => 0,
                    'contract'    => 0,
                    'declaration' => 0
                ];

                foreach ($projectLoansDetails as $partialLoan) {
                    (1 == $partialLoan->getIdTypeContract()->getIdContract()) ? $loanData['count']['bond']++ : $loanData['count']['contract']++;

                    $loans[] = [
                        'rate'      => round($partialLoan->getRate(), 1),
                        'amount'    => bcdiv($partialLoan->getAmount(), 100, 0),
                        'documents' => $this->getDocumentDetail(
                            $projectLoans['project_status'],
                            $wallet->getIdClient()->getHash(),
                            $partialLoan->getIdLoan(),
                            $partialLoan->getIdTypeContract(),
                            $projectsInDept,
                            $projectLoans['id_project'],
                            $loanData['count']['declaration']
                        )
                    ];
                }

                $loanData['loans']    = $loans;
                $lenderProjectLoans[] = $loanData;
                unset($loans, $loanData);
            }
        }

        $seriesData  = [];
        $chartColors = [
            'late-repayment' => '#FFCA2C',
            'incidents'      => '#F2980C',
            'repaid'         => '#4FA8B0',
            'repayment'      => '#1B88DB',
            'loss'           => '#F76965'
        ];

        foreach ($loanStatus as $status => $count) {
            if ($count) {
                $seriesData[] = [
                    'name'         => $this->translator->transChoice('lender-operations_loans-chart-legend-loan-status-' . $status, $count, ['%count%' => $count]),
                    'y'            => $count,
                    'showInLegend' => true,
                    'color'        => $chartColors[$status],
                    'status'       => $status
                ];
            }
        }

        return ['lenderLoans' => $lenderProjectLoans, 'seriesData' => $seriesData];
    }

    /**
     * @param int                $projectStatus
     * @param string             $hash
     * @param int                $loanId
     * @param UnderlyingContract $contract
     * @param array              $projectsInDept
     * @param int                $projectId
     * @param int                $nbDeclarations
     *
     * @return array
     */
    private function getDocumentDetail(
        int $projectStatus,
        string $hash,
        int $loanId,
        UnderlyingContract $contract,
        array $projectsInDept,
        int $projectId,
        int &$nbDeclarations = 0
    ): array
    {
        $documents = [];

        if ($projectStatus >= \projects_status::REMBOURSEMENT) {
            $documents[] = [
                'url'   => $this->assetPackage->getUrl('') . '/pdf/contrat/' . $hash . '/' . $loanId,
                'label' => $this->translator->trans('contract-type-label_' . $contract->getLabel()),
                'type'  => 'bond'
            ];
        }

        if (in_array($projectId, $projectsInDept)) {
            $nbDeclarations++;
            $documents[] = [
                'url'   => $this->assetPackage->getUrl('') . '/pdf/declaration_de_creances/' . $hash . '/' . $loanId,
                'label' => $this->translator->trans('lender-operations_loans-table-declaration-of-debt-doc-tooltip'),
                'type'  => 'declaration'
            ];
        }
        return $documents;
    }
}
