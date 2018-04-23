<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    ProjectAbandonReason, Projects, ProjectsStatus, Users
};
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\core\Loader;

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

        /** @var EntityManager $entityManagerSimulator */
        $entityManagerSimulator = $this->getContainer()->get('unilend.service.entity_manager');
        /** @var LoggerInterface $logger */
        $logger = $this->getContainer()->get('monolog.logger.console');

        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');

        /** @var \prescripteurs $prescripteur */
        $prescripteur = $entityManagerSimulator->getRepository('prescripteurs');
        /** @var \projects_status_history $projectStatusHistory */
        $projectStatusHistory = $entityManagerSimulator->getRepository('projects_status_history');
        /** @var \settings $settings */
        $settings = $entityManagerSimulator->getRepository('settings');
        /** @var \projects $projectData */
        $projectData = $entityManagerSimulator->getRepository('projects');

        $settings->get('Intervales relances emprunteurs', 'type');
        $aReminderIntervals = json_decode($settings->value, true);

        $settings->get('Durée moyenne financement', 'type');
        $aAverageFundingDurations = json_decode($settings->value, true);

        $settings->get('Adresse emprunteur', 'type');
        $sBorrowerEmail = $settings->value;

        $settings->get('Téléphone emprunteur', 'type');
        $sBorrowerPhoneNumber = $settings->value;

        $sUrl = $this->getContainer()->getParameter('router.request_context.scheme') . '://' . $this->getContainer()->getParameter('url.host_default');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectStatusManager $projectStatusManager */
        $projectStatusManager = $this->getContainer()->get('unilend.service.project_status_manager');



        $entityManager        = $this->getContainer()->get('doctrine.orm.entity_manager');
        $projectsRepository   = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects');
        $projectAbandonReason = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectAbandonReason')
            ->findBy(['label' => ProjectAbandonReason::BORROWER_FOLLOW_UP_UNSUCCESSFUL]);

        foreach ($aReminderIntervals as $sStatus => $aIntervals) {
            if (1 === preg_match('/^status-([1-9][0-9]*)$/', $sStatus, $aMatches)) {
                $iStatus                       = (int) $aMatches[1];
                $iLastIndex                    = count($aIntervals);
                $iPreviousReminderDaysInterval = 0;

                foreach ($aIntervals as $iReminderIndex => $iDaysInterval) {
                    $iDaysSincePreviousReminder = $iDaysInterval - $iPreviousReminderDaysInterval;

                    foreach ($projectData->getReminders($iStatus, $iDaysSincePreviousReminder, $iReminderIndex - 1) as $iProjectId) {
                        try {
                            /** @var Projects $project */
                            $project = $projectsRepository->find($iProjectId);

                            if (null === $project) {
                                $logger->error('The identifier ' . $iProjectId . ' does not correpond to any project.', [
                                    'class'    => __CLASS__,
                                    'function' => __FUNCTION__
                                ]);
                                continue;
                            }
                            $company = $project->getIdCompany();

                            if ($project->getIdPrescripteur() > 0 && $prescripteur->get($project->getIdPrescripteur(), 'id_prescripteur')) {
                                $client = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($prescripteur->id_client);
                            } elseif (null !== $company->getIdClientOwner()) {
                                $client = $company->getIdClientOwner();
                            } else {
                                $logger->error('Cannot send reminder (project ' . $project->getIdProject() . '). No associated client found.', [
                                    'id_project' => $project->getIdProject(),
                                    'class'      => __CLASS__,
                                    'function'   => __FUNCTION__
                                ]);

                                continue;
                            }

                            $email = preg_replace('/^(.*)-[0-9]+$/', '$1', $client->getEmail());

                            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                $projectStatusHistory->loadLastProjectHistory($project->getIdProject());

                                $oSubmissionDate = $project->getAdded();

                                // @todo arbitrary default value
                                $iAverageFundingDuration = 15;
                                reset($aAverageFundingDurations);
                                foreach ($aAverageFundingDurations as $aAverageFundingDuration) {
                                    if ($project->getAmount() >= $aAverageFundingDuration['min'] && $project->getAmount() <= $aAverageFundingDuration['max']) {
                                        $iAverageFundingDuration = $aAverageFundingDuration['heures'] / 24;
                                        break;
                                    }
                                }

                                $keywords = [
                                    'firstName'                  => $client->getPrenom(),
                                    'requestDate'                => strftime('%d %B %Y', $oSubmissionDate->getTimestamp()),
                                    'companyName'                => $company->getName(),
                                    'continueRequestLink'        => $sUrl . '/depot_de_dossier/reprise/' . $project->getHash(),
                                    'stopReminderEmails'         => $sUrl . '/depot_de_dossier/emails/' . $project->getHash(),
                                    'fundingPercentage'          => $iDaysInterval > $iAverageFundingDuration ? 100 : round(100 - ($iAverageFundingDuration - $iDaysInterval) / $iAverageFundingDuration * 100),
                                    'borrowerServicePhoneNumber' => $sBorrowerPhoneNumber,
                                    'borrowerServiceEmail'       => $sBorrowerEmail,
                                    'amount'                     => $ficelle->formatNumber($project->getAmount(), 0)
                                ];

                                if (in_array($iStatus, [ProjectsStatus::INCOMPLETE_REQUEST, ProjectsStatus::COMPLETE_REQUEST])) {
                                    $oCompletenessDate        = $projectStatusHistory->getDateProjectStatus($project->getIdProject(), ProjectsStatus::INCOMPLETE_REQUEST, true);
                                    $keywords['date_demande'] = strftime('%d %B %Y', $oCompletenessDate->getTimestamp());
                                }

                                /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                                $message = $this->getContainer()->get('unilend.swiftmailer.message_provider')->newMessage('depot-dossier-relance-status-' . $iStatus . '-' . $iReminderIndex, $keywords);

                                try {
                                    $message->setTo($email);
                                    $mailer = $this->getContainer()->get('mailer');
                                    $mailer->send($message);
                                } catch (\Exception $exception) {
                                    $logger->warning(
                                        'Could not send email: depot-dossier-relance-status-' . $iStatus . '-' . $iReminderIndex . ' - Exception: ' . $exception->getMessage(), [
                                            'id_mail_template' => $message->getTemplateId(),
                                            'id_client'        => $client->getIdClient(),
                                            'class'            => __CLASS__,
                                            'function'         => __FUNCTION__
                                        ]
                                    );
                                }
                            }

                            /**
                             * When project is pending documents, abort status is not automatic and must be set manually in BO
                             */
                            if ($iReminderIndex === $iLastIndex && $iStatus != \projects_status::COMMERCIAL_REVIEW) {
                                $projectStatusManager->abandonProject($project, $projectAbandonReason, Users::USER_ID_CRON, $iReminderIndex);
                            } else {
                                $projectStatusManager->addProjectStatus(Users::USER_ID_CRON, $iStatus, $project, $iReminderIndex, $projectStatusHistory->content);
                            }
                        } catch (\Exception $exception) {
                            $logger->error('Cannot send reminder (project ' . $project->getIdProject() . ') - Message: "' . $exception->getMessage() . '"', [
                                'class'      => __CLASS__,
                                'function'   => __FUNCTION__,
                                'id_project' => $project->getIdProject()
                            ]);
                        }
                    }

                    $iPreviousReminderDaysInterval = $iDaysInterval;
                }
            }
        }
    }
}
