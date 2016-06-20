<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
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
        /** @var \settings $settings */
        $settings = $this->oEntityManager->getRepository('settings');

        $settings->get('Facebook', 'type');
        $sFacebookLink = $settings->value;
        $settings->get('Twitter', 'type');
        $sTwitterLink = $settings->value;

        $iTimeMinus8D = mktime(0, 0, 0, date("m"), date("d") - 8, date("Y"));
        $aLenders     = $clients->selectPreteursByStatus('20', '', 'added_status DESC');

        foreach ($aLenders as $aLender) {
            $timestamp_date = $this->oDate->formatDateMySqlToTimeStamp($aLender['added_status']);

            if ($timestamp_date <= $iTimeMinus8D) {
                $clients_status_history->get($aLender['id_client_status_history'], 'id_client_status_history');
                $this->sendReminderEmail($aLender, $sTwitterLink, $sFacebookLink, $clients_status_history->content);
                $clients_status_history->addStatus(\users::USER_ID_CRON, \clients_status::COMPLETENESS_REMINDER, $aLender['id_client'], $clients_status_history->content);
            }
        }

        $aLenders = $clients->selectPreteursByStatus('30', '', 'added_status DESC');

        $iTimeMinus8D  = mktime(0, 0, 0, date("m"), date("d") - 8, date("Y"));
        $iTimeMinus30D = mktime(0, 0, 0, date("m"), date("d") - 30, date("Y"));

        foreach ($aLenders as $aLender) {
            $bSendReminder     = false;
            $aClientLastStatus = $clients_status_history->get_last_statut($aLender['id_client']);
            $iRevivalNumber    = $aClientLastStatus['numero_relance'];
            $timestamp_date    = $this->oDate->formatDateMySqlToTimeStamp($aLender['added_status']);

            if ($timestamp_date <= $iTimeMinus8D && $iRevivalNumber == 0) {// Reminder D+15
                $bSendReminder = true;
                $clients_status_history->addStatus(\users::USER_ID_CRON, \clients_status::COMPLETENESS_REMINDER, $aLender['id_client'], $aClientLastStatus['content'], 2);
            } elseif ($timestamp_date <= $iTimeMinus8D && $iRevivalNumber == 2) {// Reminder D+30
                $bSendReminder = true;
                $clients_status_history->addStatus(\users::USER_ID_CRON, \clients_status::COMPLETENESS_REMINDER, $aLender['id_client'], $aClientLastStatus['content'], 3);
            } elseif ($timestamp_date <= $iTimeMinus30D && $iRevivalNumber == 3) {// Reminder D+60
                $bSendReminder = true;
                $clients_status_history->addStatus(\users::USER_ID_CRON, \clients_status::COMPLETENESS_REMINDER, $aLender['id_client'], $aClientLastStatus['content'], 4);
            }

            if (true === $bSendReminder) {
                $this->sendReminderEmail($aLender, $sTwitterLink, $sFacebookLink, $aClientLastStatus['content']);
            }
        }
    }

    /**
     * @param array $aLender
     * @param string $sTwitterLink
     * @param string $sFacebookLink
     * @param string $sContent
     */
    private function sendReminderEmail(array $aLender, $sTwitterLink, $sFacebookLink, $sContent)
    {
        $sUrl       = $this->getContainer()->getParameter('router.request_context.scheme') . '://' . $this->getContainer()->getParameter('url.host_default');
        $sStaticUrl = $this->getContainer()->get('assets.packages')->getUrl('');
        $timeCreate = strtotime($aLender['added_status']);
        $month      = $this->oDate->tableauMois['fr'][date('n', $timeCreate)];

        $varMail = array(
            'furl'          => $sUrl,
            'surl'          => $sStaticUrl,
            'url'           => $sUrl,
            'prenom_p'      => $aLender['prenom'],
            'date_creation' => date('d', $timeCreate) . ' ' . $month . ' ' . date('Y', $timeCreate),
            'content'       => $sContent,
            'lien_fb'       => $sFacebookLink,
            'lien_tw'       => $sTwitterLink
        );
        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->getContainer()->get('unilend.swiftmailer.message_provider')->newMessage('completude', $varMail);
        $message->setTo($aLender['email']);
        $mailer = $this->getContainer()->get('mailer');
        $mailer->send($message);
    }
}
