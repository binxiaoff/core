<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\{
    Input\InputInterface, Output\OutputInterface
};
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    ClientsStatus, Notifications, ProjectRepaymentDetail, ProjectRepaymentTask
};

class EmailLenderAutomaticRepaymentCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('email:lender:repayment_email')
            ->setDescription('For automatic repayments: creates repayment notifications and sends email if settings is on immediate');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entityManagerSimulator             = $this->getContainer()->get('unilend.service.entity_manager');
        $entityManager                      = $this->getContainer()->get('doctrine.orm.entity_manager');
        $projectRepaymentNotificationSender = $this->getContainer()->get('unilend.service_repayment.project_repayment_notification_sender');
        $projectRepaymentTaskManager        = $this->getContainer()->get('unilend.service_repayment.project_repayment_task_manager');

        $operationRepository            = $entityManager->getRepository('UnilendCoreBusinessBundle:Operation');
        $walletBalanceHistoryRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:WalletBalanceHistory');
        /** @var \notifications $notifications */
        $notifications = $entityManagerSimulator->getRepository('notifications');
        /** @var \clients_gestion_notifications $clients_gestion_notifications */
        $clients_gestion_notifications = $entityManagerSimulator->getRepository('clients_gestion_notifications');
        /** @var \clients_gestion_mails_notif $clients_gestion_mails_notif */
        $clients_gestion_mails_notif = $entityManagerSimulator->getRepository('clients_gestion_mails_notif');
        /** @var ProjectRepaymentDetail[] $repaymentDetails */
        $repaymentDetails = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectRepaymentDetail')->findBy(['status' => ProjectRepaymentDetail::STATUS_TREATED], null, 500);

        $emailNB = 0;

        foreach ($repaymentDetails as $repaymentDetail) {
            if ($repaymentSchedule = $repaymentDetail->getIdRepaymentSchedule()) {
                $grossRepayment = round(bcadd($repaymentDetail->getCapital(), $repaymentDetail->getInterest(), 4), 2);
                $tax            = $operationRepository->getTaxAmountByLoanAndRepaymentTaskLog($repaymentDetail->getIdLoan(), $repaymentDetail->getIdTaskLog());
                $netRepayment   = round(bcsub($grossRepayment, $tax, 4), 2);

                $wallet       = $repaymentDetail->getIdLoan()->getIdLender();
                $clientStatus = $wallet ? $wallet->getIdClient()->getIdClientStatusHistory()->getIdStatus()->getId() : null;

                if (null !== $wallet && in_array($clientStatus, ClientsStatus::GRANTED_LOGIN)) {
                    $notifications->type       = Notifications::TYPE_REPAYMENT;
                    $notifications->id_lender  = $wallet->getId();
                    $notifications->id_project = $repaymentDetail->getIdLoan()->getProject()->getIdProject();
                    $notifications->amount     = bcmul($netRepayment, 100);
                    $notifications->create();

                    if ($projectRepaymentTaskManager->isCompleteRepayment($repaymentDetail->getIdTask())) {
                        $repaymentOperation   = $operationRepository->findOneBy(['idRepaymentSchedule' => $repaymentDetail->getIdRepaymentSchedule()]);
                        $walletBalanceHistory = $walletBalanceHistoryRepository->findOneBy(['idOperation' => $repaymentOperation, 'idWallet' => $wallet]);

                        $clients_gestion_mails_notif->id_client                 = $wallet->getIdClient()->getIdClient();
                        $clients_gestion_mails_notif->id_notif                  = \clients_gestion_type_notif::TYPE_REPAYMENT;
                        $clients_gestion_mails_notif->date_notif                = $repaymentSchedule->getDateEcheanceReel()->format('Y-m-d H:i:s');
                        $clients_gestion_mails_notif->id_notification           = $notifications->id_notification;
                        $clients_gestion_mails_notif->id_wallet_balance_history = $walletBalanceHistory->getId();
                        $clients_gestion_mails_notif->create();

                        if ($repaymentDetail->getIdTask()->getType() === ProjectRepaymentTask::TYPE_LATE) {
                            $projectRepaymentNotificationSender->sendRegularisationRepaymentMailToLender($repaymentSchedule);
                        } elseif (true === $clients_gestion_notifications->getNotif($wallet->getIdClient()->getIdClient(), \clients_gestion_type_notif::TYPE_REPAYMENT, 'immediatement')) {
                            $clients_gestion_mails_notif->get($clients_gestion_mails_notif->id_clients_gestion_mails_notif, 'id_clients_gestion_mails_notif');
                            $clients_gestion_mails_notif->immediatement = 1;
                            $clients_gestion_mails_notif->update();

                            $projectRepaymentNotificationSender->sendRepaymentMailToLender($repaymentSchedule);
                        }
                    }
                }
            }
            $repaymentDetail->setStatus(ProjectRepaymentDetail::STATUS_NOTIFIED);
            $emailNB++;

            if (0 === $emailNB % 50) {
                $entityManager->flush();
            }
        }

        $entityManager->flush();
        $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectRepaymentDetail')->deleteFinished(new \DateTime('3 months ago'));
    }
}
