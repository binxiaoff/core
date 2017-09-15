<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Doctrine\ORM\EntityManager;
use Knp\Snappy\GeneratorInterface;
use PHPExcel_IOFactory;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Unilend\Bundle\CoreBusinessBundle\Entity\CompanyStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\Loans;
use Unilend\Bundle\CoreBusinessBundle\Entity\Notifications;
use Unilend\Bundle\CoreBusinessBundle\Entity\Operation;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationSubType;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContract;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Service\LenderOperationsManager;
use Unilend\Bundle\FrontBundle\Security\User\UserLender;
use Unilend\core\Loader;

class LenderOperationsController extends Controller
{
    const LAST_OPERATION_DATE = '2013-01-01';

    const LOAN_STATUS_FILTER = [
        'repayment'      => [ProjectsStatus::REMBOURSEMENT],
        'repaid'         => [ProjectsStatus::REMBOURSE, ProjectsStatus::REMBOURSEMENT_ANTICIPE],
        'late-repayment' => [ProjectsStatus::PROBLEME],
        'incidents'      => [ProjectsStatus::PROBLEME],
        'loss'           => [ProjectsStatus::PERTE]
    ];

    const LOAN_STATUS_AGGREGATE = [
        'repayment'      => [LenderOperationsManager::LOAN_STATUS_DISPLAY_IN_PROGRESS],
        'repaid'         => [LenderOperationsManager::LOAN_STATUS_DISPLAY_COMPLETED],
        'late-repayment' => [LenderOperationsManager::LOAN_STATUS_DISPLAY_LATE],
        'incidents'      => [LenderOperationsManager::LOAN_STATUS_DISPLAY_PROCEEDING, LenderOperationsManager::LOANS_STATUS_DISPLAY_AMICABLE_DC, LenderOperationsManager::LOANS_STATUS_DISPLAY_LITIGATION_DC],
        'loss'           => [LenderOperationsManager::LOAN_STATUS_DISPLAY_LOSS]
    ];

    /**
     * @Route("/operations", name="lender_operations")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        /** @var EntityManagerSimulator $entityManagerSimulator */
        $entityManagerSimulator = $this->get('unilend.service.entity_manager');

        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var LenderOperationsManager $lenderOperationsManager */
        $lenderOperationsManager = $this->get('unilend.service.lender_operations_manager');

        $wallet                 = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($this->getUser()->getClientId(), WalletType::LENDER);
        $filters                = $this->getOperationFilters($request);
        $operations             = $lenderOperationsManager->getOperationsAccordingToFilter($filters['operation']);
        $lenderOperations       = $lenderOperationsManager->getLenderOperations($wallet, $filters['startDate'], $filters['endDate'], $filters['project'], $operations);
        $projectsFundedByLender = array_combine(array_column($lenderOperations, 'id_project'), array_column($lenderOperations, 'title'));

        $loans = $this->commonLoans($request, $wallet);

