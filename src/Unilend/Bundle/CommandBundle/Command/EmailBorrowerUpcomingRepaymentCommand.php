<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\core\Loader;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class EmailBorrowerUpcomingRepaymentCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('email:borrower:upcoming_repayment')
            ->setDescription('Send emails to borrower when a repayment is coming');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $entityManger */
        $entityManger = $this->getContainer()->get('unilend.service.entity_manager');
        /** @var \prelevements $directDebit */
        $directDebit = $entityManger->getRepository('prelevements');
        /** @var \projects $project */
        $project = $entityManger->getRepository('projects');
        /** @var \companies $company */
        $company = $entityManger->getRepository('companies');
        /** @var \clients $client */
        $client = $entityManger->getRepository('clients');
        /** @var \settings $settings */
        $settings = $entityManger->getRepository('settings');
        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');

        $upcomingRepayments = $directDebit->getUpcomingRepayments(7);

        foreach ($upcomingRepayments as $repayment) {
            $project->get($repayment['id_project']);
            $company->get($project->id_company);

            if (false === empty($company->prenom_dirigeant) && false === empty($company->email_dirigeant)) {
                $firstName   = $company->prenom_dirigeant;
                $clientEmail = $company->email_dirigeant;
            } else {
                $client->get($company->id_client_owner);
                $firstName   = $client->prenom;
                $clientEmail = $client->email;
            }

            /** @var \loans $loans */
            $loans = $entityManger->getRepository('loans');

            $settings->get('Facebook', 'type');
            $facebookLink = $settings->value;
            $settings->get('Twitter', 'type');
            $twitterLink = $settings->value;
            $frontUrl    = $this->getContainer()->getParameter('router.request_context.scheme') . '://' . $this->getContainer()->getParameter('url.host_default');

            $varMail = array(
                'nb_emprunteurs'     => $loans->getNbPreteurs($repayment['id_project']),
                'echeance'           => $ficelle->formatNumber($repayment['montant'] / 100),
                'prochaine_echeance' => date('d/m/Y', strtotime($repayment['date_echeance_emprunteur'])),
                'surl'               => $frontUrl,
                'url'                => $frontUrl,
                'nom_entreprise'     => $company->name,
                'montant'            => $ficelle->formatNumber((float) $project->amount, 0),
                'prenom_e'           => $firstName,
                'lien_fb'            => $facebookLink,
                'lien_tw'            => $twitterLink
            );

            /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
            $message = $this->getContainer()->get('unilend.swiftmailer.message_provider')->newMessage('mail-echeance-emprunteur', $varMail);
            $message->setTo($clientEmail);
            $mailer = $this->getContainer()->get('mailer');
            $mailer->send($message);
        }
    }
}
