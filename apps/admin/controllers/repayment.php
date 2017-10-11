<?php

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\Zones;
use Unilend\Bundle\CoreBusinessBundle\Service\Repayment\ProjectCloseOutNettingPaymentManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Repayment\ProjectPaymentManager;

class repaymentController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->users->checkAccess(Zones::ZONE_LABEL_TRANSFERS);
        $this->menu_admin = 'remboursements';

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

    public function _create()
    {
        if (
            $this->request->request->get('reception_id')
            && $this->request->request->get('charges')
            && $this->request->request->get('debt_collector_mission_id')
            && $this->request->request->get('fee_rate')
            && $this->request->request->get('repay_on')
        ) {
            /** @var EntityManager $entityManager */
            $entityManager = $this->get('doctrine.orm.entity_manager');

            $errors = [];

            $receptionId = filter_var($this->request->request->get('reception_id'), FILTER_VALIDATE_INT);
            $reception   = $entityManager->getRepository('UnilendCoreBusinessBundle:Receptions')->find($receptionId);
            if (null === $reception) {
                $errors[] = 'Id réception ' . $receptionId . 'n\'existe pas';
            }
            if (null === $reception->getIdProject()) {
                $errors[] = 'La réception id ' . $receptionId . ' n\'a pas été attribué à un projet';
            }

            $projectCharges = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectCharge')->findBy(['id' => $this->request->request->get('charges')]);

            $debtCollectionMissionId = filter_var($this->request->request->get('debt_collector_mission_id'), FILTER_VALIDATE_INT);
            $debtCollectionMission   = $entityManager->getRepository('UnilendCoreBusinessBundle:DebtCollectionMission')->find($debtCollectionMissionId);
            if (null === $debtCollectionMission) {
                $errors[] = 'Id mission recouvrement ' . $debtCollectionMissionId . 'n\'existe pas';
            }

            $debtCollectionFeeRate = filter_var($this->request->request->get('fee_rate'), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            if (false === $debtCollectionFeeRate) {
                $errors[] = 'Le taux d\'honoraires  n\'est pas valide';
            }
            $debtCollectionFeeRate = round(bcdiv($debtCollectionFeeRate, 100, 6), 4);

            $repayOn = DateTime::createFromFormat('Y-m-d', $this->request->request->get('repay_on'));
            if (false === $repayOn) {
                $errors[] = 'La date de remboursement n\'est pas valide';
            }

            if (empty($errors)) {
                /** @var Projects $project */
                $project = $reception->getIdProject();
                /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Users $user */
                $user = $entityManager->getRepository('UnilendCoreBusinessBundle:Users')->find($_SESSION['user']['id_user']);
                if ($project->getCloseOutNettingDate()) {
                    /** @var ProjectCloseOutNettingPaymentManager $paymentManager */
                    $paymentManager = $this->get('unilend.service_repayment.project_close_out_netting_payment_manager');
                } else {
                    /** @var ProjectPaymentManager $paymentManager */
                    $paymentManager = $this->get('unilend.service_repayment.project_payment_manager');
                }
                $paymentManager->pay($reception, $user, $repayOn, $debtCollectionMission, $debtCollectionFeeRate, $projectCharges);
            } else {
                foreach ($errors as $error) {
                    $this->get('session')->getFlashBag()->add('error', $error);
                }
            }
        }
    }
}
