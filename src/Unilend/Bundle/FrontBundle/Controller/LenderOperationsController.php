<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Doctrine\ORM\EntityManager;
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
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
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
        'repayment'      => [\projects_status::REMBOURSEMENT],
        'repaid'         => [\projects_status::REMBOURSE, \projects_status::REMBOURSEMENT_ANTICIPE],
        'late-repayment' => [\projects_status::PROBLEME, \projects_status::PROBLEME_J_X],
        'problem'        => [\projects_status::RECOUVREMENT, \projects_status::PROCEDURE_SAUVEGARDE, \projects_status::REDRESSEMENT_JUDICIAIRE, \projects_status::LIQUIDATION_JUDICIAIRE, \projects_status::DEFAUT]
    ];

    /**
     * @Route("/operations", name="lender_operations")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param Request $request
     * @return Response
     */
    public function indexAction(Request $request)
    {
        /** @var EntityManagerSimulator $entityManagerSimulator */
        $entityManagerSimulator = $this->get('unilend.service.entity_manager');
        /** @var \projects_status $projectStatus */
        $projectStatus = $entityManagerSimulator->getRepository('projects_status');

        /** @var EntityManager $entityManager */
        $entityManager= $this->get('doctrine.orm.entity_manager');
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
     * @param Request $request
     * @return JsonResponse
     * @Route("/operations/filterLoans", name="filter_loans")
     * @Security("has_role('ROLE_LENDER')")
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
     * @param Request $request
     * @return JsonResponse
     * @Route("/operations/filterOperations", name="filter_operations")
     * @Security("has_role('ROLE_LENDER')")
     */
    public function filterOperationsAction(Request $request)
    {
        /** @var EntityManager $entityManager */
        $entityManager= $this->get('doctrine.orm.entity_manager');
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
     * @return Response
     * @Route("/operations/exportOperationsCsv", name="export_operations_csv")
     * @Security("has_role('ROLE_LENDER')")
     */
    public function exportOperationsCsvAction()
    {
        /** @var SessionInterface $session */
        $session = $this->get('session');

        if (false === $session->has('lenderOperationsFilters')) {
            return $this->redirectToRoute('lender_operations');
        }

        /** @var EntityManager $entityManager */
        $entityManager= $this->get('doctrine.orm.entity_manager');
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
     * @param Request $request
     * @return Response
     * @Route("/operations/exportLoansCsv", name="export_loans_csv")
     * @Security("has_role('ROLE_LENDER')")
     */
    public function exportLoansCsvAction(Request $request)
    {
        /** @var EntityManager $entityManager */
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
            $oActiveSheet->setCellValue('D' . ($iRowIndex + 2), $this->get('translator')->trans('lender-operations_project-status-label-' . $aProjectLoans['project_status']));
            $oActiveSheet->setCellValue('E' . ($iRowIndex + 2), round($aProjectLoans['rate'], 1));
            $oActiveSheet->setCellValue('F' . ($iRowIndex + 2), date('d/m/Y', strtotime($aProjectLoans['start_date'])));

            if(in_array($aProjectLoans['project_status'], [ProjectsStatus::REMBOURSE, ProjectsStatus::REMBOURSEMENT_ANTICIPE])) {
                $oActiveSheet->mergeCells('G' . ($iRowIndex + 2) . ':K'. ($iRowIndex + 2));
                $oActiveSheet->setCellValue(
                    'G' . ($iRowIndex + 2),
                    $this->get('translator')->trans(
                        'lender-operations_loans-table-project-status-label-repayment-finished-on-date',
                        ['%date%' => \DateTime::createFromFormat('Y-m-d H:i:s', $aProjectLoans['final_repayment_date'])->format('d/m/Y')]
                    )
                );
            } elseif (in_array($aProjectLoans['project_status'], [ProjectsStatus::PROCEDURE_SAUVEGARDE, ProjectsStatus::REDRESSEMENT_JUDICIAIRE, ProjectsStatus::LIQUIDATION_JUDICIAIRE, ProjectsStatus::RECOUVREMENT])) {
                $oActiveSheet->mergeCells('G' . ($iRowIndex + 2) . ':K'. ($iRowIndex + 2));
                $oActiveSheet->setCellValue(
                    'G' . ($iRowIndex + 2),
                    $this->get('translator')->transChoice(
                        'lender-operations_loans-table-project-procedure-in-progress',
                        $aProjectLoans['count']['declaration']
                    )
                );
            } else {
                $oActiveSheet->setCellValue('G' . ($iRowIndex + 2), date('d/m/Y', strtotime($aProjectLoans['next_payment_date'])));
                $oActiveSheet->setCellValue('H' . ($iRowIndex + 2), date('d/m/Y', strtotime($aProjectLoans['end_date'])));
                $oActiveSheet->setCellValue('I' . ($iRowIndex + 2), $repaymentSchedule->getRepaidCapital(['id_lender' => $wallet->getId(), 'id_project' => $aProjectLoans['id']]));
                $oActiveSheet->setCellValue('J' . ($iRowIndex + 2), $repaymentSchedule->getRepaidInterests(['id_lender' => $wallet->getId(), 'id_project' => $aProjectLoans['id']]));
                $oActiveSheet->setCellValue('K' . ($iRowIndex + 2), $repaymentSchedule->getOwedCapital(['id_lender' => $wallet->getId(), 'id_project' => $aProjectLoans['id']]));
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
     * @param Wallet $wallet
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
        $project = $entityManagerSimulator->getRepository('projects');
        $notificationsRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Notifications');

        $orderField     = $request->request->get('type', 'start');
        $orderDirection = strtoupper($request->request->get('order', 'ASC'));
        $orderDirection = in_array($orderDirection, ['ASC', 'DESC']) ? $orderDirection : 'ASC';

        switch ($orderField) {
            case 'status':
                $sOrderBy = 'p.status ' . $orderDirection . ', debut DESC, p.title ASC';
                break;
            case 'title':
                $sOrderBy = 'p.title ' . $orderDirection . ', debut DESC';
                break;
            case 'note':
                $sOrderBy = 'p.risk ' . $orderDirection . ', debut DESC, p.title ASC';
                break;
            case 'amount':
                $sOrderBy = 'amount ' . $orderDirection . ', debut DESC, p.title ASC';
                break;
            case 'interest':
                $sOrderBy = 'rate ' . $orderDirection . ', debut DESC, p.title ASC';
                break;
            case 'next':
                $sOrderBy = 'next_echeance ' . $orderDirection . ', debut DESC, p.title ASC';
                break;
            case 'end':
                $sOrderBy = 'fin ' . $orderDirection . ', debut DESC, p.title ASC';
                break;
            case 'repayment':
                $sOrderBy = 'last_perceived_repayment ' . $orderDirection . ', debut DESC, p.title ASC';
                break;
            case 'start':
            default:
                $sOrderBy = 'debut ' . $orderDirection . ', p.title ASC';
                break;
        }

        /** @var UserLender $user */
        $user               = $this->getUser();
        $projectsInDept     = $project->getProjectsInDebt();
        $filters            = $request->request->get('filter', []);
        $year               = isset($filters['date']) && false !== filter_var($filters['date'], FILTER_VALIDATE_INT) ? $filters['date'] : null;
        $status             = isset($filters['status']) && in_array($filters['status'], array_keys(self::LOAN_STATUS_FILTER)) ? self::LOAN_STATUS_FILTER[$filters['status']] : null;
        $loanStatus         = array_fill_keys(array_keys(self::LOAN_STATUS_FILTER), 0);
        $lenderLoans        = $loan->getSumLoansByProject($wallet->getId(), $sOrderBy, $year, $status);
        $lenderProjectLoans = [];

        foreach ($lenderLoans as $projectLoans) {
            $loanData = [];
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
            $loanData['duration']                 = $remainingDuration->y * 12 + $remainingDuration->m;
            $loanData['final_repayment_date']     = $projectLoans['final_repayment_date'];
            $loanData['remaining_capital_amount'] = $projectLoans['remaining_capital'];
            $loanData['project_status']           = $projectLoans['project_status'];

            switch ($projectLoans['project_status']) {
                case \projects_status::PROBLEME:
                case \projects_status::PROBLEME_J_X:
                    $loanData['status'] = 'late';
                    ++$loanStatus['late-repayment'];
                    break;
                case \projects_status::RECOUVREMENT:
                    $loanData['status'] = 'completing';
                    ++$loanStatus['problem'];
                    break;
                case \projects_status::PROCEDURE_SAUVEGARDE:
                case \projects_status::REDRESSEMENT_JUDICIAIRE:
                case \projects_status::LIQUIDATION_JUDICIAIRE:
                    $loanData['status'] = 'problem';
                    ++$loanStatus['problem'];
                    break;
                case \projects_status::DEFAUT:
                    $loanData['status'] = 'defaulted';
                    ++$loanStatus['problem'];
                    break;
                case \projects_status::REMBOURSE:
                case \projects_status::REMBOURSEMENT_ANTICIPE:
                    $loanData['status'] = 'completed';
                    ++$loanStatus['repaid'];
                    break;
                case \projects_status::REMBOURSEMENT:
                default:
                    $loanData['status'] = 'inprogress';
                    ++$loanStatus['repayment'];
                    break;
            }
            try {
                $loanData['activity'] = [
                    'unread_count' => $notificationsRepository->countUnreadNotificationsForClient($wallet->getId(), $projectLoans['id_project'])
                ];
            } catch (\Exception $exception) {
                unset($exception);
                $loanData['activity'] = [
                    'unread_count' => 0
                ];
            }

            $projectLoansDetails = $entityManager->getRepository('UnilendCoreBusinessBundle:Loans')
                ->findBy([
                    'idLender' => $wallet->getId(),
                    'idProject' => $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($projectLoans['id_project'])
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
                        $user->getHash(),
                        $partialLoan->getIdLoan(),
                        $partialLoan->getIdTypeContract()->getIdContract(),
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

        $seriesData  = [];
        $chartColors = [
            'late-repayment' => '#5FC4D0',
            'problem'        => '#F2980C',
            'repaid'         => '#1B88DB',
            'repayment'      => '#428890'
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
     * @param int $projectStatus
     * @param string $hash
     * @param int $loanId
     * @param int $docTypeId
     * @param array $projectsInDept
     * @param int $projectId
     * @param $nbDeclarations
     * @return array
     */
    private function getDocumentDetail($projectStatus, $hash, $loanId, $docTypeId, array $projectsInDept, $projectId, &$nbDeclarations = 0)
    {
        $documents = [];

        if ($projectStatus >= \projects_status::REMBOURSEMENT) {
            $documents[] = [
                'url'   => $this->get('assets.packages')->getUrl('') . '/pdf/contrat/' . $hash . '/' . $loanId,
                'label' => $this->get('translator')->trans('lender-operations_contract-type-' . $docTypeId),
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
            $code               = Response::HTTP_OK;
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
        $entityManager      = $this->get('doctrine.orm.entity_manager');
        $project            = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($projectId);
        $projectManager     = $this->get('unilend.service.project_manager');
        $projectInformation = $projectManager->getProjectEventsDetail($projectId, $this->getUser()->getClientId());
        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');

        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');
        $data       = [];

        foreach ($projectInformation['projectStatus'] as $projectStatus) {
            $titleAndContent = $this->getProjectStatusTitleAndContent($projectStatus, $project, $translator);
            $data[]          = [
                'id'        => count($data),
                'projectId' => $projectId,
                'type'      => 'account',
                'image'     => 'account',
                'title'     => $titleAndContent['title'],
                'content'   => $titleAndContent['content'],
                'datetime'  => $projectStatus['added'],
                'iso-8601'  => $projectStatus['added']->format('c'),
                'status'    => 'read'
            ];
        }
        foreach ($projectInformation['projectNotifications'] as $projectNotification) {
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
        foreach ($projectInformation['recoveryAndEarlyRepayments'] as $repayment) {
            $titleAndContent = $this->getRepaymentTitleAndContent($repayment, $project, $translator, $ficelle);

            if (empty($titleAndContent['content'])) {
                continue;
            }
            $data[] = [
                'id'        => count($data),
                'projectId' => $projectId,
                'image'     => 'remboursement',
                'type'      => 'remboursement',
                'title'     => $titleAndContent['title'],
                'content'   => $titleAndContent['content'],
                'datetime'  => $repayment['dateTransaction'],
                'iso-8601'  => $repayment['dateTransaction']->format('c'),
                'status'    => 'read'
            ];
        }
        foreach ($projectInformation['scheduledRepayments'] as $repayment) {
            $titleAndContent = $this->getRepaymentTitleAndContent($repayment, $project, $translator, $ficelle);

            if (empty($titleAndContent['content'])) {
                continue;
            }
            $data[] = [
                'id'        => count($data),
                'projectId' => $projectId,
                'image'     => 'remboursement',
                'type'      => 'remboursement',
                'title'     => $titleAndContent['title'],
                'content'   => $titleAndContent['content'],
                'datetime'  => $repayment['dateTransaction'],
                'iso-8601'  => $repayment['dateTransaction']->format('c'),
                'status'    => 'read'
            ];
        }

        if (false === empty($data)) {
            usort($data, [$this, 'sortArrayByDate']);
        }

        foreach ($data as $index => $row) {
            $data[$index]['status'] = ($row['datetime']->format('Y-m-d H:i:s') > $this->getUser()->getLastLoginDate()) ? 'unread' : 'read';
        }
        return $data;
    }

    /**
     * @param array               $projectStatus
     * @param Projects            $project
     * @param TranslatorInterface $translator
     *
     * @return array
     */
    private function getProjectStatusTitleAndContent(array $projectStatus, Projects $project, TranslatorInterface $translator)
    {
        switch ($projectStatus['status']) {
            case ProjectsStatus::PROBLEME:
                $title   = $translator->trans('lender-notifications_late-repayment-title');
                $content = (false === empty($projectStatus['siteContent'])) ? $projectStatus['siteContent'] :
                    $translator->trans('lender-notifications_late-repayment-content', [
                        '%projectUrl%' => $this->generateUrl('project_detail', ['projectSlug' => $project->getSlug()]) . '#project-section-info',
                        '%company%'    => $project->getIdCompany()->getName()
                    ]);
                break;
            case ProjectsStatus::PROBLEME_J_X:
                $title   = $translator->trans('lender-notifications_late-repayment-x-days-title');
                $content = (false === empty($projectStatus['siteContent'])) ? $projectStatus['siteContent'] :
                    $translator->trans('lender-notifications_late-repayment-x-days-content', [
                        '%projectUrl%' => $this->generateUrl('project_detail', ['projectSlug' => $project->getSlug()]) . '#project-section-info',
                        '%company%'    => $project->getIdCompany()->getName()
                    ]);
                break;
            case ProjectsStatus::RECOUVREMENT:
                $title   = $translator->trans('lender-notifications_recovery-title');
                $content = (false === empty($projectStatus['siteContent'])) ? $projectStatus['siteContent'] :
                    $translator->trans('lender-notifications_recovery-content', [
                        '%projectUrl%' => $this->generateUrl('project_detail', ['projectSlug' => $project->getSlug()]) . '#project-section-info',
                        '%company%'    => $project->getIdCompany()->getName()
                    ]);
                break;
            case ProjectsStatus::PROCEDURE_SAUVEGARDE:
                $title   = $translator->trans('lender-notifications_precautionary-process-title');
                $content = (false === empty($projectStatus['siteContent'])) ? $projectStatus['siteContent'] :
                    $translator->trans('lender-notifications_precautionary-process-content', [
                        '%projectUrl%' => $this->generateUrl('project_detail', ['projectSlug' => $project->getSlug()]) . '#project-section-info',
                        '%company%'    => $project->getIdCompany()->getName()
                    ]);
                break;
            case ProjectsStatus::REDRESSEMENT_JUDICIAIRE:
                $title   = $translator->trans('lender-notifications_receivership-title');
                $content = (false === empty($projectStatus['siteContent'])) ? $projectStatus['siteContent'] :
                    $translator->trans('lender-notifications_receivership-content', [
                        '%projectUrl%' => $this->generateUrl('project_detail', ['projectSlug' => $project->getSlug()]) . '#project-section-info',
                        '%company%'    => $project->getIdCompany()->getName()
                    ]);
                break;
            case ProjectsStatus::LIQUIDATION_JUDICIAIRE:
                $title   = $translator->trans('lender-notifications_compulsory-liquidation-title');
                $content = (false === empty($projectStatus['siteContent'])) ? $projectStatus['siteContent'] :
                    $translator->trans('lender-notifications_compulsory-liquidation-content', [
                        '%projectUrl%' => $this->generateUrl('project_detail', ['projectSlug' => $project->getSlug()]) . '#project-section-info',
                        '%company%'    => $project->getIdCompany()->getName()
                    ]);
                break;
            case ProjectsStatus::DEFAUT:
                $title   = $translator->trans('lender-notifications_company-failure-title');
                $content = (false === empty($projectStatus['siteContent'])) ? $projectStatus['siteContent'] :
                    $translator->trans('lender-notifications_company-failure-content', [
                        '%projectUrl%' => $this->generateUrl('project_detail', ['projectSlug' => $project->getSlug()]) . '#project-section-info',
                        '%company%'    => $project->getIdCompany()->getName()
                    ]);
                break;
            default:
                $title   = $projectStatus['label'];
                $content = '';
                break;
        }

        return ['title' => $title, 'content' => $content];
    }

    /**
     * @param array               $repayment
     * @param Projects            $project
     * @param TranslatorInterface $translator
     * @param \ficelle            $ficelle
     * @return array
     */
    private function getRepaymentTitleAndContent(array $repayment, Projects $project, TranslatorInterface $translator, \ficelle $ficelle)
    {
        switch ($repayment['typeTransaction']) {
            case \transactions_types::TYPE_LENDER_ANTICIPATED_REPAYMENT:
                $title   = $translator->trans('lender-notifications_early-repayment-title');
                $content = $translator->trans('lender-notifications_early-repayment-content', [
                    '%amount%'     => $ficelle->formatNumber($repayment['amount'] / 100, 2),
                    '%projectUrl%' => $this->generateUrl('project_detail', ['projectSlug' => $project->getSlug()]),
                    '%company%'    => $project->getIdCompany()->getName()
                ]);
                break;
            case \transactions_types::TYPE_LENDER_RECOVERY_REPAYMENT:
                $title   = $translator->trans('lender-notifications_recovery-repayment-title');
                $content = $translator->trans('lender-notifications_recovery-repayment-content', [
                    '%amount%'     => $ficelle->formatNumber($repayment['amount'] / 100, 2),
                    '%projectUrl%' => $this->generateUrl('project_detail', ['projectSlug' => $project->getSlug()]),
                    '%company%'    => $project->getIdCompany()->getName()
                ]);
                break;
            case \transactions_types::TYPE_LENDER_REPAYMENT_INTERESTS:
            case \transactions_types::TYPE_LENDER_REPAYMENT_CAPITAL:
            $title   = $translator->trans('lender-notifications_repayment-title');
            $content = $translator->trans('lender-notifications_repayment-content', [
                '%amount%'     => $ficelle->formatNumber($repayment['amount'] / 100, 2),
                '%projectUrl%' => $this->generateUrl('project_detail', ['projectSlug' => $project->getSlug()]),
                '%company%'    => $project->getIdCompany()->getName()
            ]);
            break;
            default:
                $title   = '';
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
     * @param Request $request
     * @return Response
     */
    public function downloadOperationPDF(Request $request)
    {

        $lenderOperations = array ( 0 => array ( 'id' => '84658910', 'available_balance' => '11.38', 'committed_balance' => '40.00', 'amount' => '2.63', 'label' => 'repayment', 'id_project' => '69289', 'date' => '2017-04-01', 'operationDate' => '2017-04-01 11:08:06', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '281944', 'id_repayment_schedule' => '12516403', 'title' => 'Azapp', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '2.35', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.34', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.059999999999999998, ), ), ), ), 1 => array ( 'id' => '84615260', 'available_balance' => '8.75', 'committed_balance' => '40.00', 'amount' => '1.50', 'label' => 'repayment', 'id_project' => '35022', 'date' => '2017-04-01', 'operationDate' => '2017-04-01 11:04:05', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '124791', 'id_repayment_schedule' => '5615019', 'title' => 'Eléphants & Co', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '1.33', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.20', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.029999999999999999, ), ), ), ), 2 => array ( 'id' => '84497633', 'available_balance' => '7.25', 'committed_balance' => '40.00', 'amount' => '-40.00', 'label' => 'bid', 'id_project' => '75943', 'date' => '2017-03-30', 'operationDate' => '2017-03-30 15:22:19', 'id_bid' => '4878044', 'id_autobid' => NULL, 'id_loan' => '', 'id_repayment_schedule' => NULL, 'title' => 'HD Loc - 2', ), 3 => array ( 'id' => '84473582', 'available_balance' => '47.25', 'committed_balance' => '0.00', 'amount' => '5.95', 'label' => 'repayment', 'id_project' => '19', 'date' => '2017-03-30', 'operationDate' => '2017-03-30 11:14:10', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '656', 'id_repayment_schedule' => '31275', 'title' => 'Comelec', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '5.25', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.83', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.13, ), ), ), ), 4 => array ( 'id' => '84112175', 'available_balance' => '41.30', 'committed_balance' => '0.00', 'amount' => '3.42', 'label' => 'repayment', 'id_project' => '60452', 'date' => '2017-03-28', 'operationDate' => '2017-03-28 11:20:04', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '228733', 'id_repayment_schedule' => '10223467', 'title' => 'Financière de Pontarlier', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '3.29', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.15', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.02, ), ), ), ), 5 => array ( 'id' => '83783414', 'available_balance' => '37.88', 'committed_balance' => '0.00', 'amount' => '1.16', 'label' => 'repayment', 'id_project' => '71550', 'date' => '2017-03-27', 'operationDate' => '2017-03-27 11:14:12', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '294119', 'id_repayment_schedule' => '12888164', 'title' => 'Holding Hurtiger', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '0.82', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.40', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.059999999999999998, ), ), ), ), 6 => array ( 'id' => '83684141', 'available_balance' => '36.72', 'committed_balance' => '0.00', 'amount' => '0.95', 'label' => 'repayment', 'id_project' => '43851', 'date' => '2017-03-27', 'operationDate' => '2017-03-27 11:06:06', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '188373', 'id_repayment_schedule' => '8474775', 'title' => 'BDP Holding', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '0.72', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.27', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.040000000000000001, ), ), ), ), 7 => array ( 'id' => '83617822', 'available_balance' => '35.77', 'committed_balance' => '0.00', 'amount' => '2.32', 'label' => 'repayment', 'id_project' => '32093', 'date' => '2017-03-27', 'operationDate' => '2017-03-27 10:08:16', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '116317', 'id_repayment_schedule' => '5335570', 'title' => 'S.T.N.L', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '2.24', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.09', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.01, ), ), ), ), 8 => array ( 'id' => '83524864', 'available_balance' => '33.45', 'committed_balance' => '0.00', 'amount' => '3.97', 'label' => 'repayment', 'id_project' => '25956', 'date' => '2017-03-26', 'operationDate' => '2017-03-26 11:06:07', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '100890', 'id_repayment_schedule' => '4809804', 'title' => 'Kep Technologies Integrated Systems', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '3.75', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.26', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.040000000000000001, ), ), ), ), 9 => array ( 'id' => '83493940', 'available_balance' => '29.48', 'committed_balance' => '0.00', 'amount' => '0.90', 'label' => 'repayment', 'id_project' => '63182', 'date' => '2017-03-26', 'operationDate' => '2017-03-26 11:04:06', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '251602', 'id_repayment_schedule' => '11176510', 'title' => 'Prat Optic', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '0.77', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.15', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.02, ), ), ), ), 10 => array ( 'id' => '83318695', 'available_balance' => '28.58', 'committed_balance' => '0.00', 'amount' => '35.00', 'label' => 'lender_loan', 'id_project' => '74773', 'date' => '2017-03-23', 'operationDate' => '2017-03-23 14:08:12', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '313226', 'id_repayment_schedule' => NULL, 'title' => 'Finae Conseil', ), 11 => array ( 'id' => '82850888', 'available_balance' => '28.58', 'committed_balance' => '35.00', 'amount' => '10.19', 'label' => 'repayment', 'id_project' => '45044', 'date' => '2017-03-21', 'operationDate' => '2017-03-21 11:22:04', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '186357', 'id_repayment_schedule' => '8442807', 'title' => 'Pascal et Béatrix Décoration - 6', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '9.98', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.24', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.029999999999999999, ), ), ), ), 12 => array ( 'id' => '82795763', 'available_balance' => '18.39', 'committed_balance' => '35.00', 'amount' => '2.69', 'label' => 'repayment', 'id_project' => '57436', 'date' => '2017-03-21', 'operationDate' => '2017-03-21 11:17:13', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '215968', 'id_repayment_schedule' => '9706255', 'title' => 'Diet Plus Shop', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '2.31', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.45', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.070000000000000007, ), ), ), ), 13 => array ( 'id' => '82392740', 'available_balance' => '15.70', 'committed_balance' => '35.00', 'amount' => '1.20', 'label' => 'repayment', 'id_project' => '37121', 'date' => '2017-03-19', 'operationDate' => '2017-03-19 11:10:08', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '151725', 'id_repayment_schedule' => '6846519', 'title' => 'Air Vide Maintenance', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '1.04', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.19', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.029999999999999999, ), ), ), ), 14 => array ( 'id' => '82365848', 'available_balance' => '14.50', 'committed_balance' => '35.00', 'amount' => '1.51', 'label' => 'repayment', 'id_project' => '37779', 'date' => '2017-03-19', 'operationDate' => '2017-03-19 11:08:06', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '156489', 'id_repayment_schedule' => '6992589', 'title' => 'Becom', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '1.30', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.24', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.029999999999999999, ), ), ), ), 15 => array ( 'id' => '82338665', 'available_balance' => '12.99', 'committed_balance' => '35.00', 'amount' => '8.12', 'label' => 'repayment', 'id_project' => '4669', 'date' => '2017-03-19', 'operationDate' => '2017-03-19 11:05:09', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '36355', 'id_repayment_schedule' => '1908276', 'title' => 'Magunivers', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '7.54', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.67', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.089999999999999997, ), ), ), ), 16 => array ( 'id' => '82318691', 'available_balance' => '4.87', 'committed_balance' => '35.00', 'amount' => '1.49', 'label' => 'repayment', 'id_project' => '63304', 'date' => '2017-03-19', 'operationDate' => '2017-03-19 11:03:04', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '250129', 'id_repayment_schedule' => '11123482', 'title' => 'Dentaltech C.S.A.', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '1.27', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.26', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.040000000000000001, ), ), ), ), 17 => array ( 'id' => '82243532', 'available_balance' => '3.38', 'committed_balance' => '35.00', 'amount' => '1.20', 'label' => 'repayment', 'id_project' => '73288', 'date' => '2017-03-18', 'operationDate' => '2017-03-18 11:09:05', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '286162', 'id_repayment_schedule' => '12621031', 'title' => 'Ethik & Nature', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '1.01', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.22', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.029999999999999999, ), ), ), ), 18 => array ( 'id' => '82101839', 'available_balance' => '2.18', 'committed_balance' => '35.00', 'amount' => '-35.00', 'label' => 'bid', 'id_project' => '74773', 'date' => '2017-03-16', 'operationDate' => '2017-03-16 14:10:03', 'id_bid' => '4706612', 'id_autobid' => NULL, 'id_loan' => '', 'id_repayment_schedule' => NULL, 'title' => 'Finae Conseil', ), 19 => array ( 'id' => '82088321', 'available_balance' => '37.18', 'committed_balance' => '0.00', 'amount' => '8.16', 'label' => 'repayment', 'id_project' => '12123', 'date' => '2017-03-16', 'operationDate' => '2017-03-16 11:17:39', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '65887', 'id_repayment_schedule' => '3265543', 'title' => 'Pascal et Béatrix Décoration - 2', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '7.91', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.29', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.040000000000000001, ), ), ), ), 20 => array ( 'id' => '81863539', 'available_balance' => '29.02', 'committed_balance' => '0.00', 'amount' => '4.74', 'label' => 'repayment', 'id_project' => '4085', 'date' => '2017-03-15', 'operationDate' => '2017-03-15 11:04:15', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '28384', 'id_repayment_schedule' => '1544306', 'title' => 'Actual', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '4.43', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.37', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.059999999999999998, ), ), ), ), 21 => array ( 'id' => '81694981', 'available_balance' => '24.28', 'committed_balance' => '0.00', 'amount' => '50.00', 'label' => 'lender_loan', 'id_project' => '61430', 'date' => '2017-03-13', 'operationDate' => '2017-03-13 17:43:46', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '305579', 'id_repayment_schedule' => NULL, 'title' => 'SA Curty Matériels ', ), 22 => array ( 'id' => '81660758', 'available_balance' => '24.28', 'committed_balance' => '50.00', 'amount' => '8.42', 'label' => 'repayment', 'id_project' => '62236', 'date' => '2017-03-13', 'operationDate' => '2017-03-13 11:15:05', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '241762', 'id_repayment_schedule' => '10893406', 'title' => 'G. David Investissements', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '8.32', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.11', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.01, ), ), ), ), 23 => array ( 'id' => '81212074', 'available_balance' => '15.86', 'committed_balance' => '50.00', 'amount' => '1.50', 'label' => 'repayment', 'id_project' => '44520', 'date' => '2017-03-10', 'operationDate' => '2017-03-10 11:14:12', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '204733', 'id_repayment_schedule' => '9274003', 'title' => 'Cabinet Kupiec et Debergh', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '1.29', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.24', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.029999999999999999, ), ), ), ), 24 => array ( 'id' => '81191719', 'available_balance' => '14.36', 'committed_balance' => '50.00', 'amount' => '4.61', 'label' => 'repayment', 'id_project' => '18150', 'date' => '2017-03-10', 'operationDate' => '2017-03-10 11:12:25', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '79810', 'id_repayment_schedule' => '3888387', 'title' => 'Gaïa', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '4.09', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.61', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.089999999999999997, ), ), ), ), 25 => array ( 'id' => '81151885', 'available_balance' => '9.75', 'committed_balance' => '50.00', 'amount' => '0.95', 'label' => 'repayment', 'id_project' => '39189', 'date' => '2017-03-10', 'operationDate' => '2017-03-10 11:10:07', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '192435', 'id_repayment_schedule' => '8718492', 'title' => 'HD Loc', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '0.71', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.28', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.040000000000000001, ), ), ), ), 26 => array ( 'id' => '80896865', 'available_balance' => '8.80', 'committed_balance' => '50.00', 'amount' => '6.86', 'label' => 'repayment', 'id_project' => '30559', 'date' => '2017-03-08', 'operationDate' => '2017-03-08 11:05:05', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '113059', 'id_repayment_schedule' => '5252482', 'title' => 'Paris Canal', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '6.80', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.06', ), ), ), ), 27 => array ( 'id' => '80861548', 'available_balance' => '1.94', 'committed_balance' => '50.00', 'amount' => '-50.00', 'label' => 'bid', 'id_project' => '61430', 'date' => '2017-03-07', 'operationDate' => '2017-03-07 14:02:10', 'id_bid' => '4626965', 'id_autobid' => NULL, 'id_loan' => '', 'id_repayment_schedule' => NULL, 'title' => 'SA Curty Matériels ', ), 28 => array ( 'id' => '80593148', 'available_balance' => '51.94', 'committed_balance' => '0.00', 'amount' => '3.46', 'label' => 'repayment', 'id_project' => '35110', 'date' => '2017-03-05', 'operationDate' => '2017-03-05 11:04:09', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '144141', 'id_repayment_schedule' => '6599829', 'title' => 'Récréalire', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '3.30', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.19', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.029999999999999999, ), ), ), ), 29 => array ( 'id' => '80390918', 'available_balance' => '48.48', 'committed_balance' => '0.00', 'amount' => '5.95', 'label' => 'repayment', 'id_project' => '19', 'date' => '2017-03-02', 'operationDate' => '2017-03-02 11:14:14', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '656', 'id_repayment_schedule' => '31274', 'title' => 'Comelec', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '5.22', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.86', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.13, ), ), ), ), 30 => array ( 'id' => '80147149', 'available_balance' => '42.53', 'committed_balance' => '0.00', 'amount' => '2.63', 'label' => 'repayment', 'id_project' => '69289', 'date' => '2017-03-01', 'operationDate' => '2017-03-01 11:05:15', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '281944', 'id_repayment_schedule' => '12516400', 'title' => 'Azapp', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '2.34', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.35', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.059999999999999998, ), ), ), ), 31 => array ( 'id' => '80123587', 'available_balance' => '39.90', 'committed_balance' => '0.00', 'amount' => '1.50', 'label' => 'repayment', 'id_project' => '35022', 'date' => '2017-03-01', 'operationDate' => '2017-03-01 11:03:05', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '124791', 'id_repayment_schedule' => '5615018', 'title' => 'Eléphants & Co', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '1.33', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.20', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.029999999999999999, ), ), ), ), 32 => array ( 'id' => '80056969', 'available_balance' => '38.40', 'committed_balance' => '0.00', 'amount' => '60.00', 'label' => 'lender_loan', 'id_project' => '71550', 'date' => '2017-02-28', 'operationDate' => '2017-02-28 13:44:11', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '294119', 'id_repayment_schedule' => NULL, 'title' => 'Holding Hurtiger', ), 33 => array ( 'id' => '80016040', 'available_balance' => '38.40', 'committed_balance' => '60.00', 'amount' => '5.04', 'label' => 'repayment', 'id_project' => '1037', 'date' => '2017-02-28', 'operationDate' => '2017-02-28 11:21:03', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '10430', 'id_repayment_schedule' => '575072', 'title' => 'Hôtel-Restaurant du Jura', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '4.17', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '1.04', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.17000000000000001, ), ), ), ), 34 => array ( 'id' => '79915111', 'available_balance' => '33.36', 'committed_balance' => '60.00', 'amount' => '3.42', 'label' => 'repayment', 'id_project' => '60452', 'date' => '2017-02-28', 'operationDate' => '2017-02-28 11:16:03', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '228733', 'id_repayment_schedule' => '10223464', 'title' => 'Financière de Pontarlier', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '3.27', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.17', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.02, ), ), ), ), 35 => array ( 'id' => '79608257', 'available_balance' => '29.94', 'committed_balance' => '60.00', 'amount' => '0.95', 'label' => 'repayment', 'id_project' => '43851', 'date' => '2017-02-27', 'operationDate' => '2017-02-27 11:09:16', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '188373', 'id_repayment_schedule' => '8474772', 'title' => 'BDP Holding', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '0.71', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.28', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.040000000000000001, ), ), ), ), 36 => array ( 'id' => '79492733', 'available_balance' => '28.99', 'committed_balance' => '60.00', 'amount' => '3.97', 'label' => 'repayment', 'id_project' => '25956', 'date' => '2017-02-26', 'operationDate' => '2017-02-26 11:07:04', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '100890', 'id_repayment_schedule' => '4809803', 'title' => 'Kep Technologies Integrated Systems', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '3.73', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.28', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.040000000000000001, ), ), ), ), 37 => array ( 'id' => '79461743', 'available_balance' => '25.02', 'committed_balance' => '60.00', 'amount' => '0.90', 'label' => 'repayment', 'id_project' => '63182', 'date' => '2017-02-26', 'operationDate' => '2017-02-26 11:06:03', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '251602', 'id_repayment_schedule' => '11176507', 'title' => 'Prat Optic', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '0.76', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.16', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.02, ), ), ), ), 38 => array ( 'id' => '79331372', 'available_balance' => '24.12', 'committed_balance' => '60.00', 'amount' => '2.32', 'label' => 'repayment', 'id_project' => '32093', 'date' => '2017-02-25', 'operationDate' => '2017-02-25 11:09:02', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '116317', 'id_repayment_schedule' => '5335569', 'title' => 'S.T.N.L', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '2.23', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.10', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.01, ), ), ), ), 39 => array ( 'id' => '78628954', 'available_balance' => '21.80', 'committed_balance' => '60.00', 'amount' => '10.18', 'label' => 'repayment', 'id_project' => '45044', 'date' => '2017-02-21', 'operationDate' => '2017-02-21 11:09:02', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '186357', 'id_repayment_schedule' => '8442804', 'title' => 'Pascal et Béatrix Décoration - 6', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '9.96', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.26', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.040000000000000001, ), ), ), ), 40 => array ( 'id' => '78573604', 'available_balance' => '11.62', 'committed_balance' => '60.00', 'amount' => '2.69', 'label' => 'repayment', 'id_project' => '57436', 'date' => '2017-02-21', 'operationDate' => '2017-02-21 11:08:04', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '215968', 'id_repayment_schedule' => '9706252', 'title' => 'Diet Plus Shop', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '2.29', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.47', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.070000000000000007, ), ), ), ), 41 => array ( 'id' => '78320399', 'available_balance' => '8.93', 'committed_balance' => '60.00', 'amount' => '40.00', 'label' => 'lender_loan', 'id_project' => '73288', 'date' => '2017-02-20', 'operationDate' => '2017-02-20 09:30:52', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '286162', 'id_repayment_schedule' => NULL, 'title' => 'Ethik & Nature', ), 42 => array ( 'id' => '78217847', 'available_balance' => '8.93', 'committed_balance' => '100.00', 'amount' => '1.20', 'label' => 'repayment', 'id_project' => '37121', 'date' => '2017-02-19', 'operationDate' => '2017-02-19 11:09:03', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '151725', 'id_repayment_schedule' => '6846516', 'title' => 'Air Vide Maintenance', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '1.04', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.19', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.029999999999999999, ), ), ), ), 43 => array ( 'id' => '78173465', 'available_balance' => '7.73', 'committed_balance' => '100.00', 'amount' => '1.50', 'label' => 'repayment', 'id_project' => '37779', 'date' => '2017-02-19', 'operationDate' => '2017-02-19 11:07:03', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '156489', 'id_repayment_schedule' => '6992586', 'title' => 'Becom', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '1.29', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.25', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.040000000000000001, ), ), ), ), 44 => array ( 'id' => '78135857', 'available_balance' => '6.23', 'committed_balance' => '100.00', 'amount' => '1.49', 'label' => 'repayment', 'id_project' => '63304', 'date' => '2017-02-19', 'operationDate' => '2017-02-19 11:03:02', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '250129', 'id_repayment_schedule' => '11123479', 'title' => 'Dentaltech C.S.A.', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '1.27', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.26', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.040000000000000001, ), ), ), ), 45 => array ( 'id' => '78007321', 'available_balance' => '4.74', 'committed_balance' => '100.00', 'amount' => '-40.00', 'label' => 'bid', 'id_project' => '73288', 'date' => '2017-02-18', 'operationDate' => '2017-02-18 10:03:52', 'id_bid' => '4452659', 'id_autobid' => NULL, 'id_loan' => '', 'id_repayment_schedule' => NULL, 'title' => 'Ethik & Nature', ), 46 => array ( 'id' => '77721013', 'available_balance' => '44.74', 'committed_balance' => '60.00', 'amount' => '8.11', 'label' => 'repayment', 'id_project' => '4669', 'date' => '2017-02-16', 'operationDate' => '2017-02-16 11:05:04', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '36355', 'id_repayment_schedule' => '1908275', 'title' => 'Magunivers', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '7.49', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.72', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.10000000000000001, ), ), ), ), 47 => array ( 'id' => '77491699', 'available_balance' => '36.63', 'committed_balance' => '60.00', 'amount' => '-60.00', 'label' => 'bid', 'id_project' => '71550', 'date' => '2017-02-13', 'operationDate' => '2017-02-13 12:32:52', 'id_bid' => '4289441', 'id_autobid' => NULL, 'id_loan' => '', 'id_repayment_schedule' => NULL, 'title' => 'Holding Hurtiger', ), 48 => array ( 'id' => '77450989', 'available_balance' => '96.63', 'committed_balance' => '0.00', 'amount' => '8.41', 'label' => 'repayment', 'id_project' => '62236', 'date' => '2017-02-13', 'operationDate' => '2017-02-13 11:08:02', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '241762', 'id_repayment_schedule' => '10893403', 'title' => 'G. David Investissements', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '8.29', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.14', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.02, ), ), ), ), 49 => array ( 'id' => '77450113', 'available_balance' => '88.22', 'committed_balance' => '0.00', 'amount' => '8.14', 'label' => 'repayment', 'id_project' => '12123', 'date' => '2017-02-13', 'operationDate' => '2017-02-13 11:07:33', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '65887', 'id_repayment_schedule' => '3265542', 'title' => 'Pascal et Béatrix Décoration - 2', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '7.85', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.35', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.059999999999999998, ), ), ), ), 50 => array ( 'id' => '77287942', 'available_balance' => '80.08', 'committed_balance' => '0.00', 'amount' => '4.74', 'label' => 'repayment', 'id_project' => '4085', 'date' => '2017-02-12', 'operationDate' => '2017-02-12 11:08:05', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '28384', 'id_repayment_schedule' => '1544305', 'title' => 'Actual', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '4.40', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.40', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.059999999999999998, ), ), ), ), 51 => array ( 'id' => '77056754', 'available_balance' => '75.34', 'committed_balance' => '0.00', 'amount' => '1.49', 'label' => 'repayment', 'id_project' => '44520', 'date' => '2017-02-10', 'operationDate' => '2017-02-10 11:14:05', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '204733', 'id_repayment_schedule' => '9274000', 'title' => 'Cabinet Kupiec et Debergh', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '1.28', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.25', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.040000000000000001, ), ), ), ), 52 => array ( 'id' => '76991923', 'available_balance' => '73.85', 'committed_balance' => '0.00', 'amount' => '4.61', 'label' => 'repayment', 'id_project' => '18150', 'date' => '2017-02-10', 'operationDate' => '2017-02-10 11:11:10', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '79810', 'id_repayment_schedule' => '3888386', 'title' => 'Gaïa', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '4.06', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.64', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.089999999999999997, ), ), ), ), 53 => array ( 'id' => '76938043', 'available_balance' => '69.24', 'committed_balance' => '0.00', 'amount' => '0.95', 'label' => 'repayment', 'id_project' => '39189', 'date' => '2017-02-10', 'operationDate' => '2017-02-10 11:09:03', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '192435', 'id_repayment_schedule' => '8718489', 'title' => 'HD Loc', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '0.71', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.28', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.040000000000000001, ), ), ), ), 54 => array ( 'id' => '76655015', 'available_balance' => '68.29', 'committed_balance' => '0.00', 'amount' => '6.85', 'label' => 'repayment', 'id_project' => '30559', 'date' => '2017-02-08', 'operationDate' => '2017-02-08 11:32:03', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '113059', 'id_repayment_schedule' => '5252481', 'title' => 'Paris Canal', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '6.77', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.09', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.01, ), ), ), ), 55 => array ( 'id' => '76299659', 'available_balance' => '61.44', 'committed_balance' => '0.00', 'amount' => '3.46', 'label' => 'repayment', 'id_project' => '35110', 'date' => '2017-02-05', 'operationDate' => '2017-02-05 11:02:04', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '144141', 'id_repayment_schedule' => '6599826', 'title' => 'Récréalire', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '3.29', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.20', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.029999999999999999, ), ), ), ), 56 => array ( 'id' => '75982153', 'available_balance' => '57.98', 'committed_balance' => '0.00', 'amount' => '60.00', 'label' => 'lender_loan', 'id_project' => '69289', 'date' => '2017-02-01', 'operationDate' => '2017-02-01 14:34:35', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '281944', 'id_repayment_schedule' => NULL, 'title' => 'Azapp', ), 57 => array ( 'id' => '75874490', 'available_balance' => '57.98', 'committed_balance' => '60.00', 'amount' => '1.50', 'label' => 'repayment', 'id_project' => '35022', 'date' => '2017-02-01', 'operationDate' => '2017-02-01 11:07:03', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '124791', 'id_repayment_schedule' => '5615017', 'title' => 'Eléphants & Co', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '1.32', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.21', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.029999999999999999, ), ), ), ), 58 => array ( 'id' => '75866072', 'available_balance' => '56.48', 'committed_balance' => '60.00', 'amount' => '-60.00', 'label' => 'bid', 'id_project' => '69289', 'date' => '2017-02-01', 'operationDate' => '2017-02-01 08:45:05', 'id_bid' => '4171531', 'id_autobid' => NULL, 'id_loan' => '', 'id_repayment_schedule' => NULL, 'title' => 'Azapp', ), 59 => array ( 'id' => '75814130', 'available_balance' => '116.48', 'committed_balance' => '0.00', 'amount' => '5.04', 'label' => 'repayment', 'id_project' => '1037', 'date' => '2017-01-31', 'operationDate' => '2017-01-31 11:17:03', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '10430', 'id_repayment_schedule' => '575071', 'title' => 'Hôtel-Restaurant du Jura', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '4.14', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '1.07', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.17000000000000001, ), ), ), ), 60 => array ( 'id' => '75740486', 'available_balance' => '111.44', 'committed_balance' => '0.00', 'amount' => '5.95', 'label' => 'repayment', 'id_project' => '19', 'date' => '2017-01-30', 'operationDate' => '2017-01-30 12:02:04', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '656', 'id_repayment_schedule' => '31273', 'title' => 'Comelec', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '5.18', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.90', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.13, ), ), ), ), 61 => array ( 'id' => '75311165', 'available_balance' => '105.49', 'committed_balance' => '0.00', 'amount' => '3.42', 'label' => 'repayment', 'id_project' => '60452', 'date' => '2017-01-28', 'operationDate' => '2017-01-28 11:57:03', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '228733', 'id_repayment_schedule' => '10223461', 'title' => 'Financière de Pontarlier', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '3.26', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.18', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.02, ), ), ), ), 62 => array ( 'id' => '75114575', 'available_balance' => '102.07', 'committed_balance' => '0.00', 'amount' => 60, 'label' => 'refused-bid', 'id_project' => '54727', 'date' => '2017-01-28', 'operationDate' => '2017-01-28 10:25:04', 'id_bid' => '3991594', 'id_autobid' => NULL, 'id_loan' => '', 'id_repayment_schedule' => NULL, 'title' => 'Plein Sud Etiquettes', ), 63 => array ( 'id' => '75103865', 'available_balance' => '42.07', 'committed_balance' => '60.00', 'amount' => '-60.00', 'label' => 'bid', 'id_project' => '54727', 'date' => '2017-01-27', 'operationDate' => '2017-01-27 18:23:49', 'id_bid' => '3991594', 'id_autobid' => NULL, 'id_loan' => '', 'id_repayment_schedule' => NULL, 'title' => 'Plein Sud Etiquettes', ), 64 => array ( 'id' => '74975399', 'available_balance' => '102.07', 'committed_balance' => '0.00', 'amount' => '0.95', 'label' => 'repayment', 'id_project' => '43851', 'date' => '2017-01-27', 'operationDate' => '2017-01-27 11:17:03', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '188373', 'id_repayment_schedule' => '8474769', 'title' => 'BDP Holding', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '0.71', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.28', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.040000000000000001, ), ), ), ), 65 => array ( 'id' => '74860631', 'available_balance' => '101.12', 'committed_balance' => '0.00', 'amount' => '3.97', 'label' => 'repayment', 'id_project' => '25956', 'date' => '2017-01-26', 'operationDate' => '2017-01-26 11:22:04', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '100890', 'id_repayment_schedule' => '4809802', 'title' => 'Kep Technologies Integrated Systems', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '3.71', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.30', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.040000000000000001, ), ), ), ), 66 => array ( 'id' => '74829587', 'available_balance' => '97.15', 'committed_balance' => '0.00', 'amount' => '0.90', 'label' => 'repayment', 'id_project' => '63182', 'date' => '2017-01-26', 'operationDate' => '2017-01-26 11:17:03', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '251602', 'id_repayment_schedule' => '11176504', 'title' => 'Prat Optic', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '0.76', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.16', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.02, ), ), ), ), 67 => array ( 'id' => '74731376', 'available_balance' => '96.25', 'committed_balance' => '0.00', 'amount' => '2.32', 'label' => 'repayment', 'id_project' => '32093', 'date' => '2017-01-25', 'operationDate' => '2017-01-25 11:12:03', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '116317', 'id_repayment_schedule' => '5335568', 'title' => 'S.T.N.L', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '2.22', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.11', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.01, ), ), ), ), 68 => array ( 'id' => '74248372', 'available_balance' => '93.93', 'committed_balance' => '0.00', 'amount' => '10.18', 'label' => 'repayment', 'id_project' => '45044', 'date' => '2017-01-21', 'operationDate' => '2017-01-21 11:57:02', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '186357', 'id_repayment_schedule' => '8442801', 'title' => 'Pascal et Béatrix Décoration - 6', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '9.92', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.30', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.040000000000000001, ), ), ), ), 69 => array ( 'id' => '74192863', 'available_balance' => '83.75', 'committed_balance' => '0.00', 'amount' => '2.69', 'label' => 'repayment', 'id_project' => '57436', 'date' => '2017-01-21', 'operationDate' => '2017-01-21 11:52:06', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '215968', 'id_repayment_schedule' => '9706249', 'title' => 'Diet Plus Shop', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '2.29', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.47', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.070000000000000007, ), ), ), ), 70 => array ( 'id' => '73968976', 'available_balance' => '81.06', 'committed_balance' => '0.00', 'amount' => '1.50', 'label' => 'repayment', 'id_project' => '37779', 'date' => '2017-01-20', 'operationDate' => '2017-01-20 18:22:54', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '156489', 'id_repayment_schedule' => '6992583', 'title' => 'Becom', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '1.29', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.25', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.040000000000000001, ), ), ), ), 71 => array ( 'id' => '73930882', 'available_balance' => '79.56', 'committed_balance' => '0.00', 'amount' => '1.20', 'label' => 'repayment', 'id_project' => '37121', 'date' => '2017-01-20', 'operationDate' => '2017-01-20 18:22:14', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '151725', 'id_repayment_schedule' => '6846513', 'title' => 'Air Vide Maintenance', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '1.03', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.20', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.029999999999999999, ), ), ), ), 72 => array ( 'id' => '73822558', 'available_balance' => '78.36', 'committed_balance' => '0.00', 'amount' => '8.10', 'label' => 'repayment', 'id_project' => '4669', 'date' => '2017-01-20', 'operationDate' => '2017-01-20 18:19:26', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '36355', 'id_repayment_schedule' => '1908274', 'title' => 'Magunivers', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '7.44', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.77', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.11, ), ), ), ), 73 => array ( 'id' => '73808230', 'available_balance' => '70.26', 'committed_balance' => '0.00', 'amount' => '1.49', 'label' => 'repayment', 'id_project' => '63304', 'date' => '2017-01-20', 'operationDate' => '2017-01-20 18:05:27', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '250129', 'id_repayment_schedule' => '11123476', 'title' => 'Dentaltech C.S.A.', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '1.26', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.27', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.040000000000000001, ), ), ), ), );

        return $this->render('pdf/lender_operations.html.twig', ['lenderOperations' => $lenderOperations]);
    }
}
