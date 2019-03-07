<?php

namespace Unilend\Bundle\FrontBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Routing\{Generator\UrlGeneratorInterface, RouterInterface};
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{CompanyStatus, Loans, Notifications, Projects, ProjectsStatus, UnderlyingContract, Wallet};

class LenderLoansDisplayManager
{
    const LOAN_STATUS_DISPLAY_IN_PROGRESS   = 'in-progress';
    const LOAN_STATUS_DISPLAY_LATE          = 'late';
    const LOAN_STATUS_DISPLAY_AMICABLE_DC   = 'amicable-dc';
    const LOAN_STATUS_DISPLAY_LITIGATION_DC = 'litigation-dc';
    const LOAN_STATUS_DISPLAY_COMPLETED     = 'completed';
    const LOAN_STATUS_DISPLAY_PROCEEDING    = 'proceeding';
    const LOAN_STATUS_DISPLAY_LOSS          = 'loss';
    const LOAN_STATUS_DISPLAY_PENDING       = 'pending';

    const LOAN_STATUS_AGGREGATE = [
        'pending'        => [self::LOAN_STATUS_DISPLAY_PENDING],
        'repayment'      => [self::LOAN_STATUS_DISPLAY_IN_PROGRESS],
        'repaid'         => [self::LOAN_STATUS_DISPLAY_COMPLETED],
        'late-repayment' => [self::LOAN_STATUS_DISPLAY_LATE],
        'incidents'      => [
            self::LOAN_STATUS_DISPLAY_PROCEEDING,
            self::LOAN_STATUS_DISPLAY_AMICABLE_DC,
            self::LOAN_STATUS_DISPLAY_LITIGATION_DC
        ],
        'loss'           => [self::LOAN_STATUS_DISPLAY_LOSS]
    ];

    const LOAN_STATUS_FILTER = [
        'pending'        => [ProjectsStatus::BID_TERMINATED, ProjectsStatus::FUNDE],
        'repayment'      => [ProjectsStatus::REMBOURSEMENT],
        'repaid'         => [ProjectsStatus::REMBOURSE, ProjectsStatus::REMBOURSEMENT_ANTICIPE],
        'late-repayment' => [ProjectsStatus::PROBLEME],
        'incidents'      => [ProjectsStatus::PROBLEME],
        'loss'           => [ProjectsStatus::LOSS]
    ];

    /** @var TranslatorInterface */
    private $translator;
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var RouterInterface */
    private $router;
    /** @var Packages */
    private $assetPackage;

