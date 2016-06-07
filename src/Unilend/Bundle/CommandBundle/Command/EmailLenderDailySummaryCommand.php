<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bridge\Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Service\MailerManager;
use Unilend\Service\Simulator\EntityManager;


class EmailLenderDailySummaryCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('email:lender:daily_summary')
            ->setDescription('Send the daily summaries to lenders');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        ini_set('memory_limit', '1G');

        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('unilend.service.entity_manager');
        /** @var \clients_gestion_notifications $oCustomerNotificationSettings */
        $oCustomerNotificationSettings = $entityManager->getRepository('clients_gestion_notifications');

        // Loaded for class constants
        $entityManager->getRepository('clients_gestion_type_notif');

        /** @var MailerManager $mailerManager */
        $mailerManager = $this->getContainer()->get('unilend.service.email_manager');
        /** @var Logger $logger */
        $logger = $this->getContainer()->get('monolog.logger.console');
        $mailerManager->setLogger($logger);

        $iCurrentTime = time();

        if (
            $iCurrentTime >= mktime(19, 30, 0, date('m'), date('d'), date('Y'))
            && $iCurrentTime < mktime(20, 0, 0, date('m'), date('d'), date('Y'))
        ) {
            $mailerManager->sendNewProjectsSummaryEmail($oCustomerNotificationSettings->getCustomersByNotification('quotidienne', \clients_gestion_type_notif::TYPE_NEW_PROJECT), 'quotidienne');
        } elseif (
            $iCurrentTime >= mktime(20, 0, 0, date('m'), date('d'), date('Y'))
            && $iCurrentTime < mktime(20, 15, 0, date('m'), date('d'), date('Y'))
        ) {
            $mailerManager->sendPlacedBidsSummaryEmail($oCustomerNotificationSettings->getCustomersByNotification('quotidienne', \clients_gestion_type_notif::TYPE_BID_PLACED), 'quotidienne');
        } elseif (
            $iCurrentTime >= mktime(20, 15, 0, date('m'), date('d'), date('Y'))
            && $iCurrentTime < mktime(20, 30, 0, date('m'), date('d'), date('Y'))
        ) {
            $mailerManager->sendRejectedBidsSummaryEmail($oCustomerNotificationSettings->getCustomersByNotification('quotidienne', \clients_gestion_type_notif::TYPE_BID_REJECTED), 'quotidienne');
        } elseif (
            $iCurrentTime >= mktime(20, 30, 0, date('m'), date('d'), date('Y'))
            && $iCurrentTime < mktime(21, 0, 0, date('m'), date('d'), date('Y'))
        ) {
            $mailerManager->sendAcceptedLoansSummaryEmail($oCustomerNotificationSettings->getCustomersByNotification('quotidienne', \clients_gestion_type_notif::TYPE_LOAN_ACCEPTED), 'quotidienne');
        } elseif (
            $iCurrentTime >= mktime(18, 0, 0, date('m'), date('d'), date('Y'))
            && $iCurrentTime <  mktime(19, 30, 0, date('m'), date('d'), date('Y'))
        ) {
            $mailerManager->sendRepaymentsSummaryEmail($oCustomerNotificationSettings->getCustomersByNotification('quotidienne', \clients_gestion_type_notif::TYPE_REPAYMENT), 'quotidienne');
        }
    }
}
