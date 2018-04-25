<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\{
    Method, Route, Security
};
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\{
    JsonResponse, Request, Response, StreamedResponse
};
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    AddressType, ClientsStatus, CompanyStatus, Notifications, OperationSubType, OperationType, Projects, UnderlyingContract, Wallet, WalletType
};
use Unilend\Bundle\CoreBusinessBundle\Service\LenderOperationsManager;
use Unilend\core\Loader;

class LenderOperationsController extends Controller
{
    /**
     * @Route("/operations", name="lender_operations")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function indexAction(Request $request): Response
    {
        if (false === in_array($this->getUser()->getClientStatus(), ClientsStatus::GRANTED_LENDER_ACCOUNT_READ)) {
            return $this->redirectToRoute('home');
        }

        $entityManagerSimulator  = $this->get('unilend.service.entity_manager');
        $entityManager           = $this->get('doctrine.orm.entity_manager');
        $lenderOperationsManager = $this->get('unilend.service.lender_operations_manager');

        $wallet                 = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($this->getUser()->getClientId(), WalletType::LENDER);
        $filters                = $this->getOperationFilters($request);
        $operations             = $lenderOperationsManager->getOperationsAccordingToFilter($filters['operation']);
        $lenderOperations       = $lenderOperationsManager->getLenderOperations($wallet, $filters['startDate'], $filters['endDate'], $filters['project'], $operations);
        $projectsFundedByLender = array_combine(array_column($lenderOperations, 'id_project'), array_column($lenderOperations, 'title'));

        $loans = $this->commonLoans($request, $wallet);

        return $this->render(
            'lender_operations/index.html.twig',
            [
                'clientId'               => $wallet->getIdClient()->getIdClient(),
                'hash'                   => $this->getUser()->getHash(),
                'lenderOperations'       => $lenderOperations,
                'projectsFundedByLender' => $projectsFundedByLender,
                'loansStatusFilter'      => LenderOperationsManager::LOAN_STATUS_FILTER,
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
    public function filterLoansAction(Request $request): JsonResponse
    {
        if (false === in_array($this->getUser()->getClientStatus(), ClientsStatus::GRANTED_LENDER_ACCOUNT_READ)) {
            return $this->json([
                'target'   => 'loans .panel-table',
                'template' => ''
            ]);
        }

        $entityManager = $this->get('doctrine.orm.entity_manager');
        $wallet        = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($this->getUser()->getClientId(), WalletType::LENDER);
        $loans         = $this->commonLoans($request, $wallet);

        return $this->json([
            'target'   => 'loans .panel-table',
            'template' => $this->render(
                'lender_operations/my_loans_table.html.twig',
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
    public function filterOperationsAction(Request $request): JsonResponse
    {
        if (false === in_array($this->getUser()->getClientStatus(), ClientsStatus::GRANTED_LENDER_ACCOUNT_READ)) {
            return $this->json([
                'target'   => 'loans .panel-table',
                'template' => ''
            ]);
        }

        $entityManager           = $this->get('doctrine.orm.entity_manager');
        $lenderOperationsManager = $this->get('unilend.service.lender_operations_manager');
        $wallet                  = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($this->getUser()->getClientId(), WalletType::LENDER);
        $filters                 = $this->getOperationFilters($request);
        $operations              = $lenderOperationsManager->getOperationsAccordingToFilter($filters['operation']);
        $lenderOperations        = $lenderOperationsManager->getLenderOperations($wallet, $filters['startDate'], $filters['endDate'], $filters['project'], $operations);
        $projectsFundedByLender  = array_combine(array_column($lenderOperations, 'id_project'), array_column($lenderOperations, 'title'));

        return $this->json([
            'target'   => 'operations',
            'template' => $this->render('lender_operations/my_operations.html.twig',
                [
                    'clientId'               => $this->getUser()->getClientId(),
                    'hash'                   => $this->getUser()->getHash(),
                    'projectsFundedByLender' => $projectsFundedByLender,
                    'lenderOperations'       => $lenderOperations,
                    'currentFilters'         => $filters
                ])->getContent()
        ]);
    }

    /**
     * @Route("/operations/excel", name="lender_operations_excel")
     * @Route("/operations/exportOperationsCsv", name="export_operations_csv_legacy")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @return Response
     */
    public function exportOperationsExcelAction(): Response
    {
        if (false === in_array($this->getUser()->getClientStatus(), ClientsStatus::GRANTED_LENDER_ACCOUNT_READ)) {
            return $this->redirectToRoute('home');
        }

        $session = $this->get('session');

        if (false === $session->has('lenderOperationsFilters')) {
            return $this->redirectToRoute('lender_operations');
        }

        $entityManager           = $this->get('doctrine.orm.entity_manager');
        $lenderOperationsManager = $this->get('unilend.service.lender_operations_manager');
        $wallet                  = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($this->getUser()->getClientId(), WalletType::LENDER);
        $filters                 = $session->get('lenderOperationsFilters');
        $operations              = $lenderOperationsManager->getOperationsAccordingToFilter($filters['operation']);
        $fileName                = 'operations_' . date('Y-m-d_His') . '.xlsx';
        $writer                  = $lenderOperationsManager->getOperationsExcelFile($wallet, $filters['startDate'], $filters['endDate'], $filters['project'], $operations, $fileName);

        return new StreamedResponse(
            function () use ($writer) {
                $writer->close();
            }, Response::HTTP_OK, [
                'Content-Type' => 'application/force-download; charset=utf-8',
                'Expires'      => 0
            ]
        );
    }