        return $this->render(
            '/pages/lender_operations/layout.html.twig',
            [
                'clientId'               => $wallet->getIdClient()->getIdClient(),
                'hash'                   => $this->getUser()->getHash(),
                'lenderOperations'       => $lenderOperations,
                'projectsFundedByLender' => $projectsFundedByLender,
                'loansStatusFilter'      => self::LOAN_STATUS_FILTER,
                'firstLoanYear'          => $entityManagerSimulator->getRepository('loans')->getFirstLoanYear($wallet->getId()),
                'lenderLoans'            => $loans['lenderLoans'],
                'seriesData'             => $loans['seriesData'],
                'currentFilters'         => $filters
            ]
        );
    }

    /**
     * @Route("/operations/filterLoans", name="filter_loans")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function filterLoansAction(Request $request)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $wallet        = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($this->getUser()->getClientId(), WalletType::LENDER);
        $loans         = $this->commonLoans($request, $wallet);

        return $this->json([
            'target'   => 'loans .panel-table',
            'template' => $this->render(
                '/pages/lender_operations/my_loans_table.html.twig',
                ['lenderLoans' => $loans['lenderLoans']]
            )->getContent()
        ]);
    }

    /**
     * @Route("/operations/filterOperations", name="filter_operations")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function filterOperationsAction(Request $request)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var LenderOperationsManager $lenderOperationsManager */
        $lenderOperationsManager = $this->get('unilend.service.lender_operations_manager');

        $wallet                 = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($this->getUser()->getClientId(), WalletType::LENDER);
        $filters                = $this->getOperationFilters($request);
        $operations             = $lenderOperationsManager->getOperationsAccordingToFilter($filters['operation']);
        $lenderOperations       = $lenderOperationsManager->getLenderOperations($wallet, $filters['startDate'], $filters['endDate'], $filters['project'], $operations);
        $projectsFundedByLender = array_combine(array_column($lenderOperations, 'id_project'), array_column($lenderOperations, 'title'));

        return $this->json(
            [
                'target'   => 'operations',
                'template' => $this->render('/pages/lender_operations/my_operations.html.twig',
                    [
                        'clientId'               => $this->getUser()->getClientId(),
                        'hash'                   => $this->getUser()->getHash(),
                        'projectsFundedByLender' => $projectsFundedByLender,
                        'lenderOperations'       => $lenderOperations,
                        'currentFilters'         => $filters
                    ])->getContent()
            ]
        );
    }

    /**
     * @Route("/operations/exportOperationsCsv", name="export_operations_csv")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @return Response
     */
    public function exportOperationsCsvAction()
    {
        /** @var SessionInterface $session */
        $session = $this->get('session');

        if (false === $session->has('lenderOperationsFilters')) {
            return $this->redirectToRoute('lender_operations');
        }

        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var LenderOperationsManager $lenderOperationsManager */
        $lenderOperationsManager = $this->get('unilend.service.lender_operations_manager');

        $wallet     = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($this->getUser()->getClientId(), WalletType::LENDER);
        $filters    = $session->get('lenderOperationsFilters');
        $operations = $lenderOperationsManager->getOperationsAccordingToFilter($filters['operation']);
        $document   = $lenderOperationsManager->getOperationsExcelFile($wallet, $filters['startDate'], $filters['endDate'], $filters['project'], $operations);
        $fileName   = 'operations_' . date('Y-m-d_H:i:s');

        /** @var \PHPExcel_Writer_Excel2007 $writer */
        $writer = PHPExcel_IOFactory::createWriter($document, 'Excel2007');

        return new StreamedResponse(
            function () use ($writer) {
                $writer->save('php://output');
            }, Response::HTTP_OK, [
            'Content-type'        => 'application/force-download; charset=utf-8',
            'Expires'             => 0,
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Content-Disposition' => 'attachment;filename=' . $fileName . '.xlsx'
        ]);
    }

    /**
     * @Route("/operations/exportLoansCsv", name="export_loans_csv")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function exportLoansCsvAction(Request $request)
    {
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $wallet        = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($this->getUser()->getClientId(), WalletType::LENDER);
        /** @var \echeanciers $repaymentSchedule */
        $repaymentSchedule = $this->get('unilend.service.entity_manager')->getRepository('echeanciers');
        $loans             = $this->commonLoans($request, $wallet);

        \PHPExcel_Settings::setCacheStorageMethod(
            \PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp,
            ['memoryCacheSize' => '2048MB', 'cacheTime' => 1200]
        );

        $oDocument    = new \PHPExcel();
        $oActiveSheet = $oDocument->setActiveSheetIndex(0);

        $oActiveSheet->setCellValue('A1', 'Projet');
        $oActiveSheet->setCellValue('B1', 'Numéro de projet');
        $oActiveSheet->setCellValue('C1', 'Montant');
        $oActiveSheet->setCellValue('D1', 'Statut');
        $oActiveSheet->setCellValue('E1', 'Taux d\'intérêts');
        $oActiveSheet->setCellValue('F1', 'Premier remboursement');
        $oActiveSheet->setCellValue('G1', 'Prochain remboursement prévu');
        $oActiveSheet->setCellValue('H1', 'Date dernier remboursement');
        $oActiveSheet->setCellValue('I1', 'Capital perçu');
        $oActiveSheet->setCellValue('J1', 'Intérêts perçus');
        $oActiveSheet->setCellValue('K1', 'Capital restant dû');
        $oActiveSheet->setCellValue('L1', 'Note');

        foreach ($loans['lenderLoans'] as $iRowIndex => $aProjectLoans) {
            $oActiveSheet->setCellValue('A' . ($iRowIndex + 2), $aProjectLoans['name']);
            $oActiveSheet->setCellValue('B' . ($iRowIndex + 2), $aProjectLoans['id']);
            $oActiveSheet->setCellValue('C' . ($iRowIndex + 2), $aProjectLoans['amount']);
            $oActiveSheet->setCellValue('D' . ($iRowIndex + 2), $aProjectLoans['loanStatusLabel']);
            $oActiveSheet->setCellValue('E' . ($iRowIndex + 2), round($aProjectLoans['rate'], 1));
            $oActiveSheet->setCellValue('F' . ($iRowIndex + 2), date('d/m/Y', strtotime($aProjectLoans['start_date'])));

            switch ($aProjectLoans['loanStatus']) {
                case LenderOperationsManager::LOAN_STATUS_DISPLAY_COMPLETED:
                    $oActiveSheet->mergeCells('G' . ($iRowIndex + 2) . ':K' . ($iRowIndex + 2));
                    $oActiveSheet->setCellValue(
                        'G' . ($iRowIndex + 2),
                        $this->get('translator')->trans(
                            'lender-operations_loans-table-project-status-label-repayment-finished-on-date',
                            ['%date%' => \DateTime::createFromFormat('Y-m-d H:i:s', $aProjectLoans['final_repayment_date'])->format('d/m/Y')]
                        )
                    );
                    break;
                case LenderOperationsManager::LOAN_STATUS_DISPLAY_PROCEEDING:
                case LenderOperationsManager::LOANS_STATUS_DISPLAY_AMICABLE_DC:
                case LenderOperationsManager::LOANS_STATUS_DISPLAY_LITIGATION_DC:
                    $oActiveSheet->mergeCells('G' . ($iRowIndex + 2) . ':K' . ($iRowIndex + 2));
                    $oActiveSheet->setCellValue(
                        'G' . ($iRowIndex + 2),
                        $this->get('translator')->transChoice(
                            'lender-operations_loans-table-project-procedure-in-progress',
                            $aProjectLoans['count']['declaration']
                        )
                    );
                    break;
                default:
                    $oActiveSheet->setCellValue('G' . ($iRowIndex + 2), date('d/m/Y', strtotime($aProjectLoans['next_payment_date'])));
                    $oActiveSheet->setCellValue('H' . ($iRowIndex + 2), date('d/m/Y', strtotime($aProjectLoans['end_date'])));
                    $oActiveSheet->setCellValue('I' . ($iRowIndex + 2), $repaymentSchedule->getRepaidCapital(['id_lender' => $wallet->getId(), 'id_project' => $aProjectLoans['id']]));
                    $oActiveSheet->setCellValue('J' . ($iRowIndex + 2), $repaymentSchedule->getRepaidInterests(['id_lender' => $wallet->getId(), 'id_project' => $aProjectLoans['id']]));
                    $oActiveSheet->setCellValue('K' . ($iRowIndex + 2), $repaymentSchedule->getOwedCapital(['id_lender' => $wallet->getId(), 'id_project' => $aProjectLoans['id']]));
                    break;
            }
            $sRisk = isset($aProjectLoans['risk']) ? $aProjectLoans['risk'] : '';
            $sNote = $this->getProjectNote($sRisk);
            $oActiveSheet->setCellValue('L' . ($iRowIndex + 2), $sNote);
        }

        /** @var \PHPExcel_Writer_Excel5 $oWriter */
        $oWriter = \PHPExcel_IOFactory::createWriter($oDocument, 'Excel5');
        ob_start();
        $oWriter->save('php://output');
        $content = ob_get_clean();

        return new Response($content, Response::HTTP_OK, [
            'Content-type'        => 'application/force-download; charset=utf-8',
            'Expires'             => 0,
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'content-disposition' => "attachment;filename=" . 'prets_' . date('Y-m-d_H:i:s') . ".xls"
        ]);
    }

    /**
     * @param string $sRisk a letter that gives the risk value [A-H]
     *
     * @return string
     */
    private function getProjectNote($sRisk)
    {
        switch ($sRisk) {
            case 'A':
                $sNote = '5';
                break;
            case 'B':
                $sNote = '4,5';
                break;
            case 'C':
                $sNote = '4';
                break;
            case 'D':
                $sNote = '3,5';
                break;
            case 'E':
                $sNote = '3';
                break;
            case 'F':
                $sNote = '2,5';
                break;
            case 'G':
                $sNote = '2';
                break;
            case 'H':
                $sNote = '1,5';
                break;
            default:
                $sNote = '';
        }
        return $sNote;
    }

    /**
     * @param Request $request
     * @param Wallet  $wallet
     *
     * @return array
     */
    private function commonLoans(Request $request, Wallet $wallet)
    {
        $entityManagerSimulator = $this->get('unilend.service.entity_manager');
        $entityManager          = $this->get('doctrine.orm.entity_manager');
        /** @var \loans $loan */
        $loan = $entityManagerSimulator->getRepository('loans');
        /** @var \projects $project */
        $project                 = $entityManagerSimulator->getRepository('projects');
        $notificationsRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Notifications');
        $lenderOperationManager  = $this->get('unilend.service.lender_operations_manager');
        $projectRepository       = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects');

        /** @var UserLender $user */
        $user               = $this->getUser();
        $projectsInDept     = $project->getProjectsInDebt();
        $filters            = $request->request->get('filter', []);
        $year               = isset($filters['date']) && false !== filter_var($filters['date'], FILTER_VALIDATE_INT) ? $filters['date'] : null;
        $statusFilter       = isset($filters['status']) ? $filters['status'] : null;
        $status             = in_array($statusFilter, array_keys(self::LOAN_STATUS_FILTER)) ? [self::LOAN_STATUS_FILTER[$statusFilter]] : null;
        $loanStatus         = array_fill_keys(array_keys(self::LOAN_STATUS_FILTER), 0);
        $lenderLoans        = $loan->getSumLoansByProject($wallet->getId(), 'debut ASC, p.title ASC', $year, $status);
        $lenderProjectLoans = [];

        foreach ($lenderLoans as $projectLoans) {
            if ($projectLoans['project_status'] >= ProjectsStatus::REMBOURSEMENT) {
                $loanData               = [];
                $projectEntity          = $projectRepository->find($projectLoans['id_project']);
                $loanStatusInfo = $lenderOperationManager->getLenderLoanStatusToDisplay($projectEntity);

                if (false === empty($statusFilter) && false === in_array($loanStatusInfo['status'], self::LOAN_STATUS_AGGREGATE[$statusFilter])) {
                    continue;
                }
                /** @var \DateTime $startDateTime */
                $startDateTime = new \DateTime(date('Y-m-d'));
                /** @var \DateTime $endDateTime */
                $endDateTime = new \DateTime($projectLoans['fin']);
                /** @var \DateInterval $remainingDuration */
                $remainingDuration = $startDateTime->diff($endDateTime);

                $loanData['id']                       = $projectLoans['id_project'];
                $loanData['url']                      = $this->generateUrl('project_detail', ['projectSlug' => $projectLoans['slug']]);
                $loanData['name']                     = $projectLoans['title'];
                $loanData['rate']                     = round($projectLoans['rate'], 1);
                $loanData['risk']                     = $projectLoans['risk'];
                $loanData['amount']                   = round($projectLoans['amount']);
                $loanData['start_date']               = $projectLoans['debut'];
                $loanData['end_date']                 = $projectLoans['fin'];
                $loanData['next_payment_date']        = $projectLoans['next_echeance'];
                $loanData['monthly_repayment_amount'] = $projectLoans['monthly_repayment_amount'];
                $loanData['duration']                 = $remainingDuration->y * 12 + $remainingDuration->m + ($remainingDuration->d > 0 ? 1 : 0);
                $loanData['final_repayment_date']     = $projectLoans['final_repayment_date'];
                $loanData['remaining_capital_amount'] = $projectLoans['remaining_capital'];
                $loanData['project_status']           = $projectLoans['project_status'];
                $loanData['loanStatus']               = $loanStatusInfo['status'];
                $loanData['loanStatusLabel']          = $loanStatusInfo['statusLabel'];

                switch ($loanData['loanStatus']) {
                    case LenderOperationsManager::LOAN_STATUS_DISPLAY_PROCEEDING:
                    case LenderOperationsManager::LOANS_STATUS_DISPLAY_LITIGATION_DC:
                    case LenderOperationsManager::LOANS_STATUS_DISPLAY_AMICABLE_DC:
                        ++$loanStatus['incidents'];
                        break;
                    case LenderOperationsManager::LOAN_STATUS_DISPLAY_LATE:
                        ++$loanStatus['late-repayment'];
                        break;
                    case LenderOperationsManager::LOAN_STATUS_DISPLAY_COMPLETED:
                        ++$loanStatus['repaid'];
                        break;
                    case LenderOperationsManager::LOAN_STATUS_DISPLAY_IN_PROGRESS:
                        ++$loanStatus['repayment'];
                        break;
                    case LenderOperationsManager::LOAN_STATUS_DISPLAY_LOSS:
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

                $projectLoansDetails = $entityManager->getRepository('UnilendCoreBusinessBundle:Loans')
                    ->findBy([
                        'idLender'  => $wallet->getId(),
                        'idProject' => $projectEntity
                    ]);
                $loans               = [];
                $loanData['count']   = [
                    'bond'        => 0,
                    'contract'    => 0,
                    'declaration' => 0
                ];
                /** @var Loans $partialLoan */
                foreach ($projectLoansDetails as $partialLoan) {
                    (1 == $partialLoan->getIdTypeContract()->getIdContract()) ? $loanData['count']['bond']++ : $loanData['count']['contract']++;

                    $loans[] = [
                        'rate'      => round($partialLoan->getRate(), 1),
                        'amount'    => bcdiv($partialLoan->getAmount(), 100, 0),
                        'documents' => $this->getDocumentDetail(
                            $projectLoans['project_status'],
                            $user->getHash(),
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
            'late-repayment' => '#5FC4D0',
            'incidents'      => '#F2980C',
            'repaid'         => '#1B88DB',
            'repayment'      => '#428890',
            'loss'           => '#787679'
        ];

        foreach ($loanStatus as $status => $count) {
            if ($count) {
                $seriesData[] = [
                    'name'         => $this->get('translator')->transChoice('lender-operations_loans-chart-legend-loan-status-' . $status, $count, ['%count%' => $count]),
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
    private function getDocumentDetail($projectStatus, $hash, $loanId, UnderlyingContract $contract, array $projectsInDept, $projectId, &$nbDeclarations = 0)
    {
        $documents = [];

        if ($projectStatus >= \projects_status::REMBOURSEMENT) {
            $documents[] = [
                'url'   => $this->get('assets.packages')->getUrl('') . '/pdf/contrat/' . $hash . '/' . $loanId,
                'label' => $this->get('translator')->trans('contract-type-label_' . $contract->getLabel()),
                'type'  => 'bond'
            ];
        }

        if (in_array($projectId, $projectsInDept)) {
            $nbDeclarations++;
            $documents[] = [
                'url'   => $this->get('assets.packages')->getUrl('') . '/pdf/declaration_de_creances/' . $hash . '/' . $loanId,
                'label' => $this->get('translator')->trans('lender-operations_loans-table-declaration-of-debt-doc-tooltip'),
                'type'  => 'declaration'
            ];
        }
        return $documents;
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    private function getOperationFilters(Request $request)
    {
        $defaultValues = [
            'start'          => date('d/m/Y', strtotime('-1 month')),
            'end'            => date('d/m/Y'),
            'slide'          => 1,
            'year'           => date('Y'),
            'operation'      => 1,
            'project'        => null,
            'id_last_action' => 'operation'
        ];

        if ($request->request->get('filter')) {
            $filters    = $request->request->get('filter');
            $start      = isset($filters['start']) && 1 === preg_match('#^[0-9]{2}/[0-9]{2}/[0-9]{4}$#', $filters['start']) ? $filters['start'] : $defaultValues['start'];
            $end        = isset($filters['end']) && 1 === preg_match('#^[0-9]{2}/[0-9]{2}/[0-9]{4}$#', $filters['end']) ? $filters['end'] : $defaultValues['end'];
            $slide      = isset($filters['slide']) && in_array($filters['slide'], [1, 3, 6, 12]) ? $filters['slide'] : $defaultValues['slide'];
            $year       = isset($filters['year']) && false !== filter_var($filters['year'], FILTER_VALIDATE_INT) ? $filters['year'] : $defaultValues['year'];
            $operation  = isset($filters['operation']) && array_key_exists($filters['operation'], LenderOperationsManager::ALL_TYPES) ? $filters['operation'] : $defaultValues['operation'];
            $project    = isset($filters['project']) && false !== filter_var($filters['project'], FILTER_VALIDATE_INT) ? $filters['project'] : $defaultValues['project'];
            $lastAction = isset($filters['id_last_action']) ? $filters['id_last_action'] : $defaultValues['id_last_action'];
            $filters    = [
                'start'          => $start,
                'end'            => $end,
                'slide'          => $slide,
                'year'           => $year,
                'operation'      => $operation,
                'project'        => $project,
                'id_last_action' => $lastAction
            ];
        } elseif ($request->getSession()->has('lenderOperationsFilters')) {
            $filters = $request->getSession()->get('lenderOperationsFilters');
        } else {
            $filters = $defaultValues;
        }

        switch ($filters['id_last_action']) {
            default:
            case 'start':
            case 'end':
                $filters['startDate'] = \DateTime::createFromFormat('d/m/Y', $filters['start']);
                $filters['endDate']   = \DateTime::createFromFormat('d/m/Y', $filters['end']);
                break;
            case 'slide':
                if (empty($filters['slide'])) {
                    $filters['slide'] = 1;
                }

                $filters['startDate'] = (new \DateTime('NOW'))->sub(new \DateInterval('P' . $filters['slide'] . 'M'));
                $filters['endDate']   = new \DateTime('NOW');
                break;
            case 'year':
                $filters['startDate'] = new \DateTime('first day of January ' . $filters['year']);
                $filters['endDate']   = new \DateTime('last day of December ' . $filters['year']);
                break;
        }

        $filters['id_client'] = $this->getUser()->getClientId();
        $filters['start']     = $filters['startDate']->format('d/m/Y');
        $filters['end']       = $filters['endDate']->format('d/m/Y');

        /** @var SessionInterface $session */
        $session = $request->getSession();
        $session->set('lenderOperationsFilters', $filters);

        unset($filters['id_last_action']);

        return $filters;
    }

    /**
     * @Route("/operations/projectNotifications/{projectId}", name="lender_loans_notifications", requirements={"projectId": "\d+"})
     * @Security("has_role('ROLE_LENDER')")
     * @Method("GET")
     *
     * @param int $projectId
     *
     * @return Response
     */
    public function loadProjectNotificationsAction($projectId)
    {
        try {
            $data = $this->getProjectInformation($projectId);
            $code = Response::HTTP_OK;
        } catch (\Exception $exception) {
            $data = [];
            $code = Response::HTTP_INTERNAL_SERVER_ERROR;
            $this->get('logger')->error('Exception while getting client notifications for id_project: ' . $projectId . ' Message: ' . $exception->getMessage(),
                ['id_client' => $this->getUser()->getClientId(), 'class' => __CLASS__, 'function' => __FUNCTION__]);
        }

        return new JsonResponse(
            [
                'tpl' => $this->renderView(':frontbundle/pages/lender_operations:my_loans_details_activity.html.twig', ['projectNotifications' => $data, 'code' => $code]),
            ],
            $code
        );
    }

    /**
     * @param int $projectId
     *
     * @return array
     */
    private function getProjectInformation($projectId)
    {
        $entityManager   = $this->get('doctrine.orm.entity_manager');
        $translator      = $this->get('translator');
        $numberFormatter = $this->get('number_formatter');

        $operationRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Operation');

        /** @var Projects $project */
        $project = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($projectId);
        /** @var Wallet $wallet */
        $wallet = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($this->getUser()->getClientId(), WalletType::LENDER);

        $data = [];

        $companyStatusHistoryContent = $entityManager->getRepository('UnilendCoreBusinessBundle:CompanyStatusHistory')
            ->getNotificationContent($project->getIdCompany());
        foreach ($companyStatusHistoryContent as $content) {
            $titleAndContent = $this->getProjectStatusTitleAndContent($content, $project, $translator);
            $data[]          = [
                'id'        => count($data),
                'projectId' => $projectId,
                'type'      => 'account',
                'image'     => 'account',
                'title'     => $titleAndContent['title'],
                'content'   => $titleAndContent['content'],
                'datetime'  => $content['added'],
                'iso-8601'  => $content['added']->format('c'),
                'status'    => 'read'
            ];
        }

        $projectNotifications = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectNotification')
            ->findBy(['idProject' => $project->getIdProject()], ['notificationDate' => 'DESC']);
        foreach ($projectNotifications as $projectNotification) {
            $data[] = [
                'id'        => count($data),
                'projectId' => $projectNotification->getIdProject()->getIdProject(),
                'image'     => 'information',
                'type'      => 'account',
                'title'     => $projectNotification->getSubject(),
                'datetime'  => $projectNotification->getNotificationDate(),
                'iso-8601'  => $projectNotification->getNotificationDate()->format('c'),
                'content'   => $projectNotification->getContent(),
                'status'    => 'read'
            ];
        }

        $capitalEarlyRepaymentType = $entityManager->getRepository('UnilendCoreBusinessBundle:OperationSubType')->findOneBy(['label' => OperationSubType::CAPITAL_REPAYMENT_EARLY]);
        /** @var Operation[] $earlyRepayments */
        $earlyRepayments = $operationRepository->findBy([
            'idProject'        => $project,
            'idWalletCreditor' => $wallet,
            'idSubType'        => $capitalEarlyRepaymentType
        ]);
        foreach ($earlyRepayments as $repayment) {
            $data[] = [
                'id'        => count($data),
                'projectId' => $projectId,
                'image'     => 'remboursement',
                'type'      => 'remboursement',
                'title'     => $translator->trans('lender-notifications_early-repayment-title'),
                'content'   => $translator->trans('lender-notifications_early-repayment-content', [
                    '%amount%'     => $numberFormatter->format($repayment->getAmount()),
                    '%projectUrl%' => $this->generateUrl('project_detail', ['projectSlug' => $project->getSlug()]),
                    '%company%'    => $project->getIdCompany()->getName()
                ]),
                'datetime'  => $repayment->getAdded(),
                'iso-8601'  => $repayment->getAdded()->format('c'),
                'status'    => 'read'
            ];
        }

        $capitalDebtCollectionRepaymentType = $entityManager->getRepository('UnilendCoreBusinessBundle:OperationSubType')->findOneBy(['label' => OperationSubType::CAPITAL_REPAYMENT_DEBT_COLLECTION]);
        /** @var Operation[] $debtCollectionRepayments */
        $debtCollectionRepayments = $operationRepository->findBy([
            'idProject'        => $project,
            'idWalletCreditor' => $wallet,
            'idSubType'        => $capitalDebtCollectionRepaymentType
        ]);
        foreach ($debtCollectionRepayments as $repayment) {
            $data[] = [
                'id'        => count($data),
                'projectId' => $projectId,
                'image'     => 'remboursement',
                'type'      => 'remboursement',
                'title'     => $translator->trans('lender-notifications_recovery-repayment-title'),
                'content'   => $translator->trans('lender-notifications_recovery-repayment-content', [
                    '%amount%'     => $numberFormatter->format($repayment->getAmount()),
                    '%projectUrl%' => $this->generateUrl('project_detail', ['projectSlug' => $project->getSlug()]),
                    '%company%'    => $project->getIdCompany()->getName()
                ]),
                'datetime'  => $repayment->getAdded(),
                'iso-8601'  => $repayment->getAdded()->format('c'),
                'status'    => 'read'
            ];
        }

        $capitalRepaymentType = $entityManager->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::CAPITAL_REPAYMENT]);
        /** @var Operation[] $scheduledRepayments */
        $scheduledRepayments = $operationRepository->findBy([
            'idProject'        => $project,
            'idWalletCreditor' => $wallet,
            'idType'           => $capitalRepaymentType,
            'idSubType'        => null
        ]);
        foreach ($scheduledRepayments as $repayment) {

            $title   = $translator->trans('lender-notifications_repayment-title');
            $content = $translator->trans('lender-notifications_repayment-content', [
                '%amount%'     => $numberFormatter->format($operationRepository->getNetAmountByRepaymentScheduleId($repayment->getRepaymentSchedule())),
                '%projectUrl%' => $this->generateUrl('project_detail', ['projectSlug' => $project->getSlug()]),
                '%company%'    => $project->getIdCompany()->getName()
            ]);

            $data[] = [
                'id'        => count($data),
                'projectId' => $projectId,
                'image'     => 'remboursement',
                'type'      => 'remboursement',
                'title'     => $title,
                'content'   => $content,
                'datetime'  => $repayment->getAdded(),
                'iso-8601'  => $repayment->getAdded()->format('c'),
                'status'    => 'read'
            ];
        }

        $capitalRepaymentRegularizationType = $entityManager->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::CAPITAL_REPAYMENT_REGULARIZATION]);
        /** @var Operation[] $regularizedRepayments */
        $regularizedRepayments = $operationRepository->findBy([
            'idProject'      => $project,
            'idWalletDebtor' => $wallet,
            'idType'         => $capitalRepaymentRegularizationType,
            'idSubType'      => null
        ]);

        foreach ($regularizedRepayments as $repayment) {

            $title   = $translator->trans('lender-notifications_repayment-regularization-title');
            $content = $translator->trans('lender-notifications_repayment-regularization-content', [
                '%amount%'     => $numberFormatter->format($operationRepository->getNetAmountByRepaymentScheduleId($repayment->getRepaymentSchedule())),
                '%projectUrl%' => $this->generateUrl('project_detail', ['projectSlug' => $project->getSlug()]),
                '%company%'    => $project->getIdCompany()->getName()
            ]);

            $data[] = [
                'id'        => count($data),
                'projectId' => $projectId,
                'image'     => 'remboursement',
                'type'      => 'remboursement',
                'title'     => $title,
                'content'   => $content,
                'datetime'  => $repayment->getAdded(),
                'iso-8601'  => $repayment->getAdded()->format('c'),
                'status'    => 'read'
            ];
        }

        $acceptedLoansNotifications = $entityManager->getRepository('UnilendCoreBusinessBundle:Notifications')
            ->findBy(['idProject' => $projectId, 'idLender' => $wallet, 'type' => Notifications::TYPE_LOAN_ACCEPTED]);
        $bidsEntity                 = $entityManager->getRepository('UnilendCoreBusinessBundle:Bids');
        $acceptedBidEntity          = $entityManager->getRepository('UnilendCoreBusinessBundle:AcceptedBids');
        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');

        foreach ($acceptedLoansNotifications as $notification) {
            $title             = $translator->trans('lender-notifications_accepted-loan-title');
            $bid               = $bidsEntity->find($notification->getIdBid());
            $acceptedBids      = $acceptedBidEntity->findBy(['idBid' => $bid->getIdBid()]);
            $acceptedBidAmount = 0;
            foreach ($acceptedBids as $acceptedBid) {
                $acceptedBidAmount += $acceptedBid->getAmount();
            }
            $content = $translator->trans('lender-notifications_accepted-loan-content', [
                '%rate%'       => $ficelle->formatNumber($bid->getRate(), 1),
                '%amount%'     => $ficelle->formatNumber(bcdiv($acceptedBidAmount, 100), 0),
                '%projectUrl%' => $this->generateUrl('project_detail', ['projectSlug' => $project->getSlug()]),
                '%company%'    => $project->getIdCompany()->getName()
            ]);

            $data[] = [
                'id'        => $notification->getIdNotification(),
                'projectId' => $projectId,
                'image'     => 'offer-accepted',
                'type'      => 'offer-accepted',
                'title'     => $title,
                'content'   => $content,
                'datetime'  => $notification->getAdded(),
                'iso-8601'  => $notification->getAdded()->format('c'),
                'status'    => $notification->getStatus() == Notifications::STATUS_READ ? 'read' : 'unread'
            ];

            break;
        }

        if (false === empty($data)) {
            usort($data, [$this, 'sortArrayByDate']);
        }

        foreach ($data as $index => $row) {
            $data[$index]['status'] = ($row['datetime'] > $this->getUser()->getLastLoginDate()) ? 'unread' : 'read';
        }
        return $data;
    }

    /**
     * @param array               $content
     * @param Projects            $project
     * @param TranslatorInterface $translator
     *
     * @return array
     */
    private function getProjectStatusTitleAndContent(array $content, Projects $project, TranslatorInterface $translator)
    {
        switch ($content['label']) {
            case CompanyStatus::STATUS_PRECAUTIONARY_PROCESS:
                $title   = $translator->trans('lender-notifications_precautionary-process-title');
                $content = (false === empty($content['siteContent'])) ? $content['siteContent'] :
                    $translator->trans('lender-notifications_precautionary-process-content', [
                        '%projectUrl%' => $this->generateUrl('project_detail', ['projectSlug' => $project->getSlug()]) . '#project-section-info',
                        '%company%'    => $project->getIdCompany()->getName()
                    ]);
                break;
            case CompanyStatus::STATUS_RECEIVERSHIP:
                $title   = $translator->trans('lender-notifications_receivership-title');
                $content = (false === empty($content['siteContent'])) ? $content['siteContent'] :
                    $translator->trans('lender-notifications_receivership-content', [
                        '%projectUrl%' => $this->generateUrl('project_detail', ['projectSlug' => $project->getSlug()]) . '#project-section-info',
                        '%company%'    => $project->getIdCompany()->getName()
                    ]);
                break;
            case CompanyStatus::STATUS_COMPULSORY_LIQUIDATION:
                $title   = $translator->trans('lender-notifications_compulsory-liquidation-title');
                $content = (false === empty($content['siteContent'])) ? $content['siteContent'] :
                    $translator->trans('lender-notifications_compulsory-liquidation-content', [
                        '%projectUrl%' => $this->generateUrl('project_detail', ['projectSlug' => $project->getSlug()]) . '#project-section-info',
                        '%company%'    => $project->getIdCompany()->getName()
                    ]);
                break;
            default:
                $title   = $content['label'];
                $content = '';
                break;
        }

        return ['title' => $title, 'content' => $content];
    }

    /**
     * @param array $a
     * @param array $b
     *
     * @return mixed
     */
    private function sortArrayByDate(array $a, array $b)
    {
        return $b['datetime']->getTimestamp() - $a['datetime']->getTimestamp();
    }

    /**
     * @Route("/operations/pdf", name="lender_operations_pdf")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @return Response
     */
    public function downloadOperationPDF()
    {
        /** @var SessionInterface $session */
        $session = $this->get('session');

        if (false === $session->has('lenderOperationsFilters')) {
            return $this->redirectToRoute('lender_operations');
        }

        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var LenderOperationsManager $lenderOperationsManager */
        $lenderOperationsManager = $this->get('unilend.service.lender_operations_manager');

        $wallet        = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($this->getUser()->getClientId(), WalletType::LENDER);
        $clientAddress = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientsAdresses')->findOneBy(['idClient' => $wallet->getIdClient()->getIdClient()]);
        if (false === $wallet->getIdClient()->isNaturalPerson()) {
            $company = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findOneBy(['idClientOwner' => $wallet->getIdClient()->getIdClient()]);
        }

        $filters          = $session->get('lenderOperationsFilters');
        $operations       = $lenderOperationsManager->getOperationsAccordingToFilter($filters['operation']);
        $lenderOperations = $lenderOperationsManager->getLenderOperations($wallet, $filters['startDate'], $filters['endDate'], $filters['project'], $operations);
        $fileName         = 'vos_operations_' . date('Y-m-d') . '.pdf';
        $pdfContent       = $this->renderView('pdf/lender_operations.html.twig', [
            'lenderOperations'  => $lenderOperations,
            'client'            => $wallet->getIdClient(),
            'clientAddress'     => $clientAddress,
            'company'           => empty($company) ? null : $company,
            'available_balance' => $wallet->getAvailableBalance()
        ]);


        /** @var GeneratorInterface $snappy */
        $snappy = $this->get('knp_snappy.pdf');

        return new Response(
            $snappy->getOutputFromHtml($pdfContent),
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => sprintf('attachment; filename="%s"', $fileName)
            ]
        );
    }
}
