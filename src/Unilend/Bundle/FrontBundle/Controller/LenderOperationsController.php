<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Doctrine\ORM\EntityManager;
use Knp\Snappy\GeneratorInterface;
use PHPExcel_IOFactory;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Service\LenderOperationsManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;
use Unilend\Bundle\FrontBundle\Security\User\UserLender;
use Unilend\core\Loader;

class LenderOperationsController extends Controller
{
    const LAST_OPERATION_DATE = '2013-01-01';

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
                'loansStatusFilter'      => $projectStatus->select('status >= ' . \projects_status::REMBOURSEMENT, 'status ASC'),
                'firstLoanYear'          => $entityManagerSimulator->getRepository('loans')->getFirstLoanYear($wallet->getId()),
                'lenderLoans'            => $loans['lenderLoans'],
                'loanStatus'             => $loans['loanStatus'],
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
        /** @var EntityManagerSimulator $entityManagerSimulator */
        $entityManagerSimulator = $this->get('unilend.service.entity_manager');
        /** @var \projects_status $projectStatus */
        $projectStatus = $entityManagerSimulator->getRepository('projects_status');
        $wallet        = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($this->getUser()->getClientId(), WalletType::LENDER);
        $loans         = $this->commonLoans($request, $wallet);

        return $this->json(
            [
                'target'   => 'loans',
                'template' => $this->render('/pages/lender_operations/my_loans.html.twig',
                    [
                        'clientId'          => $this->getUser()->getClientId(),
                        'hash'              => $this->getUser()->getHash(),
                        'loansStatusFilter' => $projectStatus->select('status >= ' . \projects_status::REMBOURSEMENT, 'status ASC'),
                        'firstLoanYear'     => $entityManagerSimulator->getRepository('loans')->getFirstLoanYear($wallet->getId()),
                        'lenderLoans'       => $loans['lenderLoans'],
                        'loanStatus'        => $loans['loanStatus'],
                        'seriesData'        => $loans['seriesData'],
                        'currentFilters'    => $request->request->get('filter', [])
                    ]
                )->getContent()
            ]
        );
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
            $oActiveSheet->setCellValue('G' . ($iRowIndex + 2), date('d/m/Y', strtotime($aProjectLoans['next_payment_date'])));
            $oActiveSheet->setCellValue('H' . ($iRowIndex + 2), date('d/m/Y', strtotime($aProjectLoans['end_date'])));
            $oActiveSheet->setCellValue('I' . ($iRowIndex + 2), $repaymentSchedule->getRepaidCapital(['id_lender' => $wallet->getId(), 'id_project' => $aProjectLoans['id']]));
            $oActiveSheet->setCellValue('J' . ($iRowIndex + 2), $repaymentSchedule->getRepaidInterests(['id_lender' => $wallet->getId(), 'id_project' => $aProjectLoans['id']]));
            $oActiveSheet->setCellValue('K' . ($iRowIndex + 2), $repaymentSchedule->getOwedCapital(['id_lender' => $wallet->getId(), 'id_project' => $aProjectLoans['id']]));

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
        /** @var \loans $loanEntity */
        $loanEntity = $this->get('unilend.service.entity_manager')->getRepository('loans');
        /** @var \projects $project */
        $project = $this->get('unilend.service.entity_manager')->getRepository('projects');

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

        $projectsInDept = $project->getProjectsInDebt();
        $filters        = $request->request->get('filter', []);
        $year           = isset($filters['date']) && false !== filter_var($filters['date'], FILTER_VALIDATE_INT) ? $filters['date'] : null;
        $status         = isset($filters['status']) && false !== filter_var($filters['status'], FILTER_VALIDATE_INT) ? $filters['status'] : null;
        $lenderLoans    = $loanEntity->getSumLoansByProject($wallet->getId(), $sOrderBy, $year, $status);
        $loanStatus     = [
            'no-problem'            => 0,
            'late-repayment'        => 0,
            'recovery'              => 0,
            'collective-proceeding' => 0,
            'default'               => 0,
            'refund-finished'       => 0,
        ];
        /** @var UserLender $user */
        $user               = $this->getUser();
        $lenderProjectLoans = [];

