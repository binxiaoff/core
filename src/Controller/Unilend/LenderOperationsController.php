<?php

namespace Unilend\Controller\Unilend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response, StreamedResponse};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Entity\{AcceptedBids, AddressType, Bids, ClientAddress, Clients, Companies, CompanyAddress, CompanyStatus, CompanyStatusHistory, Notifications, Operation, OperationSubType, OperationType,
    ProjectNotification, Projects, Wallet, WalletType};
use Unilend\Service\LenderOperationsManager;
use Unilend\Service\Front\LenderLoansDisplayManager;
use Unilend\core\Loader;

class LenderOperationsController extends Controller
{
    /**
     * @Route("/operations", name="lender_operations")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param Request                    $request
     * @param UserInterface|Clients|null $client
     *
     * @return Response
     */
    public function indexAction(Request $request, ?UserInterface $client): Response
    {
        if (false === $client->isGrantedLenderRead()) {
            return $this->redirectToRoute('home');
        }

        $entityManagerSimulator  = $this->get('unilend.service.entity_manager');
        $entityManager           = $this->get('doctrine.orm.entity_manager');
        $lenderOperationsManager = $this->get('unilend.service.lender_operations_manager');

        $wallet                 = $entityManager->getRepository(Wallet::class)->getWalletByType($client, WalletType::LENDER);
        $filters                = $this->getOperationFilters($request, $client);
        $operations             = $lenderOperationsManager->getOperationsAccordingToFilter($filters['operation']);
        $lenderOperations       = $lenderOperationsManager->getLenderOperations($wallet, $filters['startDate'], $filters['endDate'], $filters['project'], $operations);
        $projectsFundedByLender = array_combine(array_column($lenderOperations, 'id_project'), array_column($lenderOperations, 'title'));

        $loans = $this->commonLoans($request, $wallet);

        return $this->render(
            'lender_operations/index.html.twig',
            [
                'clientId'               => $client->getIdClient(),
                'hash'                   => $client->getHash(),
                'lenderOperations'       => $lenderOperations,
                'projectsFundedByLender' => $projectsFundedByLender,
                'loansStatusFilter'      => LenderLoansDisplayManager::LOAN_STATUS_FILTER,
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
     * @param Request                    $request
     * @param UserInterface|Clients|null $client
     *
     * @return JsonResponse
     */
    public function filterLoansAction(Request $request, ?UserInterface $client): JsonResponse
    {
        if (false === $client->isGrantedLenderRead()) {
            return $this->json([
                'target'   => 'loans .panel-table',
                'template' => ''
            ]);
        }

        $entityManager = $this->get('doctrine.orm.entity_manager');
        $wallet        = $entityManager->getRepository(Wallet::class)->getWalletByType($client, WalletType::LENDER);
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
     * @param Request                    $request
     * @param UserInterface|Clients|null $client
     *
     * @return JsonResponse
     */
    public function filterOperationsAction(Request $request, ?UserInterface $client): JsonResponse
    {
        if (false === $client->isGrantedLenderRead()) {
            return $this->json([
                'target'   => 'loans .panel-table',
                'template' => ''
            ]);
        }

        $entityManager           = $this->get('doctrine.orm.entity_manager');
        $lenderOperationsManager = $this->get('unilend.service.lender_operations_manager');
        $wallet                  = $entityManager->getRepository(Wallet::class)->getWalletByType($client, WalletType::LENDER);
        $filters                 = $this->getOperationFilters($request, $client);
        $operations              = $lenderOperationsManager->getOperationsAccordingToFilter($filters['operation']);
        $lenderOperations        = $lenderOperationsManager->getLenderOperations($wallet, $filters['startDate'], $filters['endDate'], $filters['project'], $operations);
        $projectsFundedByLender  = array_combine(array_column($lenderOperations, 'id_project'), array_column($lenderOperations, 'title'));

        return $this->json([
            'target'   => 'operations',
            'template' => $this->render('lender_operations/my_operations.html.twig', [
                'clientId'               => $client->getIdClient(),
                'hash'                   => $client->getHash(),
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
     * @param UserInterface|Clients|null $client
     *
     * @return Response
     */
    public function exportOperationsExcelAction(?UserInterface $client): Response
    {
        if (false === $client->isGrantedLenderRead()) {
            return $this->redirectToRoute('home');
        }

        $session = $this->get('session');

        if (false === $session->has('lenderOperationsFilters')) {
            return $this->redirectToRoute('lender_operations');
        }

        $entityManager           = $this->get('doctrine.orm.entity_manager');
        $lenderOperationsManager = $this->get('unilend.service.lender_operations_manager');
        $wallet                  = $entityManager->getRepository(Wallet::class)->getWalletByType($client, WalletType::LENDER);
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
     * @param UserInterface $client
     *
     * @return Response
     */
    public function exportLoansExcelAction(?UserInterface $client): Response
    {
        if (false === $client->isGrantedLenderRead()) {
            return $this->redirectToRoute('home');
        }

        $entityManager          = $this->get('doctrine.orm.entity_manager');
        $entityManagerSimulator = $this->get('unilend.service.entity_manager');

        $wallet = $entityManager->getRepository(Wallet::class)->getWalletByType($client, WalletType::LENDER);
        /** @var \echeanciers $repaymentSchedule */
        $repaymentSchedule = $entityManagerSimulator->getRepository('echeanciers');

        \PHPExcel_Settings::setCacheStorageMethod(
            \PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp,
            ['memoryCacheSize' => '2048MB', 'cacheTime' => 1200]
        );

        $phpExcel = new \PHPExcel();

        try {
            $activeSheet = $phpExcel->setActiveSheetIndex(0);
        } catch (\PHPExcel_Exception $exception) {
            $this->get('logger')->error('Could not set PHPExcel active sheet. Error: ' . $exception->getMessage(), [
                'id_client' => $client->getIdClient(),
                'class'     => __CLASS__,
                'function'  => __FUNCTION__,
                'file'      => $exception->getFile(),
                'line'      => $exception->getLine()
            ]);

            return $this->redirectToRoute('lender_operations');
        }

        $activeSheet->setCellValue('A1', 'Projet');
        $activeSheet->setCellValue('B1', 'Numéro de projet');
        $activeSheet->setCellValue('C1', 'Montant');
        $activeSheet->setCellValue('D1', 'Statut');
        $activeSheet->setCellValue('E1', 'Taux d\'intérêts');
        $activeSheet->setCellValue('F1', 'Premier remboursement');
        $activeSheet->setCellValue('G1', 'Prochain remboursement prévu');
        $activeSheet->setCellValue('H1', 'Date dernier remboursement');
        $activeSheet->setCellValue('I1', 'Capital perçu');
        $activeSheet->setCellValue('J1', 'Intérêts perçus');
        $activeSheet->setCellValue('K1', 'Capital restant dû');
        $activeSheet->setCellValue('L1', 'Note');

        $lenderLoans               = $entityManagerSimulator->getRepository('loans')->getSumLoansByProject($wallet->getId(), 'debut ASC, title ASC', null, true);
        $lenderLoansDisplayManager = $this->get(LenderLoansDisplayManager::class);

        try {
            foreach ($lenderLoansDisplayManager->formatLenderLoansForExport($lenderLoans) as $rowIndex => $projectLoans) {
                $activeSheet->setCellValue('A' . ($rowIndex + 2), $projectLoans['name']);
                $activeSheet->setCellValue('B' . ($rowIndex + 2), $projectLoans['id']);
                $activeSheet->setCellValue('C' . ($rowIndex + 2), $projectLoans['amount']);
                $activeSheet->setCellValue('D' . ($rowIndex + 2), $projectLoans['loanStatusLabel']);
                $activeSheet->setCellValue('E' . ($rowIndex + 2), $projectLoans['rate']);
                $activeSheet->setCellValue('F' . ($rowIndex + 2), $projectLoans['startDate']);

                switch ($projectLoans['loanStatus']) {
                    case LenderLoansDisplayManager::LOAN_STATUS_DISPLAY_PENDING:
                        $activeSheet->mergeCells('G' . ($rowIndex + 2) . ':K' . ($rowIndex + 2));
                        $activeSheet->setCellValue('G' . ($rowIndex + 2), $this->get('translator')->trans('lender-operations_loans-table-project-status-label-pending'));
                        break;
                    case LenderLoansDisplayManager::LOAN_STATUS_DISPLAY_COMPLETED:
                        if ($projectLoans['isCloseOutNetting']) {
                            $translationId = 'lender-operations_loans-table-project-status-label-collected-on-date';
                        } else {
                            $translationId = 'lender-operations_loans-table-project-status-label-repayment-finished-on-date';
                        }

                        $activeSheet->mergeCells('G' . ($rowIndex + 2) . ':K' . ($rowIndex + 2));
                        $activeSheet->setCellValue('G' . ($rowIndex + 2), $this->get('translator')->trans($translationId, ['%date%' => $projectLoans['finalRepaymentDate']]));
                        break;
                    case LenderLoansDisplayManager::LOAN_STATUS_DISPLAY_PROCEEDING:
                    case LenderLoansDisplayManager::LOAN_STATUS_DISPLAY_AMICABLE_DC:
                    case LenderLoansDisplayManager::LOAN_STATUS_DISPLAY_LITIGATION_DC:
                        $activeSheet->mergeCells('G' . ($rowIndex + 2) . ':K' . ($rowIndex + 2));
                        $activeSheet->setCellValue(
                            'G' . ($rowIndex + 2),
                            $this->get('translator')->transChoice(
                                'lender-operations_loans-table-project-procedure-in-progress',
                                $projectLoans['numberOfLoansInDebt']
                            )
                        );
                        break;
                    case LenderLoansDisplayManager::LOAN_STATUS_DISPLAY_LOSS:
                        $activeSheet->mergeCells('G' . ($rowIndex + 2) . ':K' . ($rowIndex + 2));
                        $activeSheet->setCellValue(
                            'G' . ($rowIndex + 2),
                            $this->get('translator')->transChoice(
                                'lender-operations_detailed-loan-status-label-lost',
                                $projectLoans['numberOfLoansInDebt']
                            )
                        );
                        break;
                    default:
                        $activeSheet->setCellValue('G' . ($rowIndex + 2), $projectLoans['nextRepaymentDate']);
                        $activeSheet->setCellValue('H' . ($rowIndex + 2), $projectLoans['endDate']);
                        $activeSheet->setCellValue('I' . ($rowIndex + 2), $repaymentSchedule->getRepaidCapital(['id_lender' => $wallet->getId(), 'id_project' => $projectLoans['id']]));
                        $activeSheet->setCellValue('J' . ($rowIndex + 2), $repaymentSchedule->getRepaidInterests(['id_lender' => $wallet->getId(), 'id_project' => $projectLoans['id']]));
                        $activeSheet->setCellValue('K' . ($rowIndex + 2), $repaymentSchedule->getOwedCapital(['id_lender' => $wallet->getId(), 'id_project' => $projectLoans['id']]));
                        break;
                }
                $risk = isset($projectLoans['risk']) ? $projectLoans['risk'] : '';
                $note = $this->getProjectNote($risk);
                $activeSheet->setCellValue('L' . ($rowIndex + 2), $note);
            }
        } catch (\PHPExcel_Exception $exception) {
            $this->get('logger')->error('Could not write PHPExcel file content. Error: ' . $exception->getMessage(), [
                'id_client' => $wallet->getIdClient(),
                'class'     => __CLASS__,
                'function'  => __FUNCTION__,
                'file'      => $exception->getFile(),
                'line'      => $exception->getLine()
            ]);

            return $this->redirectToRoute('lender_operations');
        }

        /** @var \PHPExcel_Writer_Excel5 $oWriter */
        try {
            $oWriter = \PHPExcel_IOFactory::createWriter($phpExcel, 'Excel5');

            ob_start();
            $oWriter->save('php://output');
            $content = ob_get_clean();

            return new Response($content, Response::HTTP_OK, [
                'Content-type'        => 'application/force-download; charset=utf-8',
                'Expires'             => 0,
                'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
                'content-disposition' => "attachment;filename=" . 'prets_' . date('Y-m-d_H:i:s') . ".xls"
            ]);
        } catch (\PHPExcel_Reader_Exception $exception) {
            $this->get('logger')->error('Could not save Excel file content. Error: ' . $exception->getMessage(), [
                'id_client' => $wallet->getIdClient(),
                'class'     => __CLASS__,
                'function'  => __FUNCTION__,
                'file'      => $exception->getFile(),
                'line'      => $exception->getLine()
            ]);

            return $this->redirectToRoute('lender_operations');
        }
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
     */
    private function commonLoans(Request $request, Wallet $wallet): array
    {
        $filters      = $request->request->get('filter', []);
        $year         = isset($filters['date']) && false !== filter_var($filters['date'], FILTER_VALIDATE_INT) ? $filters['date'] : null;
        $statusFilter = isset($filters['status']) ? $filters['status'] : null;

        /** @var \loans $loan */
        $loan = $this->get('unilend.service.entity_manager')->getRepository('loans');
        try {
            $lenderLoans = $loan->getSumLoansByProject($wallet->getId(), 'debut ASC, title ASC', $year, true);
        } catch (\Exception $exception) {
            $lenderLoans = [];

            $this->get('logger')->error('Could not get lender loans. Error: ' . $exception->getMessage(), [
                'id_client' => $wallet->getIdClient()->getIdClient(),
                'class'     => __CLASS__,
                'function'  => __FUNCTION__,
                'file'      => $exception->getFile(),
                'line'      => $exception->getLine()
            ]);
        }

        $lenderLoansDisplayManager = $this->get(LenderLoansDisplayManager::class);

        return $lenderLoansDisplayManager->formatLenderLoansData($wallet, $lenderLoans, $statusFilter);
    }

    /**
     * @param Request $request
     * @param Clients $client
     *
     * @return array
     * @throws \Exception
     */
    private function getOperationFilters(Request $request, Clients $client): array
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

        $filters['id_client'] = $client->getIdClient();
        $filters['start']     = $filters['startDate']->format('d/m/Y');
        $filters['end']       = $filters['endDate']->format('d/m/Y');

        $session = $request->getSession();
        $session->set('lenderOperationsFilters', $filters);

        unset($filters['id_last_action']);

        return $filters;
    }

    /**
     * @Route("/operations/projectNotifications/{projectId}", name="lender_loans_notifications",
     *     requirements={"projectId": "\d+"}, methods={"GET"})
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param int                        $projectId
     * @param UserInterface|Clients|null $client
     *
     * @return JsonResponse
     */
    public function loadProjectNotificationsAction(int $projectId, ?UserInterface $client): JsonResponse
    {
        if (false === $client->isGrantedLenderRead()) {
            return $this->json(['tpl' => '']);
        }

        try {
            $data = $this->getProjectInformation($projectId, $client);
            $code = Response::HTTP_OK;
        } catch (\Exception $exception) {
            $data = [];
            $code = Response::HTTP_INTERNAL_SERVER_ERROR;
            $this->get('logger')->error('Exception while getting client notifications for id_project ' . $projectId . '. Message: ' . $exception->getMessage(), [
                'id_client' => $client->getIdClient(),
                'class'     => __CLASS__,
                'function'  => __FUNCTION__,
                'file'      => $exception->getFile(),
                'line'      => $exception->getLine()
            ]);
        }

        return $this->json(
            [
                'tpl' => $this->renderView('lender_operations/my_loans_details_activity.html.twig', ['projectNotifications' => $data, 'code' => $code]),
            ],
            $code
        );
    }

    /**
     * @param int     $projectId
     * @param Clients $client
     *
     * @return array
     */
    private function getProjectInformation(int $projectId, Clients $client): array
    {
        $entityManager   = $this->get('doctrine.orm.entity_manager');
        $translator      = $this->get('translator');
        $numberFormatter = $this->get('number_formatter');

        $operationRepository = $entityManager->getRepository(Operation::class);
        $project             = $entityManager->getRepository(Projects::class)->find($projectId);
        $wallet              = $entityManager->getRepository(Wallet::class)->getWalletByType($client, WalletType::LENDER);

        $data = [];

        $companyStatusHistoryContent = $entityManager
            ->getRepository(CompanyStatusHistory::class)
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
            ->getRepository(ProjectNotification::class)
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

        $capitalEarlyRepaymentType = $entityManager->getRepository(OperationSubType::class)->findOneBy(['label' => OperationSubType::CAPITAL_REPAYMENT_EARLY]);
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

        $capitalDebtCollectionRepaymentType = $entityManager->getRepository(OperationSubType::class)->findOneBy(['label' => OperationSubType::CAPITAL_REPAYMENT_DEBT_COLLECTION]);
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

        $capitalRepaymentType = $entityManager->getRepository(OperationType::class)->findOneBy(['label' => OperationType::CAPITAL_REPAYMENT]);
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

        $capitalRepaymentRegularizationType = $entityManager->getRepository(OperationType::class)->findOneBy(['label' => OperationType::CAPITAL_REPAYMENT_REGULARIZATION]);
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
        $bidsEntity                 = $entityManager->getRepository(Bids::class);
        $acceptedBidEntity          = $entityManager->getRepository(AcceptedBids::class);
        $acceptedLoansNotifications = $entityManager
            ->getRepository(Notifications::class)
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
                '%rate%'       => $ficelle->formatNumber($bid->getRate()->getMargin(), 1),
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
            $data[$index]['status'] = ($row['datetime'] > $client->getLastlogin()) ? 'unread' : 'read';
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
     * @param UserInterface|Clients|null $client
     *
     * @return Response
     */
    public function downloadOperationPdfAction(?UserInterface $client): Response
    {
        if (false === $client->isGrantedLenderRead()) {
            return $this->redirectToRoute('home');
        }

        $session = $this->get('session');

        if (false === $session->has('lenderOperationsFilters')) {
            return $this->redirectToRoute('lender_operations');
        }

        $entityManager           = $this->get('doctrine.orm.entity_manager');
        $lenderOperationsManager = $this->get('unilend.service.lender_operations_manager');
        /** @var Wallet $wallet */
        $wallet = $entityManager->getRepository(Wallet::class)->getWalletByType($client, WalletType::LENDER);

        if (false === $client->isNaturalPerson()) {
            $company = $entityManager->getRepository(Companies::class)->findOneBy(['idClientOwner' => $client]);
        }

        $filters    = $session->get('lenderOperationsFilters');
        $operations = $lenderOperationsManager->getOperationsAccordingToFilter($filters['operation']);
        try {
            $lenderOperations = $lenderOperationsManager->getLenderOperations($wallet, $filters['startDate'], $filters['endDate'], $filters['project'], $operations);
        } catch (\Exception $exception) {
            $lenderOperations = [];

            $this->get('logger')->error('Could not get lender operations to generate PDF. Error: ' . $exception->getMessage(), [
                'id_client' => $client->getIdClient(),
                'class'     => __CLASS__,
                'function'  => __FUNCTION__,
                'file'      => $exception->getFile(),
                'line'      => $exception->getLine()
            ]);
        }

        $pdfContent = $this->renderView('pdf/lender_operations.html.twig', [
            'lenderOperations'  => $lenderOperations,
            'client'            => $client,
            'lenderAddress'     => $this->getLenderAddress($client),
            'company'           => empty($company) ? null : $company,
            'available_balance' => $wallet->getAvailableBalance()
        ]);

        $snappy = $this->get('knp_snappy.pdf');

        return new Response(
            $snappy->getOutputFromHtml($pdfContent),
            Response::HTTP_OK,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => sprintf('attachment; filename="%s"', 'vos_operations_' . date('Y-m-d') . '.pdf')
            ]
        );
    }

    /**
     * @Route("/prets/pdf", name="lender_loans_pdf")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param UserInterface|Clients|null $client
     *
     * @return Response
     */
    public function downloadLoansPdfAction(?UserInterface $client): Response
    {
        if (false === $client->isGrantedLenderRead()) {
            return $this->redirectToRoute('home');
        }

        /** @var Wallet $wallet */
        $wallet = $this->get('doctrine.orm.entity_manager')->getRepository(Wallet::class)
            ->getWalletByType($client, WalletType::LENDER);
        /** @var \loans $loans */
        $loans                     = $this->get('unilend.service.entity_manager')->getRepository('loans');
        $lenderLoansDisplayManager = $this->get(LenderLoansDisplayManager::class);

        try {
            $lenderLoans = $loans->getSumLoansByProject($wallet->getId(), 'debut DESC, title ASC', null, true);
            $lenderLoans = $lenderLoansDisplayManager->formatLenderLoansForExport($lenderLoans);
        } catch (\Exception $exception) {
            $lenderLoans = [];

            $this->get('logger')->error('Could not get lender loans. Error: ' . $exception->getMessage(), [
                'id_client' => $wallet->getIdClient()->getIdClient(),
                'class'     => __CLASS__,
                'function'  => __FUNCTION__,
                'file'      => $exception->getFile(),
                'line'      => $exception->getLine()
            ]);
        }

        $pdfContent = $this->renderView('pdf/lender_loans.html.twig', [
            'lenderLoans'   => $lenderLoans,
            'client'        => $wallet->getIdClient(),
            'lenderAddress' => $this->getLenderAddress($wallet->getIdClient()),
            'company'       => empty($company) ? null : $company,
        ]);

        $snappy   = $this->get('knp_snappy.pdf');
        $fileName = 'vos_prets_' . date('Y-m-d') . '.pdf';

        return new Response(
            $snappy->getOutputFromHtml($pdfContent),
            Response::HTTP_OK,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => sprintf('attachment; filename="%s"', $fileName)
            ]
        );
    }

    /**
     * @param Clients $client
     *
     * @return ClientAddress|CompanyAddress|null
     */
    private function getLenderAddress(Clients $client)
    {
        try {
            $entityManager = $this->get('doctrine.orm.entity_manager');

            if ($client->isNaturalPerson()) {
                $lenderAddress = $entityManager->getRepository(ClientAddress::class)
                    ->findLastModifiedNotArchivedAddressByType($client, AddressType::TYPE_MAIN_ADDRESS);
            } else {
                $company       = $entityManager->getRepository(Companies::class)->findOneBy(['idClientOwner' => $client]);
                $lenderAddress = $entityManager->getRepository(CompanyAddress::class)
                    ->findLastModifiedNotArchivedAddressByType($company, AddressType::TYPE_MAIN_ADDRESS);
            }
        } catch (\Exception $exception) {
            $lenderAddress = null;

            $this->get('logger')->warning('Client has no main address. Error: ' . $exception->getMessage(), [
                'id_client'  => $client->getIdClient(),
                'id_company' => isset($company) ? $company->getIdCompany() : 'Lender is natural person',
                'class'      => __CLASS__,
                'function'   => __FUNCTION__,
                'file'       => $exception->getFile(),
                'line'       => $exception->getLine()
            ]);
        }

        return $lenderAddress;
    }
}
