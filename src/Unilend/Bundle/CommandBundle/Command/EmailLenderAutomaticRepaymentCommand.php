<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\Echeanciers;

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
        $entityManagerSimulator  = $this->getContainer()->get('unilend.service.entity_manager');
        $entityManager           = $this->getContainer()->get('doctrine.orm.entity_manager');
        $projectRepaymentManager = $this->getContainer()->get('unilend.service.project_repayment_manager');

        $operationRepository           = $entityManager->getRepository('UnilendCoreBusinessBundle:Operation');
        $walletBalanceHistoryRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:WalletBalanceHistory');
        /** @var \notifications $notifications */
        $notifications = $entityManagerSimulator->getRepository('notifications');
        /** @var \clients_gestion_notifications $clients_gestion_notifications */
        $clients_gestion_notifications = $entityManagerSimulator->getRepository('clients_gestion_notifications');
        /** @var \clients_gestion_mails_notif $clients_gestion_mails_notif */
        $clients_gestion_mails_notif = $entityManagerSimulator->getRepository('clients_gestion_mails_notif');
        /** @var Echeanciers[] $repaymentSchedules */
        $repaymentSchedules = $entityManager->getRepository('UnilendCoreBusinessBundle:Echeanciers')->findBy([
            'status'          => Echeanciers::STATUS_REPAID,
            'statusEmailRemb' => Echeanciers::STATUS_REPAYMENT_EMAIL_NOT_SENT
        ]);

        $emailNB = 0;

        foreach ($repaymentSchedules as $repaymentSchedule) {
            if ($repaymentSchedule) {
                $grossRepayment = $operationRepository->getGrossAmountByRepaymentScheduleId($repaymentSchedule);
                $tax            = $operationRepository->getTaxAmountByRepaymentScheduleId($repaymentSchedule);
                $netRepayment   = bcsub($grossRepayment, $tax, 2);

                $wallet = $repaymentSchedule->getIdLoan()->getIdLender();
                if (null !== $wallet && Clients::STATUS_ONLINE == $wallet->getIdClient()->getStatus()) {
                    $notifications->type       = \notifications::TYPE_REPAYMENT;
                    $notifications->id_lender  = $wallet->getId();
                    $notifications->id_project = $repaymentSchedule->getIdLoan()->getProject()->getIdProject();
                    $notifications->amount     = bcmul($netRepayment, 100);
                    $notifications->create();

                    $repaymentOperation  = $operationRepository->findOneBy(['idRepaymentSchedule' => $repaymentSchedule]);
                    $walletBalanceHistory = $walletBalanceHistoryRepository->findOneBy(['idOperation' => $repaymentOperation, 'idWallet' => $wallet]);

                    $clients_gestion_mails_notif->id_client                 = $wallet->getIdClient()->getIdClient();
                    $clients_gestion_mails_notif->id_notif                  = \clients_gestion_type_notif::TYPE_REPAYMENT;
                    $clients_gestion_mails_notif->date_notif                = $repaymentSchedule->getDateEcheanceReel()->format('Y-m-d H:i:s');
                    $clients_gestion_mails_notif->id_notification           = $notifications->id_notification;
                    $clients_gestion_mails_notif->id_wallet_balance_history = $walletBalanceHistory->getId();
                    $clients_gestion_mails_notif->create();

                    if (true === $clients_gestion_notifications->getNotif($wallet->getIdClient()->getIdClient(), \clients_gestion_type_notif::TYPE_REPAYMENT, 'immediatement')) {
                        $clients_gestion_mails_notif->get($clients_gestion_mails_notif->id_clients_gestion_mails_notif, 'id_clients_gestion_mails_notif');
                        $clients_gestion_mails_notif->immediatement = 1;
                        $clients_gestion_mails_notif->update();

                        $projectRepaymentManager->sendRepaymentMailToLender($repaymentSchedule);
                    }
                }
                $repaymentSchedule->setStatusEmailRemb(Echeanciers::STATUS_REPAYMENT_EMAIL_SENT);
                $emailNB++;

                if (0 === $emailNB % 50) {
                    $entityManager->flush();
                }
            }
        }
        $entityManager->flush();
    }
}
