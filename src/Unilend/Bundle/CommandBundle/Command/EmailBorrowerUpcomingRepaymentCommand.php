<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\core\Loader;
use Unilend\Service\Simulator\EntityManager;


class EmailBorrowerUpcomingRepaymentCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('email:borrower:upcoming_repayment')
            ->setDescription('Send emails to borrower and lenders when an early repayment is done');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $entityManger */
        $entityManger = $this->getContainer()->get('unilend.service.entity_manager');
        /** @var \prelevements $oDirectDebit */
        $oDirectDebit = $entityManger->getRepository('prelevements');
        /** @var \projects $project */
        $project = $entityManger->getRepository('projects');
        /** @var \companies $company */
        $company = $entityManger->getRepository('companies');
        /** @var \clients $client */
        $client = $entityManger->getRepository('clients');
        /** @var \echeanciers_emprunteur $oPaymentSchedule */
        $oPaymentSchedule    = $entityManger->getRepository('echeanciers_emprunteur');
        /** @var \settings $settings */
        $settings = $entityManger->getRepository('settings');
        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');
        $aUpcomingRepayments = $oPaymentSchedule->getUpcomingRepayments(7);

        foreach ($aUpcomingRepayments as $aRepayment) {
            $aDirectDebit = $oDirectDebit->select('id_project = ' . $aRepayment['id_project'] . ' AND type = 2 AND num_prelevement = ' . $aRepayment['ordre']);

            if (false === empty($aDirectDebit)) {
                $project->get($aRepayment['id_project']);
                $company->get($project->id_company);

                if (false === empty($company->prenom_dirigeant) && false === empty($company->email_dirigeant)) {
                    $sFirstName  = $company->prenom_dirigeant;
                    $sMailClient = $company->email_dirigeant;
                } else {
                    $client->get($company->id_client_owner);
                    $sFirstName  = $client->prenom;
                    $sMailClient = $client->email;
                }

                /** @var \loans $oLoans */
                $oLoans = $entityManger->getRepository('loans');

                $settings->get('Facebook', 'type');
                $sFB      = $settings->value;
                $settings->get('Twitter', 'type');
                $sTwitter = $settings->value;
                $sUrl     = $this->getContainer()->getParameter('router.request_context.scheme') . '://' . $this->getContainer()->getParameter('router.request_context.host');

                $aMail = array(
                    'nb_emprunteurs'     => $oLoans->getNbPreteurs($aRepayment['id_project']),
                    'echeance'           => $ficelle->formatNumber($aDirectDebit[0]['montant'] / 100),
                    'prochaine_echeance' => date('d/m/Y', strtotime($aRepayment['date_echeance_emprunteur'])),
                    'surl'               => $sUrl,
                    'url'                => $sUrl,
                    'nom_entreprise'     => $company->name,
                    'montant'            => $ficelle->formatNumber((float) $project->amount, 0),
                    'prenom_e'           => $sFirstName,
                    'lien_fb'            => $sFB,
                    'lien_tw'            => $sTwitter
                );

                /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                $message = $this->getContainer()->get('unilend.swiftmailer.message_provider')->newMessage('mail-echeance-emprunteur', $aMail);
                $message->setTo($sMailClient);
                $mailer = $this->getContainer()->get('mailer');
                $mailer->send($message);
            }
        }
    }
}