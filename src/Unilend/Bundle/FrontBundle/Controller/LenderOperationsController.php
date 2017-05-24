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
use Unilend\Bundle\CoreBusinessBundle\Entity\Operation;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationSubType;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Service\LenderOperationsManager;
use Unilend\Bundle\FrontBundle\Security\User\UserLender;

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
     *
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
        $entityManager   = $this->get('doctrine.orm.entity_manager');
        $translator      = $this->get('translator');
        $numberFormatter = $this->get('number_formatter');

        $operationRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Operation');

        /** @var Projects $project */
        $project = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($projectId);
        /** @var Wallet $wallet */
        $wallet = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($this->getUser()->getClientId(), WalletType::LENDER);

        $data = [];

        $projectStatusAfterEarlyRepayment = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsStatusHistory')
            ->getHistoryAfterGivenStatus($project, ProjectsStatus::REMBOURSEMENT_ANTICIPE);
        foreach ($projectStatusAfterEarlyRepayment as $projectStatus) {
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
                    '%amount%'     => $numberFormatter->format((float) $repayment->getAmount()),
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
                    '%amount%'     => $numberFormatter->format((float) $repayment->getAmount()),
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
        $entityManager= $this->get('doctrine.orm.entity_manager');
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
        $pdfContent = $this->renderView('pdf/lender_operations.html.twig', [
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
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => sprintf('attachment; filename="%s"', $fileName)
                ]
        );
    }
}
