<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\MailerManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class EmailLenderSummaryCommand extends ContainerAwareCommand
{
    const DAILY   = 'quotidienne';
    const WEEKLY  = 'hebdomadaire';
    const MONTHLY = 'mensuelle';

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('email:lender:summary')
            ->setDescription('Send the summaries to lenders')
            ->addArgument(
                'summary_type',
                InputArgument::REQUIRED,
                'Which type of summary do you like to send (daily, weekly or monthly) ?'
            )
            ->addArgument(
                'notification_type',
                InputArgument::REQUIRED,
                'Which type of notification do you like to send (new_project|placed_bid|rejected_bid|accepted_loan|repayment)?'
            )
            ->setHelp(<<<EOF
The <info>email:lender:summary</info> command sends the summaries to lenders.
Usage: <info>php bin/console email:lender:summary <daily|weekly|monthly> <new_project|placed_bid|rejected_bid|accepted_loan|repayment></info>
EOF
            );
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
        /** @var LoggerInterface $logger */
        $logger = $this->getContainer()->get('monolog.logger.console');
        $mailerManager->setLogger($logger);

        $summaryType = $input->getArgument('summary_type');

        switch ($summaryType) {
            case 'daily':
                $summaryType = self::DAILY;
                break;
            case 'weekly':
                $summaryType = self::WEEKLY;
                break;
            case 'monthly':
                $summaryType = self::MONTHLY;
                break;
            default:
                $output->writeln('Unknown summary type (' . $summaryType . ')');
                $logger->warning('Unknown summary type (' . $summaryType . ')', array('class' => __CLASS__, 'function' => __FUNCTION__));
                return;
        }

        $notificationType = $input->getArgument('notification_type');

        switch ($notificationType) {
            case 'repayment': // run at 18:00 for daily, at 10:00 for weekly and at 11::00 for monthly
                $mailerManager->sendRepaymentsSummaryEmail($oCustomerNotificationSettings->getCustomersByNotification($summaryType, \clients_gestion_type_notif::TYPE_REPAYMENT), $summaryType);
                break;
            case 'new_project': // run at 19:30 for daily, at 9:00 for weekly
                $mailerManager->sendNewProjectsSummaryEmail($oCustomerNotificationSettings->getCustomersByNotification($summaryType, \clients_gestion_type_notif::TYPE_NEW_PROJECT), $summaryType);
                break;
            case 'placed_bid': // run at 20:00 for daily
                $mailerManager->sendPlacedBidsSummaryEmail($oCustomerNotificationSettings->getCustomersByNotification($summaryType, \clients_gestion_type_notif::TYPE_BID_PLACED), $summaryType);
                break;
            case 'rejected_bid': // run at 20:15 for daily
                $mailerManager->sendRejectedBidsSummaryEmail($oCustomerNotificationSettings->getCustomersByNotification($summaryType, \clients_gestion_type_notif::TYPE_BID_REJECTED), $summaryType);
                break;
            case 'accepted_loan': // run at 20:30 for daily, at 9:30 for weekly and at 10::30 for monthly
                $mailerManager->sendAcceptedLoansSummaryEmail($oCustomerNotificationSettings->getCustomersByNotification($summaryType, \clients_gestion_type_notif::TYPE_LOAN_ACCEPTED), $summaryType);
                break;
            default:
                $output->writeln('Unknown notification type (' . $notificationType . ')');
                $logger->warning('Unknown notification type (' . $notificationType . ')', array('class' => __CLASS__, 'function' => __FUNCTION__));
                return;
        }
    }
}
