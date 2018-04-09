<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\{
    Input\InputInterface, Input\InputOption, Output\OutputInterface
};
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    Clients, ClientsGestionTypeNotif, ClientsStatus, Notifications, OperationSubType, OperationType
};

class ProjectsEarlyRefundEmailCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('projects:early_refund_email')
            ->setDescription('Check projects that are in FUNDING status')
            ->addOption('limit-project', 'l', InputOption::VALUE_REQUIRED, 'Number of projects to process');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entityManagerSimulator = $this->getContainer()->get('unilend.service.entity_manager');
        $entityManager          = $this->getContainer()->get('doctrine.orm.entity_manager');
        $numberFormatter        = $this->getContainer()->get('number_formatter');
        $currencyFormatter      = $this->getContainer()->get('currency_formatter');

        /** @var \projects $project */
        $project = $entityManagerSimulator->getRepository('projects');
        /** @var \echeanciers $lenderRepaymentSchedule */
        $lenderRepaymentSchedule = $entityManagerSimulator->getRepository('echeanciers');
        /** @var \receptions $sfpmeiFeedIncoming */
        $sfpmeiFeedIncoming = $entityManagerSimulator->getRepository('receptions');
        /** @var \clients_gestion_mails_notif $emailNotification */
        $emailNotification = $entityManagerSimulator->getRepository('clients_gestion_mails_notif');
        /** @var \clients_gestion_notifications $notificationSettings */
        $notificationSettings = $entityManagerSimulator->getRepository('clients_gestion_notifications');
        /** @var \notifications $notification */
        $notification = $entityManagerSimulator->getRepository('notifications');
        /** @var \companies $company */
        $company = $entityManagerSimulator->getRepository('companies');
        /** @var \loans $loan */
        $loan = $entityManagerSimulator->getRepository('loans');
        /** @var \remboursement_anticipe_mail_a_envoyer $earlyRepaymentEmail */
        $earlyRepaymentEmail = $entityManagerSimulator->getRepository('remboursement_anticipe_mail_a_envoyer');
        /** @var \clients $client */
        $client = $entityManagerSimulator->getRepository('clients');
        $mailer = $this->getContainer()->get('mailer');
        $logger = $this->getContainer()->get('monolog.logger.console');

        $operationRepository         = $entityManager->getRepository('UnilendCoreBusinessBundle:Operation');
        $loanOperationType           = $entityManager->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::LENDER_LOAN]);
        $earlyRepaymentOperationType = $entityManager->getRepository('UnilendCoreBusinessBundle:OperationSubType')->findOneBy(['label' => OperationSubType::CAPITAL_REPAYMENT_EARLY]);

        $limit = $input->getOption('limit-project');
        $limit = $limit ? $limit : 1;

        foreach ($earlyRepaymentEmail->select('statut = 0', 'added ASC', '', $limit) as $earlyRefundPendingEmail) {
            $sfpmeiFeedIncoming->get($earlyRefundPendingEmail['id_reception']);
            $project->get($sfpmeiFeedIncoming->id_project);
            $company->get($project->id_company, 'id_company');
            $client->get($company->id_client_owner);
            $financeOperation = $operationRepository->findOneBy(['idProject' => $project->id_project, 'idType' => $loanOperationType]);

            $projectLenders           = $lenderRepaymentSchedule->get_liste_preteur_on_project($project->id_project);

            $keywords = [
                'firstName'            => $client->prenom,
                'fundingDate'          => $financeOperation->getAdded()->format('d/m/Y'),
                'companyName'          => $company->name,
                'projectAmount'        => $numberFormatter->format($project->amount),
                'projectDuration'      => $project->period,
                'fundingDuration'      => (new \DateTime($project->date_publication))->diff(new \DateTime($project->date_retrait))->d,
                'lendersCount'         => count($projectLenders),
                'earlyPaymentDate'     => \DateTime::createFromFormat('Y-m-d H:i:s', $sfpmeiFeedIncoming->added)->format('d/m/Y'),
                'borrowerServiceEmail' => $entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Adresse emprunteur'])->getValue()
            ];

            $message = $this->getContainer()->get('unilend.swiftmailer.message_provider')->newMessage('emprunteur-remboursement-anticipe', $keywords);

            try {
                $message->setTo($client->email);
                $mailer->send($message);
            } catch (\Exception $exception) {
                $logger->warning(
                    'Could not send email : emprunteur-remboursement-anticipe - Exception: ' . $exception->getMessage(),
                    ['id_mail_template' => $message->getTemplateId(), 'id_client' => $client->id_client, 'class' => __CLASS__, 'function' => __FUNCTION__]
                );
            }

            foreach ($projectLenders as $projectLender) {
                $wallet = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->find($projectLender['id_lender']);
                /** @var Clients $client */
                $client       = $wallet->getIdClient();
                $clientStatus = $client->getIdClientStatusHistory()->getIdStatus()->getId();

                if (in_array($clientStatus, ClientsStatus::GRANTED_LOGIN)) {
                    $lenderRemainingCapital = $operationRepository->getTotalEarlyRepaymentByLoan($projectLender['id_loan']);

                    $notification->type       = Notifications::TYPE_REPAYMENT;
                    $notification->id_lender  = $projectLender['id_lender'];
                    $notification->id_project = $project->id_project;
                    $notification->amount     = bcmul($lenderRemainingCapital, 100);
                    $notification->create();

                    $earlyRepaymentOperation = $operationRepository->findOneBy(['idLoan' => $projectLender['id_loan'], 'idSubType' => $earlyRepaymentOperationType]);
                    $walletBalanceHistory    = $entityManager->getRepository('UnilendCoreBusinessBundle:WalletBalanceHistory')->findOneBy([
                        'idWallet'    => $wallet,
                        'idOperation' => $earlyRepaymentOperation
                    ]);

                    $emailNotification->unsetData();
                    $emailNotification->id_client                 = $client->getIdClient();
                    $emailNotification->id_notif                  = ClientsGestionTypeNotif::TYPE_REPAYMENT;
                    $emailNotification->date_notif                = $earlyRepaymentOperation->getAdded()->format('Y-m-d H:i:s');
                    $emailNotification->id_notification           = $notification->id_notification;
                    $emailNotification->id_wallet_balance_history = $walletBalanceHistory->getId();

                    if ($notificationSettings->getNotif($client->getIdClient(), ClientsGestionTypeNotif::TYPE_REPAYMENT, 'immediatement')) {
                        $emailNotification->immediatement = 1;

                        $loan->get($projectLender['id_loan'], 'id_loan');

                        $keywords = [
                            'firstName'        => $client->getPrenom(),
                            'repaymentAmount'  => $currencyFormatter->formatCurrency($lenderRemainingCapital, 'EUR'),
                            'companyName'      => $company->name,
                            'paidInterests'    => $numberFormatter->format((float) $lenderRepaymentSchedule->getRepaidInterests([
                                'id_project' => $project->id_project,
                                'id_loan'    => $projectLender['id_loan'],
                                'id_lender'  => $projectLender['id_lender']
                            ])),
                            'loanRate'         => $numberFormatter->format($loan->rate),
                            'availableBalance' => $currencyFormatter->formatCurrency($wallet->getAvailableBalance(), 'EUR'),
                            'lenderPattern'    => $wallet->getWireTransferPattern()
                        ];

                        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                        $message = $this->getContainer()->get('unilend.swiftmailer.message_provider')->newMessage('preteur-remboursement-anticipe', $keywords);

                        try {
                            $message->setTo($client->getEmail());
                            $mailer->send($message);
                        } catch (\Exception $exception) {
                            $logger->warning(
                                'Could not send email : preteur-remboursement-anticipe - Exception: ' . $exception->getMessage(),
                                ['id_mail_template' => $message->getTemplateId(), 'id_client' => $client->getIdClient(), 'class' => __CLASS__, 'function' => __FUNCTION__]
                            );
                        }
                    }

                    $emailNotification->create();
                }
            }

            $earlyRepaymentEmail->get($earlyRefundPendingEmail['id_remboursement_anticipe_mail_a_envoyer']);
            $earlyRepaymentEmail->statut     = 1;
            $earlyRepaymentEmail->date_envoi = date('Y-m-d H:i:s');
            $earlyRepaymentEmail->update();
        }
    }
}
