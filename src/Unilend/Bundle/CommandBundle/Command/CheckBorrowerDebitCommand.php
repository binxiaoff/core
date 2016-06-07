<?php

namespace Unilend\Bundle\CommandBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class CheckBorrowerDebitCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('check:borrower_debit')
            ->setDescription('Greet someone');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('unilend.service.entity_manager');
        /** @var \echeanciers $echeanciers */
        $echeanciers = $entityManager->getRepository('echeanciers');
        /** @var \projects $projects */
        $projects    = $entityManager->getRepository('projects');
        /** @var \settings $oSettings */
        $oSettings = $entityManager->getRepository('settings');

        $liste       = $echeanciers->selectEcheanciersByprojetEtOrdre();
        $liste_remb  = '';
        foreach ($liste as $l) {
            $projects->get($l['id_project'], 'id_project');
            $liste_remb .= '
                <tr>
                    <td>' . $l['id_project'] . '</td>
                    <td>' . $projects->title_bo . '</td>
                    <td>' . $l['ordre'] . '</td>
                    <td>' . $l['date_echeance'] . '</td>

                    <td>' . $l['date_echeance_emprunteur'] . '</td>
                    <td>' . $l['date_echeance_emprunteur_reel'] . '</td>
                    <td>' . ((int) $l['status_emprunteur'] === 1 ? 'Oui' : 'Non') . '</td>
                </tr>';
        }

        $varMail = array(
            '$surl'       => $this->getContainer()->getParameter('router.request_context.scheme') . '://' . $this->getContainer()->getParameter('router.request_context.host'),
            '$liste_remb' => $liste_remb
        );

        $oSettings->get('Adresse notification check remb preteurs', 'type');
        $destinataire = $oSettings->value;

        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->getContainer()->get('unilend.swiftmailer.message_provider')->newMessage('notification-prelevement-emprunteur', $varMail, false);
        $message->setTo(explode(';', trim($destinataire)));
        $mailer = $this->getContainer()->get('mailer');
        $mailer->send($message);
    }
}
