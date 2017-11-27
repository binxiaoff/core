<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\CloseOutNettingPayment;

class EmailCloseOutNettingNotificationCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('unilend:project:close_out_netting:notify')
            ->setDescription('Send emails to borrower and lenders when the project is passed to close out netting');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entityManager        = $this->getContainer()->get('doctrine.orm.entity_manager');
        $projectStatusManager = $this->getContainer()->get('unilend.service.project_status_manager');
        /** @var CloseOutNettingPayment[] $closeOutNettingPayments */
        $closeOutNettingPayments = $entityManager->getRepository('UnilendCoreBusinessBundle:CloseOutNettingPayment')
            ->findBy(['notified' => false]);

        foreach ($closeOutNettingPayments as $closeOutNettingPayment) {
            $projectStatusManager->sendCloseOutNettingEmailToBorrower($closeOutNettingPayment->getIdProject());
            $projectStatusManager->sendCloseOutNettingNotificationsToLenders($closeOutNettingPayment->getIdProject());

            $closeOutNettingPayment->setNotified(true);
            $entityManager->flush($closeOutNettingPayment);
        }
    }
}
