<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;
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
        /** @var LoggerInterface $logger */
        $logger = $this->getContainer()->get('monolog.logger.console');

        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');

        /** @var \prescripteurs $prescripteur */
        $prescripteur = $entityManager->getRepository('prescripteurs');
        /** @var \projects_status_history $projectStatusHistory */
        $projectStatusHistory = $entityManager->getRepository('projects_status_history');
        /** @var \settings $settings */
        $settings = $entityManager->getRepository('settings');
        /** @var \projects $project */
        $project = $entityManager->getRepository('projects');
        /** @var \clients $client */
        $client = $entityManager->getRepository('clients');
        /** @var \companies $company */
        $company = $entityManager->getRepository('companies');

        $settings->get('Intervales relances emprunteurs', 'type');
        $aReminderIntervals = json_decode($settings->value, true);

        $settings->get('Durée moyenne financement', 'type');
        $aAverageFundingDurations = json_decode($settings->value, true);

        $settings->get('Adresse emprunteur', 'type');
        $sBorrowerEmail = $settings->value;

        $settings->get('Téléphone emprunteur', 'type');
        $sBorrowerPhoneNumber = $settings->value;

        $sUrl            = $this->getContainer()->getParameter('router.request_context.scheme') . '://' . $this->getContainer()->getParameter('url.host_default');
        $oProjectManager = $this->getContainer()->get('unilend.service.project_manager');

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

                            if (filter_var($client->email, FILTER_VALIDATE_EMAIL)) {
                                $projectStatusHistory->loadLastProjectHistory($project->id_project);

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

                                $keywords = [
                                    'firstName'                  => $client->prenom,
                                    'requestDate'                => strftime('%d %B %Y', $oSubmissionDate->getTimestamp()),
                                    'companyName'                => $company->name,
                                    'continueRequestLink'        => $sUrl . '/depot_de_dossier/reprise/' . $project->hash,
                                    'stopReminderEmails'         => $sUrl . '/depot_de_dossier/emails/' . $project->hash,
                                    'fundingPercentage'          => $iDaysInterval > $iAverageFundingDuration ? 100 : round(100 - ($iAverageFundingDuration - $iDaysInterval) / $iAverageFundingDuration * 100),
                                    'borrowerServicePhoneNumber' => $sBorrowerPhoneNumber,
                                    'borrowerServicePhoneEmail'  => $sBorrowerEmail,
                                    'amount'                     => $ficelle->formatNumber($project->amount, 0)
                                ];

                                if (in_array($iStatus, [\projects_status::INCOMPLETE_REQUEST, \projects_status::COMPLETE_REQUEST])) {
                                    $oCompletenessDate                       = $projectStatusHistory->getDateProjectStatus($project->id_project, \projects_status::INCOMPLETE_REQUEST, true);
                                    $keywords['date_demande'] = strftime('%d %B %Y', $oCompletenessDate->getTimestamp());
                                }

                                $sRecipientEmail = preg_replace('/^(.+)-[0-9]+$/', '$1', trim($client->email));

                                /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                                $message = $this->getContainer()->get('unilend.swiftmailer.message_provider')->newMessage('depot-dossier-relance-status-' . $iStatus . '-' . $iReminderIndex, $keywords);

                                try {
                                    $message->setTo($sRecipientEmail);
                                    $mailer = $this->getContainer()->get('mailer');
                                    $mailer->send($message);
                                } catch (\Exception $exception) {
                                    $logger->warning(
                                        'Could not send email: depot-dossier-relance-status-' . $iStatus . '-' . $iReminderIndex . ' - Exception: ' . $exception->getMessage(),
                                        ['id_mail_template' => $message->getTemplateId(), 'id_client' => $client->id_client, 'class' => __CLASS__, 'function' => __FUNCTION__]
                                    );
                                }
                            }

                            /**
                             * When project is pending documents, abort status is not automatic and must be set manually in BO
                             */
                            if ($iReminderIndex === $iLastIndex && $iStatus != \projects_status::COMMERCIAL_REVIEW) {
                                $oProjectManager->addProjectStatus(Users::USER_ID_CRON, \projects_status::ABANDONED, $project, $iReminderIndex, $projectStatusHistory->content);
                            } else {
                                $oProjectManager->addProjectStatus(Users::USER_ID_CRON, $iStatus, $project, $iReminderIndex, $projectStatusHistory->content);
                            }
                        } catch (\Exception $oException) {
                            $logger->error('Cannot send reminder (project ' . $project->id_project . ') - Message: "' . $oException->getMessage() . '"', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $project->id_project));
                        }
                    }

                    $iPreviousReminderDaysInterval = $iDaysInterval;
                }
            }
        }
    }
}