    /**
     * @Route("/prets/excel", name="lender_loans_excel")
     * @Route("/operations/exportLoansCsv", name="export_loans_csv_legacy")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function exportLoansExcelAction(Request $request): Response
    {
        if (false === in_array($this->getUser()->getClientStatus(), ClientsStatus::GRANTED_LENDER_ACCOUNT_READ)) {
            return $this->redirectToRoute('home');
        }

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
            $oActiveSheet->setCellValue('F' . ($iRowIndex + 2), $aProjectLoans['start_date']->format('d/m/Y'));

            switch ($aProjectLoans['loanStatus']) {
                case LenderOperationsManager::LOAN_STATUS_DISPLAY_COMPLETED:
                    if ($aProjectLoans['isCloseOutNetting']) {
                        $translationId = 'lender-operations_loans-table-project-status-label-collected-on-date';
                    } else {
                        $translationId = 'lender-operations_loans-table-project-status-label-repayment-finished-on-date';

                    }
                    $oActiveSheet->mergeCells('G' . ($iRowIndex + 2) . ':K' . ($iRowIndex + 2));
                    $oActiveSheet->setCellValue('G' . ($iRowIndex + 2), $this->get('translator')->trans($translationId, ['%date%' => $aProjectLoans['final_repayment_date']->format('d/m/Y')]));
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
                case LenderOperationsManager::LOAN_STATUS_DISPLAY_LOSS:
                    $oActiveSheet->mergeCells('G' . ($iRowIndex + 2) . ':K' . ($iRowIndex + 2));
                    $oActiveSheet->setCellValue(
                        'G' . ($iRowIndex + 2),
                        $this->get('translator')->transChoice(
                            'lender-operations_detailed-loan-status-label-lost',
                            $aProjectLoans['count']['declaration']
                        )
                    );
                    break;
                default:
                    $oActiveSheet->setCellValue('G' . ($iRowIndex + 2), $aProjectLoans['next_payment_date']->format('d/m/Y'));
                    $oActiveSheet->setCellValue('H' . ($iRowIndex + 2), $aProjectLoans['end_date']->format('d/m/Y'));
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
     * @param string $risk a letter that gives the risk value [A-H]
     *
     * @return string
     */
    private function getProjectNote(string $risk): string
    {
        switch ($risk) {
            case 'A':
                return '5';
                break;
            case 'B':
                return '4,5';
                break;
            case 'C':
                return '4';
                break;
            case 'D':
                return '3,5';
                break;
            case 'E':
                return '3';
                break;
            case 'F':
                return '2,5';
                break;
            case 'G':
                return '2';
                break;
            case 'H':
                return '1,5';
                break;
            default:
                return '';
        }
    }

