<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\MailerManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;


class EmailLenderMonthlySummaryCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('email:lender:monthly_summary')
            ->setDescription('Send the monthly summaries to lenders');
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

        $iCurrentTime = time();

        if (
            $iCurrentTime >= mktime(10, 30, 0, date('m'), date('d'), date('Y'))
            && $iCurrentTime < mktime(11, 0, 0, date('m'), date('d'), date('Y'))
        ) {
            $mailerManager->sendAcceptedLoansSummaryEmail($oCustomerNotificationSettings->getCustomersByNotification('mensuelle', \clients_gestion_type_notif::TYPE_LOAN_ACCEPTED), 'mensuelle');
        } elseif (
            $iCurrentTime >= mktime(11, 0, 0, date('m'), date('d'), date('Y'))
            && $iCurrentTime < mktime(11, 30, 0, date('m'), date('d'), date('Y'))
        ) {
            $mailerManager->sendRepaymentsSummaryEmail($oCustomerNotificationSettings->getCustomersByNotification('mensuelle', \clients_gestion_type_notif::TYPE_REPAYMENT), 'mensuelle');
        }
    }

}