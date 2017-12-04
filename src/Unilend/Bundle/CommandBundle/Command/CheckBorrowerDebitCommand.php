<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;

class CheckBorrowerDebitCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('check:borrower_debit')
            ->setDescription('Checks if the borrower has paid at the date of payment');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManagerSimulator $entityManagerSimulator */
        $entityManagerSimulator = $this->getContainer()->get('unilend.service.entity_manager');
        /** @var \echeanciers $echeanciers */
        $echeanciers = $entityManagerSimulator->getRepository('echeanciers');
        /** @var \projects $projects */
        $projects = $entityManagerSimulator->getRepository('projects');
        /** @var \settings $settings */
        $settings = $entityManagerSimulator->getRepository('settings');

        $liste      = $echeanciers->selectEcheanciersByprojetEtOrdre();
        $liste_remb = '';
        foreach ($liste as $l) {
            $projects->get($l['id_project'], 'id_project');
            $liste_remb .= '
                <tr>
                    <td>' . $l['id_project'] . '</td>
                    <td>' . $projects->title . '</td>
                    <td>' . $l['ordre'] . '</td>
                    <td>' . $l['date_echeance'] . '</td>

                    <td>' . $l['date_echeance_emprunteur'] . '</td>
                    <td>' . $l['date_echeance_emprunteur_reel'] . '</td>
                    <td>' . ((int) $l['status_emprunteur'] === 1 ? 'Oui' : 'Non') . '</td>
                </tr>';
        }

        $varMail = array(
            '$surl'       => $this->getContainer()->getParameter('router.request_context.scheme') . '://' . $this->getContainer()->getParameter('url.host_default'),
            '$liste_remb' => $liste_remb
        );

        $settings->get('Adresse notification check remb preteurs', 'type');
        $recipient = $settings->value;

        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->getContainer()->get('unilend.swiftmailer.message_provider')->newMessage('notification-prelevement-emprunteur', $varMail, false);

        try {
            $message->setTo(explode(';', trim($recipient)));
            $mailer = $this->getContainer()->get('mailer');
            $mailer->send($message);
        } catch (\Exception $exception) {
            $this->getContainer()->get('monolog.logger.console')->warning(
                'Could not send email : notification-prelevement-emprunteur - Exception: ' . $exception->getMessage(),
                ['id_mail_template' => $message->getTemplateId(), 'email address' => explode(';', trim($recipient)), 'class' => __CLASS__, 'function' => __FUNCTION__]
            );
        }
    }
}