    /**
     * @param Request $request
     * @param Wallet  $wallet
     *
     * @return array
     * @throws \Exception
     */
    private function commonLoans(Request $request, Wallet $wallet): array
    {
        $filters      = $request->request->get('filter', []);
        $year         = isset($filters['date']) && false !== filter_var($filters['date'], FILTER_VALIDATE_INT) ? $filters['date'] : null;
        $statusFilter = isset($filters['status']) ? $filters['status'] : null;

        /** @var \loans $loan */
        $loan        = $this->get('unilend.service.entity_manager')->getRepository('loans');
        $lenderLoans = $loan->getSumLoansByProject($wallet->getId(), 'debut ASC, p.title ASC', $year);

        $lenderOperationManager  = $this->get('unilend.service.lender_operations_manager');

        return $lenderOperationManager->formatLenderLoansData($wallet, $lenderLoans, $statusFilter);
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
     * @throws \Exception
     */
    private function getOperationFilters(Request $request): array
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
     * @return JsonResponse
     */
    public function loadProjectNotificationsAction(int $projectId): JsonResponse
    {
        if (false === in_array($this->getUser()->getClientStatus(), ClientsStatus::GRANTED_LENDER_ACCOUNT_READ)) {
            return $this->json(['tpl' => '']);
        }

        try {
            $data = $this->getProjectInformation($projectId);
            $code = Response::HTTP_OK;
        } catch (\Exception $exception) {
            $data = [];
            $code = Response::HTTP_INTERNAL_SERVER_ERROR;
            $this->get('logger')->error('Exception while getting client notifications for id_project: ' . $projectId . ' Message: ' . $exception->getMessage(),
                ['id_client' => $this->getUser()->getClientId(), 'class' => __CLASS__, 'function' => __FUNCTION__]);
        }

        return $this->json(
            [
                'tpl' => $this->renderView('lender_operations/my_loans_details_activity.html.twig', ['projectNotifications' => $data, 'code' => $code]),
            ],
            $code
        );
    }

    /**
     * @param int $projectId
     *
     * @return array
     */
    private function getProjectInformation(int $projectId): array
    {
        $entityManager   = $this->get('doctrine.orm.entity_manager');
        $translator      = $this->get('translator');
        $numberFormatter = $this->get('number_formatter');

        $operationRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Operation');
        $project             = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($projectId);
        $wallet              = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($this->getUser()->getClientId(), WalletType::LENDER);

        $data = [];

        $companyStatusHistoryContent = $entityManager
            ->getRepository('UnilendCoreBusinessBundle:CompanyStatusHistory')
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

        $projectNotifications = $entityManager
            ->getRepository('UnilendCoreBusinessBundle:ProjectNotification')
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
        $earlyRepayments           = $operationRepository->findBy([
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
        $debtCollectionRepayments           = $operationRepository->findBy([
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
        $scheduledRepayments  = $operationRepository->findBy([
            'idProject'        => $project,
            'idWalletCreditor' => $wallet,
            'idType'           => $capitalRepaymentType,
            'idSubType'        => null
        ]);

        foreach ($scheduledRepayments as $repayment) {
            $title             = $translator->trans('lender-notifications_repayment-title');
            $repaymentSchedule = $repayment->getRepaymentSchedule();
            if (null !== $repaymentSchedule) {
                $title = $translator->trans('lender-notifications_repayment-with-repayment-schedule-title', ['%scheduleSequence%' => $repaymentSchedule->getOrdre()]);
            }
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
        $regularizedRepayments              = $operationRepository->findBy([
            'idProject'      => $project,
            'idWalletDebtor' => $wallet,
            'idType'         => $capitalRepaymentRegularizationType,
            'idSubType'      => null
        ]);

        foreach ($regularizedRepayments as $repayment) {
            $title             = $translator->trans('lender-notifications_repayment-regularization-title');
            $repaymentSchedule = $repayment->getRepaymentSchedule();
            if (null !== $repaymentSchedule) {
                $title = $translator->trans('lender-notifications_repayment-regularization-with-repayment-schedule-title', ['%scheduleSequence%' => $repaymentSchedule->getOrdre()]);
            }
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

        /** @var \ficelle $ficelle */
        $ficelle                    = Loader::loadLib('ficelle');
        $bidsEntity                 = $entityManager->getRepository('UnilendCoreBusinessBundle:Bids');
        $acceptedBidEntity          = $entityManager->getRepository('UnilendCoreBusinessBundle:AcceptedBids');
        $acceptedLoansNotifications = $entityManager
            ->getRepository('UnilendCoreBusinessBundle:Notifications')
            ->findBy(['idProject' => $projectId, 'idLender' => $wallet, 'type' => Notifications::TYPE_LOAN_ACCEPTED]);

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
    private function getProjectStatusTitleAndContent(array $content, Projects $project, TranslatorInterface $translator): array
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
     * @return int
     */
    private function sortArrayByDate(array $a, array $b): int
    {
        return $b['datetime']->getTimestamp() - $a['datetime']->getTimestamp();
    }

    /**
     * @Route("/operations/pdf", name="lender_operations_pdf")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @return Response
     */
    public function downloadOperationPdfAction(): Response
    {
        if (false === in_array($this->getUser()->getClientStatus(), ClientsStatus::GRANTED_LENDER_ACCOUNT_READ)) {
            return $this->redirectToRoute('home');
        }

        $session = $this->get('session');

        if (false === $session->has('lenderOperationsFilters')) {
            return $this->redirectToRoute('lender_operations');
        }

        $entityManager           = $this->get('doctrine.orm.entity_manager');
        $lenderOperationsManager = $this->get('unilend.service.lender_operations_manager');
        $wallet                  = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($this->getUser()->getClientId(), WalletType::LENDER);
        $client                  = $wallet->getIdClient();

        if (false === $client->isNaturalPerson()) {
            $company = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findOneBy(['idClientOwner' => $client]);
        }

        $filters          = $session->get('lenderOperationsFilters');
        $operations       = $lenderOperationsManager->getOperationsAccordingToFilter($filters['operation']);
        $lenderOperations = $lenderOperationsManager->getLenderOperations($wallet, $filters['startDate'], $filters['endDate'], $filters['project'], $operations);

        try {
            if ($client->isNaturalPerson()) {
                $lenderAddress = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientAddress')
                    ->findLastModifiedNotArchivedAddressByType($client, AddressType::TYPE_MAIN_ADDRESS);
            } else {
                $lenderAddress = $entityManager->getRepository('UnilendCoreBusinessBundle:CompanyAddress')
                    ->findLastModifiedNotArchivedAddressByType($company, AddressType::TYPE_MAIN_ADDRESS);
            }
        } catch (\Exception $exception) {
            $this->get('logger')->warning('Client has no main address', [
                'class'      => __CLASS__,
                'function'   => __FUNCTION__,
                'id_client'  => $client->getIdClient(),
                'id_company' => isset($company) ? $company->getIdCompany() : 'Lender is natural person'
            ]);
        }

        $fileName         = 'vos_operations_' . date('Y-m-d') . '.pdf';
        $pdfContent       = $this->renderView('pdf/lender_operations.html.twig', [
            'lenderOperations'  => $lenderOperations,
            'client'            => $client,
            'lenderAddress'     => $lenderAddress,
            'company'           => empty($company) ? null : $company,
            'available_balance' => $wallet->getAvailableBalance()
        ]);

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
