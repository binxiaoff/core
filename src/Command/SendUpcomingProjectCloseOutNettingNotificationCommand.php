<?php

namespace Unilend\Command;

use Doctrine\DBAL\DBALException;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\{InputArgument, InputInterface};
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Entity\{Projects, Settings};
use Unilend\Bundle\CoreBusinessBundle\Service\{DebtCollectionMissionManager, ProjectCloseOutNettingManager};

class SendUpcomingProjectCloseOutNettingNotificationCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('unilend:project:upcoming_close_out_netting:notify')
            ->setDescription('Send notifications about upcoming projects close out netting, X days before the close out netting limit date')
            ->addArgument('interval', InputArgument::REQUIRED, 'Number of days left before lenders repayment date');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $slackManager                 = $this->getContainer()->get('unilend.service.slack_manager');
        $entityManager                = $this->getContainer()->get('doctrine.orm.entity_manager');
        $debtCollectionMissionManager = $this->getContainer()->get('unilend.service.debt_collection_mission_manager');
        $projectRepository            = $entityManager->getRepository(Projects::class);
        $logger                       = $this->getContainer()->get('monolog.logger.console');

        $interval = $input->getArgument('interval');
        try {
            $projectsList = $entityManager->getRepository(Projects::class)->getProjectsWithUpcomingCloseOutNettingDate($interval);
        } catch (DBALException $exception) {
            $logger->error(
                'Could not get projects list to check upcoming close out netting. Error: ' . $exception->getMessage(),
                ['method' => __METHOD__, 'file' => $exception->getFile(), 'line' => $exception->getLine()]
            );
            return;
        }
        $rows         = '';
        $projectNames = [];
        $scheme       = $this->getContainer()->getParameter('router.request_context.scheme');
        $host         = $this->getContainer()->getParameter('url.host_admin');
        $projectUrl   = $scheme . '://' . $host . '/dossiers/edit/';
        $today        = new \DateTime();

        foreach ($projectsList as $projectRow) {
            try {
                /** @var Projects $project */
                $project     = $projectRepository->find($projectRow['id_project']);
                $fundingDate = new \DateTime($projectRow['funding_date']);
                $daysLeft    = $today->diff(new \DateTime($projectRow['last_repayment_date']))->days;
                $output->writeln('Projet: ' . $project->getIdProject() . ' days left: ' . $daysLeft);
                $isDebtCollectionFeeDueToBorrower = $debtCollectionMissionManager->isDebtCollectionFeeDueToBorrower($project);
                if (
                    (ProjectCloseOutNettingManager::OVERDUE_LIMIT_DAYS_FIRST_GENERATION_LOANS - $interval === $daysLeft && false === $isDebtCollectionFeeDueToBorrower)
                    || (ProjectCloseOutNettingManager::OVERDUE_LIMIT_DAYS_SECOND_GENERATION_LOANS - $interval === $daysLeft && true === $isDebtCollectionFeeDueToBorrower)
                ) {
                    $otherFundedProjects = $projectRepository->getFundedProjectsBelongingToTheSameCompany($project->getIdProject(), $project->getIdCompany()->getSiren());
                    $rows                .= '
                        <tr>
                            <td><a href="' . $projectUrl . $project->getIdProject() . '">' . $project->getIdProject() . '</a></td>
                            <td>' . $project->getTitle() . '</td>
                            <td>' . $daysLeft . '</td>
                            <td>' . $interval . '</td>
                            <td>' . $fundingDate->format('d/m/Y') . '</td>
                            <td>' . (true === $isDebtCollectionFeeDueToBorrower ? 'Emprunteur' : 'Prêteurs') . '</td>
                            <td>' . implode(', ', $otherFundedProjects) . '</td>
                        </tr>';
                    $projectNames[]      = $slackManager->getProjectName($project);

                    foreach ($otherFundedProjects as $projectId) {
                        $project        = $projectRepository->find($projectId);
                        $projectNames[] = $slackManager->getProjectName($project);
                    }

                }
            } catch (\Exception $exception) {
                $logger->error(
                    'Could not get details for upcoming close out netting projects. Error: ' . $exception->getMessage(),
                    ['method' => __METHOD__, 'id_project' => $project->getIdProject(), 'file' => $exception->getFile(), 'line' => $exception->getLine()]
                );
            }
        }
        if (false === empty($rows)) {
            $this->sendEmail($rows, $interval);
            $this->sendSlack($projectNames, $interval);
        }
    }

    /**
     * @param array $projectNames
     * @param int   $interval
     */
    private function sendSlack(array $projectNames, int $interval): void
    {
        $slackManager = $this->getContainer()->get('unilend.service.slack_manager');
        $logger       = $this->getContainer()->get('monolog.logger.console');
        $settingType  = 'Slack notification decheance du terme a venir';

        /** @var Settings $slackListSetting */
        $slackListSetting = $this->getContainer()->get('doctrine.orm.entity_manager')
            ->getRepository(Settings::class)->findOneBy(['type' => $settingType]);
        if (null !== $slackListSetting) {
            foreach (explode(',', $slackListSetting->getValue()) as $slackChannel) {
                $slackManager->sendMessage('Projets à déchoir sous ' . $interval . ' jours: ' . implode(', ', $projectNames), $slackChannel);
            }
        } else {
            $logger->error('Could not send slack notification, no configured slack channels found.', ['method' => __METHOD__, 'setting_type' => $settingType]);
        }
    }

    /**
     * @param string $projectsList
     * @param int    $interval
     */
    private function sendEmail(string $projectsList, int $interval): void
    {
        $settingTypes = ['Adresse analystes risque', 'Adresse controle interne'];
        $emails       = [];

        $mailType                 = 'notification-decheance-du-terme-a-venir';
        $debtCollectionChangeDate = \DateTime::createFromFormat('Y-m-d', DebtCollectionMissionManager::DEBT_COLLECTION_CONDITION_CHANGE_DATE);
        $keywords                 = [
            'projectList' => $projectsList,
            'changeDate'  => $debtCollectionChangeDate->format('d/m/Y'),
            'interval'    => $interval
        ];
        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->getContainer()->get('unilend.swiftmailer.message_provider')->newMessage($mailType, $keywords);
        try {
            /** @var Settings[] $recipients */
            $recipients = $this->getContainer()->get('doctrine.orm.entity_manager')
                ->getRepository(Settings::class)->findBy(['type' => $settingTypes]);
            foreach ($recipients as $recipient) {
                $emails[] = $recipient->getValue();
            }
            if (empty($emails)) {
                $this->getContainer()->get('monolog.logger.console')
                    ->error('Could not send the email notification, no configured email address found.', ['method' => __METHOD__, 'setting_type' => $settingTypes]);
                return;
            }
            $message->setTo($emails);
            $mailer = $this->getContainer()->get('mailer');
            $mailer->send($message);
        } catch (\Exception $exception) {
            $logger = $this->getContainer()->get('monolog.logger.console');
            $logger->error(
                'Could not send email: ' . $mailType . '. - Exception: ' . $exception->getMessage(),
                ['id_mail_template' => $message->getTemplateId(), 'file' => $exception->getFile(), 'line' => $exception->getLine()]
            );
        }
    }
}