        foreach ($lenderLoans as $loanIndex => $aProjectLoans) {
            $loanData = [];
            /** @var \DateTime $startDateTime */
            $startDateTime = new \DateTime(date('Y-m-d'));
            /** @var \DateTime $endDateTime */
            $endDateTime = new \DateTime($aProjectLoans['fin']);
            /** @var \DateInterval $remainingDuration */
            $remainingDuration = $startDateTime->diff($endDateTime);

            $loanData['id']                       = $aProjectLoans['id_project'];
            $loanData['url']                      = $this->generateUrl('project_detail', ['projectSlug' => $aProjectLoans['slug']]);
            $loanData['name']                     = $aProjectLoans['title'];
            $loanData['rate']                     = round($aProjectLoans['rate'], 1);
            $loanData['risk']                     = $aProjectLoans['risk'];
            $loanData['amount']                   = round($aProjectLoans['amount']);
            $loanData['start_date']               = $aProjectLoans['debut'];
            $loanData['end_date']                 = $aProjectLoans['fin'];
            $loanData['next_payment_date']        = $aProjectLoans['next_echeance'];
            $loanData['monthly_repayment_amount'] = $aProjectLoans['monthly_repayment_amount'];
            $loanData['duration']                 = $remainingDuration->y * 12 + $remainingDuration->m;
            $loanData['status_change']            = $aProjectLoans['status_change'];
            $loanData['project_status']           = $aProjectLoans['project_status'];

            $lenderLoans[$loanIndex]['project_remaining_duration'] = $remainingDuration->y * 12 + $remainingDuration->m;

            switch ($aProjectLoans['project_status']) {
                case \projects_status::PROBLEME:
                case \projects_status::PROBLEME_J_X:
                    $lenderLoans[$loanIndex]['status_color'] = 'late';
                    $loanData['status']                      = 'late';

                    $lenderLoans[$loanIndex]['color'] = '#5FC4D0';
                    ++$loanStatus['late-repayment'];
                    break;
                case \projects_status::RECOUVREMENT:
                    $lenderLoans[$loanIndex]['status_color'] = 'completing';
                    $loanData['status']                      = 'completing';
                    $lenderLoans[$loanIndex]['color']        = '#FFCA2C';
                    ++$loanStatus['recovery'];
                    break;
                case \projects_status::PROCEDURE_SAUVEGARDE:
                case \projects_status::REDRESSEMENT_JUDICIAIRE:
                case \projects_status::LIQUIDATION_JUDICIAIRE:
                    $lenderLoans[$loanIndex]['status_color'] = 'problem';
                    $loanData['status']                      = 'problem';
                    $lenderLoans[$loanIndex]['color']        = '#F2980C';
                    ++$loanStatus['collective-proceeding'];
                    break;
                case \projects_status::DEFAUT:
                    $lenderLoans[$loanIndex]['status_color'] = 'defaulted';
                    $loanData['status']                      = 'defaulted';
                    $lenderLoans[$loanIndex]['color']        = '#F76965';
                    ++$loanStatus['default'];
                    break;
                case \projects_status::REMBOURSE:
                case \projects_status::REMBOURSEMENT_ANTICIPE:
                    $lenderLoans[$loanIndex]['status_color'] = 'completed';
                    $loanData['status']                      = 'completed';
                    $lenderLoans[$loanIndex]['color']        = '#1B88DB';
                    ++$loanStatus['refund-finished'];
                    break;
                case \projects_status::REMBOURSEMENT:
                default:
                    $lenderLoans[$loanIndex]['status_color'] = 'inprogress';
                    $loanData['status']                      = 'inprogress';
                    $lenderLoans[$loanIndex]['color']        = '#428890';
                    ++$loanStatus['no-problem'];
                    break;
            }

            if ($aProjectLoans['nb_loan'] == 1) {
                $loanData['count'] = [
                    'bond'        => 0,
                    'contract'    => 0,
                    'declaration' => 0
                ];
                (1 == $aProjectLoans['id_type_contract']) ? $loanData['count']['bond']++ : $loanData['count']['contract']++;

                $loans[0] = [
                    'rate'      => round($aProjectLoans['rate'], 1),
                    'amount'    => round($aProjectLoans['amount']),
                    'documents' => $this->getDocumentDetail(
                        $aProjectLoans['project_status'],
                        $user->getHash(),
                        $aProjectLoans['id_loan_if_one_loan'],
                        $aProjectLoans['id_type_contract'],
                        $projectsInDept,
                        $aProjectLoans['id_project'],
                        $loanData['count']['declaration']
                    )
                ];
            } else {
                $projectLoans                            = $loanEntity->select('id_lender = ' . $wallet->getId() . ' AND id_project = ' . $aProjectLoans['id_project']);
                $lenderLoans[$loanIndex]['contracts']    = [];
                $lenderLoans[$loanIndex]['loan_details'] = [];

                $loanData['count'] = [
                    'bond'        => 0,
                    'contract'    => 0,
                    'declaration' => 0
                ];

                foreach ($projectLoans as $partialLoan) {
                    (1 == $partialLoan['id_type_contract']) ? $loanData['count']['bond']++ : $loanData['count']['contract']++;

                    $loans[] = [
                        'rate'      => round($partialLoan['rate'], 1),
                        'amount'    => bcdiv($partialLoan['amount'], 100, 0),
                        'documents' => $this->getDocumentDetail(
                            $aProjectLoans['project_status'],
                            $user->getHash(),
                            $partialLoan['id_loan'],
                            $partialLoan['id_type_contract'],
                            $projectsInDept,
                            $aProjectLoans['id_project'],
                            $loanData['count']['declaration']
                        )
                    ];
                }
            }
            $loanData['loans']    = $loans;
            $lenderProjectLoans[] = $loanData;
            unset($loans, $loanData);
        }

        $chartColors = [
            'late-repayment'        => '#5FC4D0',
            'recovery'              => '#FFCA2C',
            'collective-proceeding' => '#F2980C',
            'default'               => '#F76965',
            'refund-finished'       => '#1B88DB',
            'no-problem'            => '#428890'
        ];
        $seriesData  = [];
        foreach ($loanStatus as $status => $count) {
            $seriesData[] = [
                'name'         => $this->get('translator')->transChoice('lender-operations_loans-chart-legend-loan-status-' . $status, $count, ['%count%' => $count]),
                'y'            => $count,
                'showInLegend' => true,
                'color'        => $chartColors[$status]
            ];
        }
        return ['lenderLoans' => $lenderProjectLoans, 'loanStatus' => $loanStatus, 'seriesData' => $seriesData];
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
            'lenderOperations' => $lenderOperations,
            'client'           => $wallet->getIdClient(),
            'clientAddress'    => $clientAddress,
            'company'          => empty($company) ? null : $company
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
