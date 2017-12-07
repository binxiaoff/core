<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;
use Unilend\Bundle\CoreBusinessBundle\Service\ClientStatusManager;
use Unilend\core\Loader;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class EmailLenderCompletenessReminderCommand extends ContainerAwareCommand
{
    /** @var  EntityManager */
    private $oEntityManager;
    /** @var  \dates */
    private $oDate;

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('email:lender:completeness_reminder')
            ->setDescription('Sends an email to potential lenders reminding them of missing documents');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->oEntityManager = $this->getContainer()->get('unilend.service.entity_manager');
        $this->oDate          = Loader::loadLib('dates');
        /** @var \clients $clients */
        $clients = $this->oEntityManager->getRepository('clients');
        $this->oEntityManager->getRepository('clients_status');
        /** @var \clients_status_history $clients_status_history */
        $clients_status_history = $this->oEntityManager->getRepository('clients_status_history');
        /** @var ClientStatusManager $clientStatusManager */
        $clientStatusManager = $this->getContainer()->get('unilend.service.client_status_manager');

        $iTimeMinus8D = mktime(0, 0, 0, date("m"), date("d") - 8, date("Y"));
        $aLenders     = $clients->selectPreteursByStatus(\clients_status::COMPLETENESS, '', 'added_status DESC');
        $logger = $this->getContainer()->get('monolog.logger.console');

        foreach ($aLenders as $aLender) {
            $timestamp_date = $this->oDate->formatDateMySqlToTimeStamp($aLender['added_status']);
            $clients->get($aLender['id_client']);

            if ($timestamp_date <= $iTimeMinus8D) {
                $clients_status_history->get($aLender['id_client_status_history'], 'id_client_status_history');
                try {
                    $this->sendReminderEmail($clients, $aLender, $clients_status_history->content);
                    $clientStatusManager->addClientStatus($clients, Users::USER_ID_CRON, \clients_status::COMPLETENESS_REMINDER, $clients_status_history->content);
                } catch (\Exception $exception) {
                    $logger->warning(
                        'Could not send email: completude - Exception: ' . $exception->getMessage(),
                        ['mail_type' => 'completude', 'id_client' => $aLender['id_client'], 'class' => __CLASS__, 'function' => __FUNCTION__]
                    );
                }
            }
        }

        $aLenders = $clients->selectPreteursByStatus(\clients_status::COMPLETENESS_REMINDER, '', 'added_status DESC');

        $iTimeMinus8D  = mktime(0, 0, 0, date("m"), date("d") - 8, date("Y"));
        $iTimeMinus30D = mktime(0, 0, 0, date("m"), date("d") - 30, date("Y"));

        foreach ($aLenders as $aLender) {
            $bSendReminder     = false;
            $reminder          = null;
            $aClientLastStatus = $clients_status_history->get_last_statut($aLender['id_client']);
            $iRevivalNumber    = $aClientLastStatus['numero_relance'];
            $timestamp_date    = $this->oDate->formatDateMySqlToTimeStamp($aLender['added_status']);
            $clients->get($aLender['id_client']);

            if ($timestamp_date <= $iTimeMinus8D && $iRevivalNumber == 0) {// Reminder D+15
                $bSendReminder = true;
                $reminder      = 2;
            } elseif ($timestamp_date <= $iTimeMinus8D && $iRevivalNumber == 2) {// Reminder D+30
                $bSendReminder = true;
                $reminder      = 3;
            } elseif ($timestamp_date <= $iTimeMinus30D && $iRevivalNumber == 3) {// Reminder D+60
                $bSendReminder = true;
                $reminder      = 4;
            }

            if (true === $bSendReminder) {
                try {
                    $this->sendReminderEmail($clients, $aLender, $aClientLastStatus['content']);
                    $clientStatusManager->addClientStatus($clients, Users::USER_ID_CRON, \clients_status::COMPLETENESS_REMINDER, $aClientLastStatus['content'], $reminder);
                } catch (\Exception $exception) {
                    $logger->warning(
                        'Could not send email: completude - Reminder #' . $reminder . ' - Exception: ' . $exception->getMessage(),
                        ['mail_type' => 'completude', 'id_client' => $aLender['id_client'], 'class' => __CLASS__, 'function' => __FUNCTION__]
                    );
                }
            }
        }
    }

    /**
     * @param \clients $client
     * @param array    $lender
     * @param string   $content
     */
    private function sendReminderEmail(\clients $client, array $lender, $content)
    {
        $timeCreate = strtotime($lender['added_status']);
        $month      = $this->oDate->tableauMois['fr'][date('n', $timeCreate)];
        $keywords   = [
            'firstName'        => $client->prenom,
            'modificationDate' => date('d', $timeCreate) . ' ' . $month . ' ' . date('Y', $timeCreate),
            'content'          => $content,
            'uploadLink'       => $this->getContainer()->getParameter('router.request_context.scheme') . '://' . $this->getContainer()->getParameter('url.host_default') . '/profile/documents',
            'lenderPattern'    => $client->getLenderPattern($client->id_client)
        ];

        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->getContainer()->get('unilend.swiftmailer.message_provider')->newMessage('completude', $keywords);
        $message->setTo($client->email);
        $mailer = $this->getContainer()->get('mailer');
        $mailer->send($message);
    }
}
