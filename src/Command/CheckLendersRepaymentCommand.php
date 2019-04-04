<?php

namespace Unilend\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Service\Simulator\EntityManager;

class CheckLendersRepaymentCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('check:lenders_repayment')
            ->setDescription('Checks if all repayments of which today is the due date are paid. And Sends a reporting mail to the internal control team.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('unilend.service.entity_manager');

        /** @var \echeanciers $repayment */
        $repayment = $entityManager->getRepository('echeanciers');
        /** @var \projects $project */
        $project         = $entityManager->getRepository('projects');
        $repayments      = $repayment->getRepaymentOfTheDay(new \DateTime());
        $repaymentsTable = '';

        foreach ($repayments as $repayment) {
            $project->get($repayment['id_project'], 'id_project');
            $repaymentsTable .= '
                <tr>
                    <td>' . $repayment['id_project'] . '</td>
                    <td>' . $project->title . '</td>
                    <td>' . $repayment['ordre'] . '</td>
                    <td>' . $repayment['nb_repayment'] . '</td>
                    <td>' . $repayment['nb_repayment_paid'] . '</td>
                    <td>' . ($repayment['nb_repayment'] === $repayment['nb_repayment_paid'] ? 'Oui' : 'Non') . '</td>
                </tr>';
        }

        $replacements = [
            '[#SURL#]'       => $this->getContainer()->getParameter('router.request_context.scheme') . '://' . $this->getContainer()->getParameter('url.host_default'),
            '[#REPAYMENTS#]' => $repaymentsTable
        ];

        /** @var \settings $settings */
        $settings = $entityManager->getRepository('settings');
        $settings->get('Adresse notification check remb preteurs', 'type');
        $recipient = $settings->value;

        /** @var \Unilend\SwiftMailer\TemplateMessage $message */
        $message = $this->getContainer()->get('unilend.swiftmailer.message_provider')->newMessage('notification-check-remboursements-preteurs', $replacements, false);

         try {
             $message->setTo(explode(';', str_replace(' ', '', $recipient)));
             $mailer = $this->getContainer()->get('mailer');
             $mailer->send($message);
         } catch (\Exception $exception) {
             $this->getContainer()->get('monolog.logger.console')->warning(
                 'Could not send email : notification-check-remboursements-preteurs - Exception: ' . $exception->getMessage(),
                 ['id_mail_template' => $message->getTemplateId(), 'email address' => explode(';', str_replace(' ', '', $recipient)), 'class' => __CLASS__, 'function' => __FUNCTION__]
             );
         }
    }
}
