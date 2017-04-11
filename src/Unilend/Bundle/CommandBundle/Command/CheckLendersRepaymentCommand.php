<?php
namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

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
        $project    = $entityManager->getRepository('projects');
        $date       = new \DateTime();
        $repayments = $repayment->getRepaymentOfTheDay($date);
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
        $url           = $this->getContainer()->getParameter('router.request_context.scheme') . '://' . $this->getContainer()->getParameter('url.host_default');
        $replacements = array(
            '[#SURL#]'       => $url,
            '[#REPAYMENTS#]' => $repaymentsTable
        );

        /** @var \settings $settings */
        $settings = $entityManager->getRepository('settings');
        $settings->get('Adresse notification check remb preteurs', 'type');
        $recipient = $settings->value;

        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->getContainer()->get('unilend.swiftmailer.message_provider')->newMessage('notification-check-remboursements-preteurs', $replacements, false);
        $message->setTo(explode(';', str_replace(' ', '', $recipient)));
        $mailer = $this->getContainer()->get('mailer');
        $mailer->send($message);
    }
}
