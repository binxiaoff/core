<?php

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Unilend\Bundle\CoreBusinessBundle\Entity\{DebtCollectionFeeDetail, EcheanciersEmprunteur, ProjectRepaymentTask, Projects, ProjectsStatus, Receptions, Zones};
use Unilend\Bundle\CoreBusinessBundle\Service\{BackOfficeUserManager, ProjectCloseOutNettingManager, Repayment\ProjectCloseOutNettingPaymentManager, Repayment\ProjectPaymentManager};

class remboursementController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->users->checkAccess(Zones::ZONE_LABEL_REPAYMENT);
        $this->menu_admin = 'remboursement';

        /** @var \Symfony\Component\Translation\TranslatorInterface translator */
        $this->translator = $this->get('translator');
    }

    public function _validation()
    {
        /** @var EntityManager $entityManager */
        $entityManager        = $this->get('doctrine.orm.entity_manager');
        $receptionsToValidate = $entityManager->getRepository('UnilendCoreBusinessBundle:Receptions')
            ->findReceptionsWithPendingRepaymentTasks();
        $this->render(null, ['receptionsToValidate' => $receptionsToValidate]);

        return;
    }

    public function _planifier()
    {
        if (empty($this->params[0])) {
            header('Location: ' . $this->url);
            die;
        }

        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var BackOfficeUserManager $userManager */
        $userManager = $this->get('unilend.service.back_office_user_manager');
        $session     = $this->get('session');

        $projectChargeRepository         = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectCharge');
        $debtCollectionMissionRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:DebtCollectionMission');
        $receptionRepository             = $entityManager->getRepository('UnilendCoreBusinessBundle:Receptions');

        $receptionId = filter_var($this->params[0], FILTER_VALIDATE_INT);
        $reception   = $receptionRepository->find($receptionId);
        if (null === $reception) {
            header('Location: ' . $this->url);
            die;
        }
        if (null === $reception->getIdProject()) {
            header('Location: ' . $this->url);
            die;
        }

        if (Receptions::DIRECT_DEBIT_STATUS_REJECTED === $reception->getStatusPrelevement() || Receptions::WIRE_TRANSFER_STATUS_REJECTED === $reception->getStatusVirement()) {
            header('Location: ' . $this->url);
            die;
        }

        $rejectedReception = $receptionRepository->findOneBy(['idReceptionRejected' => $reception]);
        if ($rejectedReception) {
            header('Location: ' . $this->url);
            die;
        }

        $repaymentTask = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectRepaymentTask')->findOneBy([
            'idWireTransferIn' => $reception,
            'status'           => [
                ProjectRepaymentTask::STATUS_PENDING,
                ProjectRepaymentTask::STATUS_READY,
                ProjectRepaymentTask::STATUS_IN_PROGRESS,
                ProjectRepaymentTask::STATUS_REPAID
            ]
        ]);
        if ($repaymentTask) {
            header('Location: ' . $this->url);
            die;
        }

        $debtCollectionMissions = $reception->getIdProject()->getDebtCollectionMissions(true, ['id' => 'DESC']);

        if ($this->request->isMethod(Request::METHOD_POST)) {
            $errors                = [];
            $projectChargesToApply = [];
            if ($this->request->request->get('charges')) {
                $projectChargesToApply = $projectChargeRepository->findBy(['id' => $this->request->request->get('charges')]);
            }

            $debtCollectionMission = null;
            $debtCollectionFeeRate = null;

            if (count($debtCollectionMissions) > 0) {
                if ($this->request->request->get('mission')) {
                    $debtCollectionMissionId = filter_var($this->request->request->get('mission'), FILTER_VALIDATE_INT);
                    $debtCollectionMission   = $debtCollectionMissionRepository->find($debtCollectionMissionId);
                    if (null === $debtCollectionMission) {
                        $errors[] = 'Id mission recouvrement ' . $debtCollectionMissionId . 'n\'existe pas';
                    }
                    if (false === $this->request->request->getBoolean('debt-collection-zero-rate')) {
                        $debtCollectionFeeRate = str_replace(',', '.', $this->request->request->get('fee_rate'));
                        $debtCollectionFeeRate = filter_var($debtCollectionFeeRate, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                        if (false === $debtCollectionFeeRate || $debtCollectionFeeRate > 100 || $debtCollectionFeeRate <= 0) {
                            $errors[] = 'Le taux d\'honoraires  n\'est pas valide';
                        }
                        $debtCollectionFeeRate = round(bcdiv($debtCollectionFeeRate, 100, 6), 4);
                    } else {
                        $debtCollectionFeeRate = 0;
                    }
                } else {
                    $activeDebtCollectionMissions = $reception->getIdProject()->getDebtCollectionMissions(false, ['id' => 'DESC']);
                    // Only display error when we having ongoing debt collection mission. Because a project may have the mission in the past, but it has been back to the normal repayment
                    if (count($activeDebtCollectionMissions) > 0) {
                        $errors[] = 'Impossible de récupérer la mission de recouvrement';
                    }
                }
            }

            $repayOn = DateTime::createFromFormat('d/m/Y', $this->request->request->get('repay_on'));
            if (
                false === $repayOn
                || ($repayOn->format('Y-m-d') < $this->getRepaymentMinDate($reception)->format('Y-m-d') && false === $userManager->isGrantedRisk($this->userEntity))
            ) {
                $errors[] = 'La date de remboursement n\'est pas valide';
            }

            if (empty($errors)) {
                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\Repayment\ProjectRepaymentTaskManager $projectRepaymentTaskManager */
                $projectRepaymentTaskManager = $this->get('unilend.service_repayment.project_repayment_task_manager');

                /** @var Projects $project */
                $project       = $reception->getIdProject();
                $repaymentType = $this->request->request->getInt('repayment_type');
                try {
                    switch ($repaymentType) {
                        case ProjectRepaymentTask::TYPE_REGULAR:
                        case ProjectRepaymentTask::TYPE_LATE:
                            if (null === $project->getCloseOutNettingDate()) {
                                /** @var ProjectPaymentManager $paymentManager */
                                $paymentManager = $this->get('unilend.service_repayment.project_payment_manager');
                                $paymentManager->pay($reception, $this->userEntity, $repayOn, $debtCollectionMission, $debtCollectionFeeRate, $projectChargesToApply, $repaymentType);
                            } else {
                                throw new Exception('Le type de remboursement n\'est pas valide.');
                            }
                            break;
                        case ProjectRepaymentTask::TYPE_EARLY:
                            if (null === $project->getCloseOutNettingDate()) {
                                $projectRepaymentTaskManager->planEarlyRepaymentTask($project, $reception, $this->userEntity, $repayOn);
                            } else {
                                throw new Exception('Le type de remboursement n\'est pas valide.');
                            }
                            break;
                        default:
                            if ($project->getCloseOutNettingDate()) {
                                /** @var ProjectCloseOutNettingPaymentManager $paymentManager */
                                $paymentManager = $this->get('unilend.service_repayment.project_close_out_netting_payment_manager');
                                $paymentManager->pay($reception, $this->userEntity, $repayOn, $debtCollectionMission, $debtCollectionFeeRate, $projectChargesToApply);
                            } else {
                                throw new Exception('Le type de remboursement n\'est pas valide.');
                            }
                            break;
                    }

                    /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager $projectManager */
                    $projectManager = $this->get('unilend.service.project_manager');

                    if ($projectManager->isHealthy($project)) {
                        $projectRepaymentTaskManager->enableAutomaticRepayment($project, $this->userEntity);
                        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectStatusManager $projectStatusManager */
                        $projectStatusManager = $this->get('unilend.service.project_status_manager');
                        $projectStatusManager->addProjectStatus($this->userEntity, ProjectsStatus::REMBOURSEMENT, $project);
                    }

                } catch (Exception $exception) {
                    /** @var \Psr\Log\LoggerInterface $logger */
                    $logger = $this->get('logger');
                    $logger->error('Exception occurs when plan a repayment task. Error : ' . $exception->getMessage(), [
                        'method' => __METHOD__,
                        'file'   => $exception->getFile(),
                        'line'   => $exception->getLine()
                    ]);

                    $session->getFlashBag()->add('repayment_task_error', $exception->getMessage());
                    header('Location: ' . $this->url . '/remboursement/planifier/' . $receptionId);
                    die;
                }
                $session->getFlashBag()->add('repayment_task_info', 'Le remboursement est créé, il est en attente de validation.');

                header('Location: ' . $this->url . '/remboursement/confirmation/' . $receptionId);
                die;
            } else {
                foreach ($errors as $error) {
                    $session->getFlashBag()->add('repayment_task_error', $error);
                }

                header('Location: ' . $this->url . '/remboursement/planifier/' . $receptionId);
                die;
            }
        }

        $nextRepaymentSchedule = $entityManager->getRepository('UnilendCoreBusinessBundle:Echeanciers')->findNextPendingScheduleAfter(new DateTime(), $reception->getIdProject());

        $ongoingProjectRepaymentTask = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectRepaymentTask')->findOneBy([
            'idProject' => $reception->getIdProject(),
            'status'    => ProjectRepaymentTask::STATUS_IN_PROGRESS
        ]);

        $hasOngoingProjectRepaymentTask = null !== $ongoingProjectRepaymentTask;

        $projectCharges = $projectChargeRepository->findBy(['idProject' => $reception->getIdProject(), 'idWireTransferIn' => null]);
        $projectStatus  = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsStatus')->findOneBy(['status' => $reception->getIdProject()->getStatus()]);

        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\DebtCollectionMissionManager $debtCollectionMissionManager */
        $debtCollectionMissionManager = $this->get('unilend.service.debt_collection_mission_manager');

        $this->render(null, [
            'isDebtCollectionFeeDueToBorrower' => $debtCollectionMissionManager->isDebtCollectionFeeDueToBorrower($reception->getIdProject()),
            'reception'                        => $reception,
            'charges'                          => $projectCharges,
            'missions'                         => $debtCollectionMissions,
            'projectStatus'                    => $projectStatus->getLabel(),
            'repaymentMinDate'                 => $this->getRepaymentMinDate($reception),
            'canBypassDateRestriction'         => $this->get('unilend.service.back_office_user_manager')->isGrantedRisk($this->userEntity),
            'session'                          => $session,
            'hasOngoingProjectRepaymentTask'   => $hasOngoingProjectRepaymentTask,
            'nextRepaymentSchedule'            => $nextRepaymentSchedule,
        ]);
    }

    /**
     * @param Receptions $reception
     *
     * @return DateTime
     */
    private function getRepaymentMinDate(Receptions $reception): DateTime
    {
        $repaymentMinDate = new DateTime();

        if (Receptions::TYPE_DIRECT_DEBIT === $reception->getType()) {
            $repaymentMinDate = clone $reception->getAdded();
            $repaymentMinDate->modify('+8 weeks');

            if ($repaymentMinDate < new DateTime()) {
                $repaymentMinDate = new DateTime();
            }
        }

        return $repaymentMinDate;
    }

    public function _confirmation()
    {
        if (empty($this->params[0])) {
            header('Location: ' . $this->url);
            die;
        }

        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        $receptionId = filter_var($this->params[0], FILTER_VALIDATE_INT);
        $reception   = $entityManager->getRepository('UnilendCoreBusinessBundle:Receptions')->find($receptionId);
        if (null === $reception) {
            header('Location: ' . $this->url);
            die;
        }

        $projectRepaymentTaskRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectRepaymentTask');
        $projectRepaymentTasks          = $projectRepaymentTaskRepository->findBy([
            'idWireTransferIn' => $reception,
            'status'           => [
                ProjectRepaymentTask::STATUS_PENDING,
                ProjectRepaymentTask::STATUS_READY,
                ProjectRepaymentTask::STATUS_IN_PROGRESS,
                ProjectRepaymentTask::STATUS_ERROR,
                ProjectRepaymentTask::STATUS_REPAID
            ]
        ]);

        if (0 === count($projectRepaymentTasks)) {
            header('Location: ' . $this->url);
            die;
        }

        if ($this->request->request->get('cancel')) {
            if ($reception->getIdProject()->getCloseOutNettingDate()) {
                /** @var ProjectCloseOutNettingPaymentManager $paymentManager */
                $paymentManager = $this->get('unilend.service_repayment.project_close_out_netting_payment_manager');
            } else {
                /** @var ProjectPaymentManager $paymentManager */
                $paymentManager = $this->get('unilend.service_repayment.project_payment_manager');
            }
            $paymentManager->rejectPayment($reception, $this->userEntity);

            header('Location: ' . $this->url . '/remboursement/projet/' . $reception->getIdProject()->getIdProject());
        }

        if ($this->request->request->get('validate')) {
            foreach ($projectRepaymentTasks as $projectRepaymentTask) {
                $projectRepaymentTask->setStatus(ProjectRepaymentTask::STATUS_READY)
                    ->setIdUserValidation($this->userEntity);

                $entityManager->flush($projectRepaymentTask);

                header('Location: ' . $this->url . '/remboursement/confirmation/' . $receptionId);
            }
        }

        /** @var \Symfony\Component\HttpFoundation\Session\Session $session */
        $session = $this->get('session');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\DebtCollectionMissionManager $debtCollectionMissionManager */
        $debtCollectionMissionManager = $this->get('unilend.service.debt_collection_mission_manager');

        $debtCollectionFeeDetailRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:DebtCollectionFeeDetail');
        $projectChargeRepository           = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectCharge');

        $feeOnLoan       = $debtCollectionFeeDetailRepository->getAmountsByTypeAndWireTransferIn(DebtCollectionFeeDetail::TYPE_LOAN, $reception);
        $feeOnCommission = $debtCollectionFeeDetailRepository->getAmountsByTypeAndWireTransferIn(DebtCollectionFeeDetail::TYPE_REPAYMENT_COMMISSION, $reception);
        $feeOnCharge     = $debtCollectionFeeDetailRepository->getAmountsByTypeAndWireTransferIn(DebtCollectionFeeDetail::TYPE_PROJECT_CHARGE, $reception);
        $projectCharge   = $projectChargeRepository->getTotalChargeByWireTransferIn($reception);
        $commission      = $projectRepaymentTaskRepository->getTotalCommissionByWireTransferIn($reception);
        $totalRepayment  = $projectRepaymentTaskRepository->getTotalRepaymentByWireTransferIn($reception);

        $paidPaymentSchedules   = [];
        $closeOutNettingPayment = null;
        $remainingCapital       = 0;
        if ($reception->getIdProject()->getCloseOutNettingDate()) {
            $closeOutNettingPayment = $entityManager->getRepository('UnilendCoreBusinessBundle:CloseOutNettingPayment')->findOneBy(['idProject' => $reception->getIdProject()]);
        } else {
            $firstProjectRepaymentTask = current($projectRepaymentTasks);
            // All tasks created by a wire transfer have the same type.
            if (ProjectRepaymentTask::TYPE_EARLY !== $firstProjectRepaymentTask->getType()) {
                $sequences = [];
                foreach ($projectRepaymentTasks as $task) {
                    $sequences[] = $task->getSequence();
                }

                $paidPaymentSchedules = $entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur')->findBy(['idProject' => $reception->getIdProject(), 'ordre' => $sequences]);
            } else {
                $nextRepayment = $entityManager->getRepository('UnilendCoreBusinessBundle:Echeanciers')
                    ->findNextPendingScheduleAfter($firstProjectRepaymentTask->getRepayAt(), $reception->getIdProject());

                if ($nextRepayment) {
                    $remainingCapital = $entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur')
                        ->getRemainingCapitalFrom($reception->getIdProject(), $nextRepayment->getOrdre());
                }
            }
        }
        $validated = true;
        foreach ($projectRepaymentTasks as $projectRepaymentTask) {
            if (ProjectRepaymentTask::STATUS_PENDING === $projectRepaymentTask->getStatus()) {
                $validated = false;
                break;
            }
        }

        $this->render(null, [
            'isDebtCollectionFeeDueToBorrower' => $debtCollectionMissionManager->isDebtCollectionFeeDueToBorrower($reception->getIdProject()),
            'reception'                        => $reception,
            'session'                          => $session,
            'feeOnLoan'                        => $feeOnLoan,
            'feeOnCommission'                  => $feeOnCommission,
            'feeOnCharge'                      => $feeOnCharge,
            'projectCharge'                    => $projectCharge,
            'commission'                       => $commission,
            'totalRepayment'                   => $totalRepayment,
            'paidPaymentSchedules'             => $paidPaymentSchedules,
            'repaymentTaskValidated'           => $validated,
            'closeOutNettingPayment'           => $closeOutNettingPayment,
            'projectStatus'                    => $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsStatus')->findOneBy(['status' => $reception->getIdProject()->getStatus()]),
            'remainingCapital'                 => $remainingCapital,
            'plannedRepaymentTasks'            => $projectRepaymentTasks,
        ]);
    }

    public function _projet()
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        if (false === empty($this->params[0])) {
            $projectRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects');

            $projectId       = filter_var($this->params[0], FILTER_VALIDATE_INT);
            $latePaymentData = [];
            $project         = $projectRepository->find($projectId);

            if (null !== $project && $project->getStatus() >= ProjectsStatus::REMBOURSEMENT) {
                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager $projectManager */
                $projectManager = $this->get('unilend.service.project_manager');
                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectCloseOutNettingManager $projectCloseOutNettingManager */
                $projectCloseOutNettingManager = $this->get('unilend.service.project_close_out_netting_manager');

                $projectStatus            = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsStatus')->findOneBy(['status' => $project->getStatus()]);
                $lastCompanyStatus        = $entityManager->getRepository('UnilendCoreBusinessBundle:CompanyStatusHistory')->findOneBy(['idCompany' => $project->getIdCompany()], ['added' => 'DESC']);
                $totalOverdueAmounts      = $projectManager->getOverdueAmounts($project);
                $totalOverdueAmount       = round(bcadd(bcadd($totalOverdueAmounts['capital'], $totalOverdueAmounts['interest'], 4), $totalOverdueAmounts['commission'], 4), 2);
                $currentMission           = $entityManager->getRepository('UnilendCoreBusinessBundle:DebtCollectionMission')->findOneBy(['idProject' => $project, 'archived' => null]);
                $entrustedToDebtCollector = 0;
                if (null !== $currentMission) {
                    $entrustedToDebtCollector = round(bcadd($currentMission->getCapital(), bcadd($currentMission->getInterest(), $currentMission->getCommissionVatIncl(), 4), 4), 2);
                }

                $unpaidScheduleCount   = 0;
                $paidScheduleCount     = 0;
                $paidScheduledAmount   = 0;
                $unpaidScheduledAmount = 0;
                $futurSchedule         = null;
                $paymentSchedules      = [];

                if (null === $project->getCloseOutNettingDate()) {
                    $latePaymentData           = $this->getLatePaymentsData($project);
                    $paymentScheduleRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur');
                    $unpaidScheduleCount       = count($paymentScheduleRepository->findBy([
                        'idProject'        => $project,
                        'statusEmprunteur' => [
                            EcheanciersEmprunteur::STATUS_PENDING,
                            EcheanciersEmprunteur::STATUS_PARTIALLY_PAID
                        ]
                    ]));
                    $paidScheduleCount         = count($paymentScheduleRepository->findBy(['idProject' => $project, 'statusEmprunteur' => EcheanciersEmprunteur::STATUS_PAID]));
                    $paidScheduledAmount       = $paymentScheduleRepository->getPaidScheduledAmount($project);
                    $unpaidScheduledAmount     = $paymentScheduleRepository->getUnpaidScheduledAmount($project);
                    $futurSchedule             = $paymentScheduleRepository->getNextPaymentSchedule($project);
                    foreach ($paymentScheduleRepository->findBy(['idProject' => $project]) as $paymentSchedule) {
                        $paymentSchedules[$paymentSchedule->getOrdre()] = $paymentSchedule;
                    }
                } else {
                    $closeOutNettingPayment = $entityManager->getRepository('UnilendCoreBusinessBundle:CloseOutNettingPayment')->findOneBy(['idProject' => $project]);
                    $totalAmount            = round(bcadd($closeOutNettingPayment->getCommissionTaxIncl(), bcadd($closeOutNettingPayment->getCapital(), $closeOutNettingPayment->getInterest(), 4),
                        4), 2);
                    $paidAmount             = round(bcadd($closeOutNettingPayment->getPaidCommissionTaxIncl(),
                        bcadd($closeOutNettingPayment->getPaidCapital(), $closeOutNettingPayment->getPaidInterest(), 4), 4), 2);

                    $latePaymentData[] = [
                        'date'                     => $project->getCloseOutNettingDate(),
                        'amount'                   => $totalAmount,
                        'entrustedToDebtCollector' => (0 == $entrustedToDebtCollector) ? 'Non' : ($entrustedToDebtCollector < $totalOverdueAmount ? 'Partiellement' : 'Oui'),
                        'remainingAmount'          => round(bcsub($totalAmount, $paidAmount, 4), 2)
                    ];
                }

                $debtCollectionMissions = $project->getDebtCollectionMissions(true, ['id' => 'DESC']);
                $pendingWireTransferIn  = $entityManager->getRepository('UnilendCoreBusinessBundle:Receptions')->findPendingWireTransferIn($project);
                $plannedRepaymentTasks  = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectRepaymentTask')->findBy([
                    'idProject' => $project,
                    'status'    => ProjectRepaymentTask::STATUS_PLANNED
                ]);

                $templateData = [
                    'project'                    => $project,
                    'projectStatus'              => $projectStatus,
                    'companyLastStatusHistory'   => $lastCompanyStatus,
                    'totalOverdueAmount'         => $totalOverdueAmount,
                    'entrustedToDebtCollector'   => $entrustedToDebtCollector,
                    'canBeDeclined'              => $projectCloseOutNettingManager->canBeDeclined($project),
                    'latePaymentsData'           => $latePaymentData,
                    'debtCollector'              => $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')
                        ->findOneBy(['hash' => \Unilend\Bundle\CoreBusinessBundle\Service\DebtCollectionMissionManager::CLIENT_HASH_PROGERIS]),
                    'debtCollectionMissionsData' => $debtCollectionMissions,
                    'pendingWireTransferIn'      => $pendingWireTransferIn,
                    'projectCharges'             => $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectCharge')->findBy(['idProject' => $project]),
                    'projectChargeTypes'         => $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectChargeType')->findAll(),
                    'futurSchedule'              => $futurSchedule,
                    'paidScheduleCount'          => $paidScheduleCount,
                    'futureScheduleCount'        => $unpaidScheduleCount - count($latePaymentData),
                    'futureScheduledAmount'      => round(bcsub($unpaidScheduledAmount, $totalOverdueAmount, 4), 2),
                    'paidScheduledAmount'        => $paidScheduledAmount,
                    'plannedRepaymentTasks'      => $plannedRepaymentTasks,
                    'lenderCount'                => $entityManager->getRepository('UnilendCoreBusinessBundle:Loans')->getLenderNumber($project),
                    'paymentSchedules'           => $paymentSchedules,
                    'repaymentProjectStatus'     => ProjectsStatus::FUNDE.','.ProjectsStatus::REMBOURSEMENT.','.ProjectsStatus::REMBOURSE.','.ProjectsStatus::REMBOURSEMENT_ANTICIPE
                ];

                $this->render(null, $templateData);
                return;
            }
        }
        header('Location: ' . $this->lurl . '/dossiers');
        die;
    }

    public function _switch_auto_repayment_ajax()
    {
        $this->autoFireView = false;
        $this->hideDecoration();
        $errors = [];

        if ($this->request->isXmlHttpRequest() && $this->request->isMethod(\Symfony\Component\HttpFoundation\Request::METHOD_POST)) {
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager = $this->get('doctrine.orm.entity_manager');
            $switch        = $this->request->request->getBoolean('switch');

            if (null === $switch || empty($this->params[0])) {
                $this->sendAjaxResponse(false, null, ['paramètres invalides.']);
            }

            $projectId = filter_var($this->params[0], FILTER_VALIDATE_INT);

            if (false === $projectId) {
                $this->sendAjaxResponse(false, null, ['paramètres invalides.']);
            }

            $project = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($projectId);

            if (null === $project) {
                $this->sendAjaxResponse(false, null, ['paramètres invalides.']);
            }

            if (ProjectsStatus::PROBLEME == $project->getStatus()) {
                $this->sendAjaxResponse(false, null, ['Un projet en statut problème ne peut pas être mis en remboursement automatique']);
            }

            try {
                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\Repayment\ProjectRepaymentTaskManager $projectRepaymentTaskManager */
                $projectRepaymentTaskManager = $this->get('unilend.service_repayment.project_repayment_task_manager');

                if ($switch) {
                    $projectRepaymentTaskManager->enableAutomaticRepayment($project, $this->userEntity);
                } else {
                    $projectRepaymentTaskManager->disableAutomaticRepayment($project, $this->userEntity);
                }

                $this->sendAjaxResponse(true);
            } catch (Exception $exception) {
                $errors[] = $exception->getMessage();
                $this->get('logger')->error($exception->getMessage(), [
                    'class'      => __CLASS__,
                    'function'   => __FUNCTION__,
                    'file'       => $exception->getFile(),
                    'line'       => $exception->getLine(),
                    'id_project' => $project->getIdProject()
                ]);
            }
        }

        $this->sendAjaxResponse(false, $errors, ['paramètres ou requête incorrects']);
    }

    public function _dechoir_terme()
    {
        $projectId                = $this->request->request->getInt('project_id');
        $includeUnilendCommission = $this->request->request->getBoolean('include_unilend_commission');
        $sendLendersEmail         = $this->request->request->getBoolean('send_lenders_email');
        $sendBorrowerEmail        = $this->request->request->getBoolean('send_borrower_email');
        $lendersEmailContent      = $this->request->request->filter('lenders_email_content', null, FILTER_SANITIZE_STRING);
        $borrowerEmailContent     = $this->request->request->filter('borrower_email_content', null, FILTER_SANITIZE_STRING);

        if ($lendersEmailContent) {
            $lendersEmailContent = trim($lendersEmailContent);
        }
        if ($borrowerEmailContent) {
            $borrowerEmailContent = trim($borrowerEmailContent);
        }

        /** @var BackOfficeUserManager $userManager */
        $userManager = $this->get('unilend.service.back_office_user_manager');

        if ($userManager->isGrantedRisk($this->userEntity) && $projectId) {
            /** @var EntityManager $entityManager */
            $entityManager = $this->get('doctrine.orm.entity_manager');
            $project       = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($projectId);
            /** @var ProjectCloseOutNettingManager $projectCloseOutNettingManager */
            $projectCloseOutNettingManager = $this->get('unilend.service.project_close_out_netting_manager');

            if ($project && $projectCloseOutNettingManager->canBeDeclined($project)) {
                try {
                    $projectCloseOutNettingManager->decline($project, new \DateTime(), $includeUnilendCommission, $sendLendersEmail, $sendBorrowerEmail, $lendersEmailContent, $borrowerEmailContent);
                } catch (\Exception $exception) {
                    $this->get('logger')->error('Error while trying to decline project ' . $project->getIdProject() . ' - Message: ' . $exception->getMessage(), [
                        'class'    => __CLASS__,
                        'function' => __FUNCTION__,
                        'file'     => $exception->getFile(),
                        'line'     => $exception->getLine(),
                    ]);
                    $_SESSION['freeow']['title']   = 'Déchéance du terme';
                    $_SESSION['freeow']['message'] = 'L\'opétation a échoué.';
                }
            }
        }

        header('Location: ' . $this->url . '/remboursement/projet/' . $projectId);
        die;
    }

    /**
     * @param Projects $project
     *
     * @return array
     */
    private function getLatePaymentsData(Projects $project)
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager                    = $this->get('doctrine.orm.entity_manager');
        $paymentRepository                = $entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur');
        $missionPaymentScheduleRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:DebtCollectionMissionPaymentSchedule');
        $latePaymentData                  = [];

        $pendingPayments = $paymentRepository->findBy(
            [
                'idProject'        => $project,
                'statusEmprunteur' => [EcheanciersEmprunteur::STATUS_PENDING, EcheanciersEmprunteur::STATUS_PARTIALLY_PAID]
            ],
            ['dateEcheanceEmprunteur' => 'ASC']
        );
        $yesterday       = (new \DateTime('yesterday 23:59:59'));

        foreach ($pendingPayments as $payment) {
            if ($payment->getDateEcheanceEmprunteur() > $yesterday) {
                continue;
            }
            $paymentAmount            = round(bcdiv(bcadd(bcadd(bcadd($payment->getCapital(), $payment->getInterets()), $payment->getCommission()), $payment->getTva()), 100, 4), 2);
            $paidAmount               = round(bcdiv(bcadd(bcadd($payment->getPaidCapital(), $payment->getPaidInterest()), $payment->getPaidCommissionVatIncl()), 100, 4), 2);
            $remainingAmount          = bcsub($paymentAmount, $paidAmount, 4);
            $entrustedToDebtCollector = $missionPaymentScheduleRepository->findBy([
                'idMission'         => $project->getDebtCollectionMissions()->toArray(),
                'idPaymentSchedule' => $payment->getIdEcheancierEmprunteur()
            ]);

            $latePaymentData[] = [
                'date'                     => $payment->getDateEcheanceEmprunteur(),
                'amount'                   => $paymentAmount,
                'entrustedToDebtCollector' => empty($entrustedToDebtCollector) ? 'Non' : 'Oui',
                'remainingAmount'          => round($remainingAmount, 2)
            ];
        }
        return $latePaymentData;
    }
}
