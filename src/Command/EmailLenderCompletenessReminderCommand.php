<?php

namespace Unilend\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Entity\{ClientsStatus, ClientsStatusHistory, Users};

class EmailLenderCompletenessReminderCommand extends ContainerAwareCommand
{
    const REMINDER_DELAY_DAYS_FIRST  = 8;
    const REMINDER_DELAY_DAYS_SECOND = 30;

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('email:lender:completeness_reminder')
            ->setDescription('Sends an email to potential lenders reminding them of missing documents');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        /** @var \clients $clients */
        $clients                       = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('clients');
        $clientStatusManager           = $this->getContainer()->get('unilend.service.client_status_manager');
        $clientStatusHistoryRepository = $this->getContainer()->get('doctrine.orm.entity_manager')->getRepository(ClientsStatusHistory::class);

        $firstReminderDate  = (new \DateTime(self::REMINDER_DELAY_DAYS_FIRST . ' days ago'))->setTime(0, 0, 0);
        $secondReminderDate = (new \DateTime(self::REMINDER_DELAY_DAYS_SECOND . ' days ago'))->setTime(0, 0, 0);

        $lenders = $clients->selectPreteursByStatus(ClientsStatus::STATUS_COMPLETENESS, 'added_status DESC');
        foreach ($lenders as $lender) {
            $statusDate = \DateTime::createFromFormat('Y-m-d H:i:s', $lender['added_status']);

            if ($statusDate <= $firstReminderDate) {
                $clientStatusHistory = $clientStatusHistoryRepository->find($lender['id_client_status_history']);
                $clients->get($lender['id_client']);
                $this->sendReminderEmail($clients, $lender, $clientStatusHistory->getContent());
                $clientStatusManager->addClientStatus($clients, Users::USER_ID_CRON, ClientsStatus::STATUS_COMPLETENESS_REMINDER, $clientStatusHistory->getContent());
            }
        }

        $lenders = $clients->selectPreteursByStatus(ClientsStatus::STATUS_COMPLETENESS_REMINDER, 'added_status DESC');
        foreach ($lenders as $lender) {
            $sendReminder        = false;
            $reminder            = null;
            $clientStatusHistory = $clientStatusHistoryRepository->find($lender['id_client_status_history']);
            $reminderNumber      = $clientStatusHistory->getNumeroRelance();
            $statusDate          = \DateTime::createFromFormat('Y-m-d H:i:s', $lender['added_status']);

            if ($statusDate <= $firstReminderDate && $reminderNumber == 0) {
                $sendReminder = true;
                $reminder     = 2;
            } elseif ($statusDate <= $firstReminderDate && $reminderNumber == 2) {
                $sendReminder = true;
                $reminder     = 3;
            } elseif ($statusDate <= $secondReminderDate && $reminderNumber == 3) {
                $sendReminder = true;
                $reminder     = 4;
            }

            if (true === $sendReminder) {
                $clients->get($lender['id_client']);
                $this->sendReminderEmail($clients, $lender, $clientStatusHistory->getContent());
                $clientStatusManager->addClientStatus($clients, Users::USER_ID_CRON, ClientsStatus::STATUS_COMPLETENESS_REMINDER, $clientStatusHistory->getContent(), $reminder);
            }
        }
    }

    /**
     * @param \clients $client
     * @param array    $lender
     * @param string   $content
     */
    private function sendReminderEmail(\clients $client, array $lender, string $content): void
    {
        $timeCreate    = \DateTime::createFromFormat('Y-m-d H:i:s', $lender['added_status']);
        $dateFormatter = new \IntlDateFormatter($this->getContainer()->getParameter('locale'), \IntlDateFormatter::LONG, \IntlDateFormatter::NONE);
        $keywords      = [
            'firstName'        => $client->prenom,
            'modificationDate' => $dateFormatter->format($timeCreate),
            'content'          => $content,
            'uploadLink'       => $this->getContainer()->getParameter('router.request_context.scheme') . '://' . getenv('HOST_DEFAULT_URL') . '/profile/documents',
            'lenderPattern'    => $client->getLenderPattern($client->id_client)
        ];

        try {
            $message = $this->getContainer()->get('unilend.swiftmailer.message_provider')->newMessage('completude', $keywords);
            $message->setTo($client->email);

            $mailer = $this->getContainer()->get('mailer');
            $mailer->send($message);
        } catch (\Exception $exception) {
            $this->getContainer()->get('monolog.logger.console')->warning(
                'Could not send email: "completude" - Exception: ' . $exception->getMessage(),
                ['mail_type' => 'completude', 'id_client' => $lender['id_client'], 'file' => $exception->getFile(), 'line' => $exception->getLine()]
            );
        }
    }
}
