<?php

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\DebtCollectionFeeDetail;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRepaymentTask;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\Receptions;
use Unilend\Bundle\CoreBusinessBundle\Entity\Zones;
use Unilend\Bundle\CoreBusinessBundle\Service\Repayment\ProjectCloseOutNettingPaymentManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Repayment\ProjectPaymentManager;

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
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\BackOfficeUserManager $userManager */
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

        if ($this->request->isMethod('POST')) {
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
                    $debtCollectionFeeRate = str_replace(',', '.', $this->request->request->get('fee_rate'));
                    $debtCollectionFeeRate = filter_var($debtCollectionFeeRate, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                    if (false === $debtCollectionFeeRate || $debtCollectionFeeRate > 100) {
                        $errors[] = 'Le taux d\'honoraires  n\'est pas valide';
                    }
                    $debtCollectionFeeRate = round(bcdiv($debtCollectionFeeRate, 100, 6), 4);
                } else {
                    $errors[] = 'Mission recouvrement n\'est pas défini.';
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
                /** @var Projects $project */
                $project = $reception->getIdProject();

                if ($project->getCloseOutNettingDate()) {
                    /** @var ProjectCloseOutNettingPaymentManager $paymentManager */
                    $paymentManager = $this->get('unilend.service_repayment.project_close_out_netting_payment_manager');
                } else {
                    /** @var ProjectPaymentManager $paymentManager */
                    $paymentManager = $this->get('unilend.service_repayment.project_payment_manager');
                }
                $paymentManager->pay($reception, $this->userEntity, $repayOn, $debtCollectionMission, $debtCollectionFeeRate, $projectChargesToApply);

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
        ]);
    }

    /**
     * @param Receptions $reception
     *
     * @return DateTime
     */
    private function getRepaymentMinDate(Receptions $reception) : DateTime
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
        $projectRepaymentTasks          = $projectRepaymentTaskRepository->findBy(['idWireTransferIn' => $reception, 'status' => [
            ProjectRepaymentTask::STATUS_PENDING,
            ProjectRepaymentTask::STATUS_READY,
            ProjectRepaymentTask::STATUS_IN_PROGRESS,
            ProjectRepaymentTask::STATUS_ERROR,
            ProjectRepaymentTask::STATUS_REPAID
        ]]);

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

            header('Location: ' . $this->url . '/dossiers/details_impayes/' . $reception->getIdProject()->getIdProject());
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
        if ($reception->getIdProject()->getCloseOutNettingDate()) {
            $closeOutNettingPayment = $entityManager->getRepository('UnilendCoreBusinessBundle:CloseOutNettingPayment')->findOneBy(['idProject' => $reception->getIdProject()]);
        } else {
            $sequences = [];
            foreach ($projectRepaymentTasks as $task) {
                $sequences[] = $task->getSequence();
            }

            $paidPaymentSchedules = $entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur')->findBy(['idProject' => $reception->getIdProject(), 'ordre' => $sequences]);
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
        ]);
    }
}
