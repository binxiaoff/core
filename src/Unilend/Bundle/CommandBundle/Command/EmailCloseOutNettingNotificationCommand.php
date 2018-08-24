<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\{Input\InputInterface, Output\OutputInterface};
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
        $entityManager                    = $this->getContainer()->get('doctrine.orm.entity_manager');
        $projectStatusNotificationSender  = $this->getContainer()->get('unilend.service.project_status_notification_sender');
        $closeOutNettingPaymentRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:CloseOutNettingPayment');

        /** @var CloseOutNettingPayment $closeOutNettingPayment */
        foreach ($closeOutNettingPaymentRepository->findBy(['borrowerNotified' => false]) as $closeOutNettingPayment) {
            try {
                $projectStatusNotificationSender->sendCloseOutNettingEmailToBorrower($closeOutNettingPayment->getIdProject());

                $closeOutNettingPayment->setBorrowerNotified(true);
                $entityManager->flush($closeOutNettingPayment);
            } catch (\Exception $exception) {
                $this->getContainer()->get('monolog.logger.console')->error('An exception occurred while sending borrower close out netting email for the project: ' . $closeOutNettingPayment->getIdProject()->getIdProject() . '. Exception message: ' . $exception->getMessage(), [
                    'id_project' => $closeOutNettingPayment->getIdProject()->getIdProject(),
                    'class'      => __CLASS__,
                    'function'   => __FUNCTION__,
                    'file'       => $exception->getFile(),
                    'line'       => $exception->getLine()
                ]);
            }
        }

        foreach ($closeOutNettingPaymentRepository->findBy(['lendersNotified' => false]) as $closeOutNettingPayment) {
            try {
                $projectStatusNotificationSender->sendCloseOutNettingNotificationsToLenders($closeOutNettingPayment->getIdProject());

                $closeOutNettingPayment->setLendersNotified(true);
                $entityManager->flush($closeOutNettingPayment);
            } catch (\Exception $exception) {
                $this->getContainer()->get('monolog.logger.console')->error('An exception occurred while sending lenders close out netting email for the project: ' . $closeOutNettingPayment->getIdProject()->getIdProject() . '. Exception message: ' . $exception->getMessage(), [
                    'id_project' => $closeOutNettingPayment->getIdProject()->getIdProject(),
                    'class'      => __CLASS__,
                    'function'   => __FUNCTION__,
                    'file'       => $exception->getFile(),
                    'line'       => $exception->getLine()
                ]);
            }
        }
    }
}
