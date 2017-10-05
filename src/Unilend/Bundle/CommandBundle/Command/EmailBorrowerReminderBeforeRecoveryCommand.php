<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\core\Loader;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class EmailBorrowerReminderBeforeRecoveryCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('email:borrower:reminder_before_recovery')
            ->setDescription('Send email to borrower with unpaid repayments before recovery process');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $entityManger */
        $entityManger = $this->getContainer()->get('unilend.service.entity_manager');

        /** @var \projects $project */
        $project  = $entityManger->getRepository('projects');
        $projects = $project->getProblematicProjectsWithUpcomingRepayment();

        if (false === empty($projects)) {
            /** @var \ficelle $ficelle */
            $ficelle = Loader::loadLib('ficelle');

            /** @var \clients $client */
            $client = $entityManger->getRepository('clients');
            /** @var \companies $company */
            $company = $entityManger->getRepository('companies');
            /** @var \echeanciers $lenderRepaymentSchedule */
            $lenderRepaymentSchedule = $entityManger->getRepository('echeanciers');
            /** @var \echeanciers_emprunteur $borrowerRepaymentSchedule */
            $borrowerRepaymentSchedule = $entityManger->getRepository('echeanciers_emprunteur');
            /** @var \loans $loans */
            $loans = $entityManger->getRepository('loans');
            /** @var \settings $settings */
            $settings = $entityManger->getRepository('settings');

            $settings->get('Virement - BIC', 'type');
            $bic = $settings->value;

            $settings->get('Virement - IBAN', 'type');
            $iban = $settings->value;

            $settings->get('Téléphone emprunteur', 'type');
            $borrowerServicePhoneNumber = $settings->value;

            $settings->get('Adresse emprunteur', 'type');
            $borrowerServiceEmail = $settings->value;

            $settings->get('Facebook', 'type');
            $facebookLink = $settings->value;

            $settings->get('Twitter', 'type');
            $twitterLink = $settings->value;

            $frontUrl  = $this->getContainer()->getParameter('router.request_context.scheme') . '://' . $this->getContainer()->getParameter('url.host_default');
            $staticUrl = $this->getContainer()->get('assets.packages')->getUrl('');

            $commonReplacements = array(
                'url'              => $frontUrl,
                'surl'             => $staticUrl,
                'bic_sfpmei'       => $bic,
                'iban_sfpmei'      => $iban,
                'tel_emprunteur'   => $borrowerServicePhoneNumber,
                'email_emprunteur' => $borrowerServiceEmail,
                'lien_fb'          => $facebookLink,
                'lien_tw'          => $twitterLink,
                'annee'            => date('Y')
            );

            foreach ($projects as $aProject) {
                $project->get($aProject['id_project']);
                $company->get($project->id_company);
                $client->get($company->id_client_owner);

                $nextRepayment = $borrowerRepaymentSchedule->select('id_project = ' . $project->id_project . ' AND date_echeance_emprunteur > DATE(NOW())', 'date_echeance_emprunteur ASC', 0, 1);
                $replacements  = $commonReplacements + array(
                        'entreprise'                         => htmlentities($company->name, null, 'UTF-8'),
                        'civilite_e'                         => $client->civilite,
                        'prenom_e'                           => htmlentities($client->prenom, null, 'UTF-8'),
                        'nom_e'                              => htmlentities($client->nom, null, 'UTF-8'),
                        'mensualite_e'                       => $ficelle->formatNumber(($nextRepayment[0]['montant'] + $nextRepayment[0]['commission'] + $nextRepayment[0]['tva']) / 100),
                        'num_dossier'                        => $project->id_project,
                        'nb_preteurs'                        => $loans->getNbPreteurs($project->id_project),
                        'CRD'                                => $ficelle->formatNumber($lenderRepaymentSchedule->getOwedCapital(['id_project' => $project->id_project])),
                        'date_prochaine_echeance_emprunteur' => \DateTime::createFromFormat('Y-m-d H:i:s', $nextRepayment[0]['date_echeance_emprunteur'])->format('d/m/Y') // @todo Intl
                    );
                $message       = $this->getContainer()->get('unilend.swiftmailer.message_provider')->newMessage('emprunteur-projet-statut-probleme-j-x-avant-prochaine-echeance', $replacements);
                try {
                    $message->setTo(trim($client->email));
                    $mailer = $this->getContainer()->get('mailer');
                    $mailer->send($message);
                } catch (\Exception $exception) {
                    $logger = $this->getContainer()->get('monolog.logger.console');
                    $logger->warning(
                        'Could not send email: emprunteur-projet-statut-probleme-j-x-avant-prochaine-echeance - Exception: ' . $exception->getMessage(),
                        ['id_mail_template' => $message->getTemplateId(), 'id_client' => $client->id_client, 'class' => __CLASS__, 'function' => __FUNCTION__]
                    );
                }
            }
        }
    }
}
