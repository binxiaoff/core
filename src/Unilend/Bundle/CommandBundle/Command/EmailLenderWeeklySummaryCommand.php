<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bridge\Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Service\MailerManager;
use Unilend\Service\Simulator\EntityManager;


class EmailLenderWeeklySummaryCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('email:lender:weekly_summary')
            ->setDescription('Send the weekly summaries to lenders');
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
            $iCurrentTime >= mktime(9, 0, 0, date('m'), date('d'), date('Y'))
            && $iCurrentTime < mktime(9, 30, 0, date('m'), date('d'), date('Y'))
        ) {
            $mailerManager->sendNewProjectsSummaryEmail($oCustomerNotificationSettings->getCustomersByNotification('hebdomadaire', \clients_gestion_type_notif::TYPE_NEW_PROJECT), 'hebdomadaire');
        } elseif (
            $iCurrentTime >= mktime(9, 30, 0, date('m'), date('d'), date('Y'))
            && $iCurrentTime < mktime(10, 0, 0, date('m'), date('d'), date('Y'))
        ) {
            $mailerManager->sendAcceptedLoansSummaryEmail($oCustomerNotificationSettings->getCustomersByNotification('hebdomadaire', \clients_gestion_type_notif::TYPE_LOAN_ACCEPTED), 'hebdomadaire');
        } elseif (
            $iCurrentTime >= mktime(10, 0, 0, date('m'), date('d'), date('Y'))
            && $iCurrentTime < mktime(10, 30, 0, date('m'), date('d'), date('Y'))
        ) {
            $mailerManager->sendRepaymentsSummaryEmail($oCustomerNotificationSettings->getCustomersByNotification('hebdomadaire', \clients_gestion_type_notif::TYPE_REPAYMENT), 'hebdomadaire');
        }
    }
}