    /**
     * @param TranslatorInterface    $translator
     * @param EntityManagerInterface $entityManager
     * @param RouterInterface        $router
     * @param Packages               $assetPackage
     */
    public function __construct(TranslatorInterface $translator, EntityManagerInterface $entityManager, RouterInterface $router, Packages $assetPackage)
    {
        $this->translator    = $translator;
        $this->entityManager = $entityManager;
        $this->router        = $router;
        $this->assetPackage  = $assetPackage;
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
            $project        = $projectRepository->find($projectLoans['id_project']);
            $loanStatusInfo = $this->getLenderLoanStatusToDisplay($project);
            $loanData       = [
                'id'                       => $projectLoans['id_project'],
                'url'                      => $this->router->generate('project_detail', ['projectSlug' => $projectLoans['slug']], UrlGeneratorInterface::ABSOLUTE_PATH),
                'name'                     => $projectLoans['title'],
                'rate'                     => round($projectLoans['rate'], 1),
                'risk'                     => $projectLoans['risk'],
                'amount'                   => round($projectLoans['amount']),
                'start_date'               => null,
                'end_date'                 => null,
                'next_payment_date'        => null,
                'monthly_repayment_amount' => null,
                'duration'                 => 0,
                'final_repayment_date'     => null,
                'remaining_capital_amount' => null,
                'project_status'           => $projectLoans['project_status'],
                'loanStatus'               => $loanStatusInfo['status'],
                'loanStatusLabel'          => $loanStatusInfo['statusLabel'],
                'isCloseOutNetting'        => false
            ];

            if (in_array($projectLoans['project_status'], [ProjectsStatus::BID_TERMINATED, ProjectsStatus::FUNDE])) {
                if (false === empty($statusFilter) && false === in_array($loanStatusInfo['status'], self::LOAN_STATUS_AGGREGATE[$statusFilter])) {
                    continue;
                }

                ++$loanStatus['pending'];

                $lenderProjectLoans[] = $loanData;
                unset($loanData);
            } elseif ($projectLoans['project_status'] >= ProjectsStatus::REMBOURSEMENT) {
                if (false === empty($statusFilter) && false === in_array($loanStatusInfo['status'], self::LOAN_STATUS_AGGREGATE[$statusFilter])) {
                    continue;
                }

                $startDateTime     = new \DateTime();
                $endDateTime       = new \DateTime($projectLoans['fin']);
                $remainingDuration = $startDateTime->diff($endDateTime);

                $loanData['start_date']               = \DateTime::createFromFormat('Y-m-d', $projectLoans['debut']);
                $loanData['end_date']                 = \DateTime::createFromFormat('Y-m-d', $projectLoans['fin']);
                $loanData['next_payment_date']        = \DateTime::createFromFormat('Y-m-d', $projectLoans['next_echeance']);
                $loanData['monthly_repayment_amount'] = $projectLoans['monthly_repayment_amount'];
                $loanData['duration']                 = $remainingDuration->y * 12 + $remainingDuration->m + ($remainingDuration->d > 0 ? 1 : 0);
                $loanData['final_repayment_date']     = \DateTime::createFromFormat('Y-m-d H:i:s', $projectLoans['final_repayment_date']);
                $loanData['remaining_capital_amount'] = $projectLoans['remaining_capital'];
                $loanData['isCloseOutNetting']        = $project->getCloseOutNettingDate() instanceof \DateTime;

                switch ($loanData['loanStatus']) {
                    case self::LOAN_STATUS_DISPLAY_PROCEEDING:
                    case self::LOAN_STATUS_DISPLAY_LITIGATION_DC:
                    case self::LOAN_STATUS_DISPLAY_AMICABLE_DC:
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
                        'rate'      => round($partialLoan->getRate()->getMargin(), 1),
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
            'pending'        => '#787679',
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
     * @param array $lenderLoans
     *
     * @return array
     */
    public function formatLenderLoansForExport(array $lenderLoans): array
    {
        $projectRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects');

        $projectsInDept     = $projectRepository->getProjectsInDebt();
        $lenderProjectLoans = [];

        foreach ($lenderLoans as $projectLoans) {
            $project        = $projectRepository->find($projectLoans['id_project']);
            $loanStatusInfo = $this->getLenderLoanStatusToDisplay($project);
            $loanData = [
                'id'                     => $projectLoans['id_project'],
                'name'                   => $projectLoans['title'],
                'loanStatusLabel'        => $loanStatusInfo['statusLabel'],
                'amount'                 => round($projectLoans['amount']),
                'risk'                   => $projectLoans['risk'],
                'rate'                   => round($projectLoans['rate'], 1),
                'startDate'              => '',
                'loanStatus'             => $loanStatusInfo['status'],
                'isCloseOutNetting'      => false,
                'finalRepaymentDate'     => '',
                'nextRepaymentDate'      => '',
                'endDate'                => '',
                'monthlyRepaymentAmount' => '',
                'numberOfLoansInDebt'    => 0
            ];

            if ($projectLoans['project_status'] >= ProjectsStatus::REMBOURSEMENT) {
                $startDate          = \DateTime::createFromFormat('Y-m-d', $projectLoans['debut']);
                $finalRepaymentDate = \DateTime::createFromFormat('Y-m-d H:i:s', $projectLoans['final_repayment_date']);
                $nextRepaymentDate  = \DateTime::createFromFormat('Y-m-d', $projectLoans['next_echeance']);
                $endDate            = \DateTime::createFromFormat('Y-m-d', $projectLoans['fin']);

                $loanData['startDate']              = $startDate ? $startDate->format('d/m/Y') : '';
                $loanData['isCloseOutNetting']      = $project->getCloseOutNettingDate() instanceof \DateTime;
                $loanData['finalRepaymentDate']     = $finalRepaymentDate ? $finalRepaymentDate->format('d/m/Y') : '';
                $loanData['nextRepaymentDate']      = $nextRepaymentDate ? $nextRepaymentDate->format('d/m/Y') : '';
                $loanData['endDate']                = $endDate ? $endDate->format('d/m/Y') : '';
                $loanData['monthlyRepaymentAmount'] = $projectLoans['monthly_repayment_amount'];
                $loanData['numberOfLoansInDebt']    = in_array($project->getIdProject(), $projectsInDept) ? $projectLoans['nb_loan'] : 0;

                $lenderProjectLoans[] = $loanData;
            } elseif (in_array($projectLoans['project_status'], [ProjectsStatus::BID_TERMINATED, ProjectsStatus::FUNDE])) {
                $lenderProjectLoans[] = $loanData;
            }
        }

        return $lenderProjectLoans;
    }

    /**
     * @param Projects $project
     *
     * @return array
     */
    private function getLenderLoanStatusToDisplay(Projects $project)
    {
        switch ($project->getStatus()) {
            case ProjectsStatus::PROBLEME:
                switch ($project->getIdCompany()->getIdStatus()->getLabel()) {
                    case CompanyStatus::STATUS_PRECAUTIONARY_PROCESS:
                    case CompanyStatus::STATUS_RECEIVERSHIP:
                    case CompanyStatus::STATUS_COMPULSORY_LIQUIDATION:
                        $statusToDisplay = self::LOAN_STATUS_DISPLAY_PROCEEDING;
                        $loanStatusLabel = $this->translator->trans('lender-operations_detailed-loan-status-label-' . str_replace('_', '-', $project->getIdCompany()->getIdStatus()->getLabel()));
                        break;
                    case CompanyStatus::STATUS_IN_BONIS:
                    default:
                        if (0 === $project->getDebtCollectionMissions()->count()) {
                            $statusToDisplay = self::LOAN_STATUS_DISPLAY_LATE;
                        } elseif (0 < $project->getLitigationDebtCollectionMissions()->count()) {
                            $statusToDisplay = self::LOAN_STATUS_DISPLAY_LITIGATION_DC;
                        } else {
                            $statusToDisplay = self::LOAN_STATUS_DISPLAY_AMICABLE_DC;
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
            case ProjectsStatus::BID_TERMINATED:
            case ProjectsStatus::FUNDE:
                $statusToDisplay = self::LOAN_STATUS_DISPLAY_PENDING;
                $loanStatusLabel = $this->translator->trans('lender-operations_detailed-loan-status-label-pending');
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
                'url'   => $this->router->generate('loan_contract_pdf', ['clientHash' => $hash, 'idLoan' => $loanId]),
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
