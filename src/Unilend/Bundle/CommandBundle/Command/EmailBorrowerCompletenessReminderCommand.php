<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\core\Loader;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class EmailBorrowerCompletenessReminderCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('email:borrower:completeness_reminder')
            ->setDescription('Sends an email to potential borrowers reminding them of missing documents');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        ini_set('memory_limit', '1G');

        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('unilend.service.entity_manager');
        /** @var \prescripteurs $prescripteur */
        $prescripteur                = $entityManager->getRepository('prescripteurs');
        /** @var \projects_status_history $projectStatusHistory */
        $projectStatusHistory      = $entityManager->getRepository('projects_status_history');
        /** @var \projects_last_status_history $projectLastStatusHistory */
        $projectLastStatusHistory = $entityManager->getRepository('projects_last_status_history');
        /** @var \settings $settings */
        $settings = $entityManager->getRepository('settings');
        /** @var \projects $project */
        $project = $entityManager->getRepository('projects');
        /** @var \clients $client */
        $client = $entityManager->getRepository('clients');
        /** @var \companies $company */
        $company = $entityManager->getRepository('companies');
        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');
        /** @var LoggerInterface $logger */
        $logger = $this->getContainer()->get('monolog.logger.console');

        $settings->get('Intervales relances emprunteurs', 'type');
        $aReminderIntervals = json_decode($settings->value, true);

        $settings->get('Durée moyenne financement', 'type');
        $aAverageFundingDurations = json_decode($settings->value, true);

        $settings->get('Adresse emprunteur', 'type');
        $sBorrowerEmail = $settings->value;

        $settings->get('Téléphone emprunteur', 'type');
        $sBorrowerPhoneNumber = $settings->value;

        $settings->get('Facebook', 'type');
        $sFB      = $settings->value;
        $settings->get('Twitter', 'type');
        $sTwitter = $settings->value;
        $sUrl     = $this->getContainer()->getParameter('router.request_context.scheme') . '://' . $this->getContainer()->getParameter('url.host_default');

        $aReplacements = array(
            'adresse_emprunteur'   => $sBorrowerEmail,
            'telephone_emprunteur' => $sBorrowerPhoneNumber,
            'furl'                 => $sUrl,
            'surl'                 => $sUrl,
            'lien_fb'              => $sFB,
            'lien_tw'              => $sTwitter
        );

        foreach ($aReminderIntervals as $sStatus => $aIntervals) {
            if (1 === preg_match('/^status-([1-9][0-9]*)$/', $sStatus, $aMatches)) {
                $iStatus                       = (int) $aMatches[1];
                $iLastIndex                    = count($aIntervals);
                $iPreviousReminderDaysInterval = 0;

                foreach ($aIntervals as $iReminderIndex => $iDaysInterval) {
                    $iDaysSincePreviousReminder = $iDaysInterval - $iPreviousReminderDaysInterval;

                    foreach ($project->getReminders($iStatus, $iDaysSincePreviousReminder, $iReminderIndex - 1) as $iProjectId) {
                        try {
                            $project->get($iProjectId, 'id_project');
                            $company->get($project->id_company, 'id_company');

                            if ($project->id_prescripteur > 0) {
                                $prescripteur->get($project->id_prescripteur, 'id_prescripteur');
                                $client->get($prescripteur->id_client, 'id_client');
                            } else {
                                $client->get($company->id_client_owner, 'id_client');
                            }

                            if (false === empty($client->email) && 0 == $project->stop_relances) {
                                $projectLastStatusHistory->get($project->id_project, 'id_project');
                                $projectStatusHistory->get($projectLastStatusHistory->id_project_status_history, 'id_project_status_history');

                                $oSubmissionDate = new \DateTime($project->added);

                                // @todo arbitrary default value
                                $iAverageFundingDuration = 15;
                                reset($aAverageFundingDurations);
                                foreach ($aAverageFundingDurations as $aAverageFundingDuration) {
                                    if ($project->amount >= $aAverageFundingDuration['min'] && $project->amount <= $aAverageFundingDuration['max']) {
                                        $iAverageFundingDuration = $aAverageFundingDuration['heures'] / 24;
                                        break;
                                    }
                                }

                                if (in_array($iStatus, array(7, 8))) {
                                    $oCompletenessDate                       = $projectStatusHistory->getDateProjectStatus($project->id_project, \projects_status::COMPLETUDE_ETAPE_2, true);
                                    $aReplacements['date_completude_etape2'] = strftime('%d %B %Y', $oCompletenessDate->getTimestamp());
                                }

                                $aReplacements['liste_pieces']            = $projectStatusHistory->content;
                                $aReplacements['raison_sociale']          = $company->name;
                                $aReplacements['prenom']                  = $client->prenom;
                                $aReplacements['montant']                 = $ficelle->formatNumber($project->amount, 0);
                                $aReplacements['delai_demande']           = $iDaysInterval;
                                $aReplacements['lien_reprise_dossier']    = $sUrl . '/depot_de_dossier/reprise/' . $project->hash;
                                $aReplacements['lien_stop_relance']       = $sUrl . '/depot_de_dossier/emails/' . $project->hash;
                                $aReplacements['date_demande']            = strftime('%d %B %Y', $oSubmissionDate->getTimestamp());
                                $aReplacements['pourcentage_financement'] = $iDaysInterval > $iAverageFundingDuration ? 100 : round(100 - ($iAverageFundingDuration - $iDaysInterval) / $iAverageFundingDuration * 100);
                                $aReplacements['annee']                   = date('Y');

                                $sRecipientEmail = preg_replace('/^(.+)-[0-9]+$/', '$1', trim($client->email));

                                /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                                $message = $this->getContainer()->get('unilend.swiftmailer.message_provider')->newMessage('depot-dossier-relance-status-' . $iStatus . '-' . $iReminderIndex, $aReplacements);var_dump('depot-dossier-relance-status-' . $iStatus . '-' . $iReminderIndex);
                                $message->setTo($sRecipientEmail);
                                $mailer = $this->getContainer()->get('mailer');
                                $mailer->send($message);
                            }

                            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager $oProjectManager */
                            $oProjectManager = $this->getContainer()->get('unilend.service.project_manager');

                            /**
                             * When project is pending documents, abort status is not automatic and must be set manually in BO
                             */
                            if ($iReminderIndex === $iLastIndex && $iStatus != \projects_status::EN_ATTENTE_PIECES) {
                                $oProjectManager->addProjectStatus(\users::USER_ID_CRON, \projects_status::ABANDON, $project, $iReminderIndex, $projectStatusHistory->content);
                            } else {
                                $oProjectManager->addProjectStatus(\users::USER_ID_CRON, $iStatus, $project, $iReminderIndex, $projectStatusHistory->content);
                            }
                        } catch (\Exception $oException) {
                            $logger->error('Cannot send reminder for project id_project=' . $project->id_project . '(Exception message : ' . $oException->getMessage() . ')', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $project->id_project));
                        }
                    }

                    $iPreviousReminderDaysInterval = $iDaysInterval;
                }
            }
        }
    }
}
