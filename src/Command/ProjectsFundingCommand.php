<?php

namespace Unilend\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\{Input\InputInterface, Output\OutputInterface};
use Unilend\Entity\{Projects, ProjectsStatus};
use Unilend\Service\AcceptedBidAndLoanNotificationSender;
use Unilend\librairies\CacheKeys;

class ProjectsFundingCommand extends ContainerAwareCommand
{
    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        $this
            ->setName('projects:funding')
            ->setDescription('Check projects that are in FUNDING status');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        ini_set('memory_limit', '1G');

        $entityManagerSimulator         = $this->getContainer()->get('unilend.service.entity_manager');
        $mailerManager                  = $this->getContainer()->get('unilend.service.email_manager');
        $acceptedBidsNotificationSender = $this->getContainer()->get(AcceptedBidAndLoanNotificationSender::class);
        $logger                         = $this->getContainer()->get('monolog.logger.console');
        $projectManager                 = $this->getContainer()->get('unilend.service.project_manager');
        $projectLifecycleManager        = $this->getContainer()->get('unilend.service.project_lifecycle_manager');
        $entityManager                  = $this->getContainer()->get('doctrine.orm.entity_manager');
        $projectRepository              = $entityManager->getRepository(Projects::class);

        /** @var \loans $loan */
        $loan = $entityManagerSimulator->getRepository('loans');

        $projects           = $projectRepository->findBy(['status' => ProjectsStatus::STATUS_ONLINE]);
        $hasProjectFinished = false;

        foreach ($projects as $project) {
            $output->writeln('Project : ' . $project->getTitle());

            try {
                $currentDate      = new \DateTime();
                $endDate          = $projectManager->getProjectEndDate($project);
                $isFunded         = $projectManager->isFunded($project);
                $isRateMinReached = $projectManager->isRateMinReached($project);

                if ($isFunded) {
                    if (false === $isRateMinReached && $endDate > $currentDate && empty($project->getDateFunded())) {
                        $mailerManager->sendFundedToBorrower($project);
                    }

                    $projectLifecycleManager->markAsFunded($project);
                }

                if ($endDate > $currentDate && false === $isRateMinReached) {
                    $projectLifecycleManager->checkBids($project, true);
                    $projectLifecycleManager->autoBid($project);
                } else {
                    /**
                     * Useful in case of the project was not funded in prepublish step
                     */
                    if (empty($project->getDateFin())) {
                        $project->setDateFin($currentDate);
                        $entityManager->flush($project);
                    }

                    $hasProjectFinished = true;

                    if ($isFunded) {
                        $projectLifecycleManager->buildLoans($project);
                        $projectLifecycleManager->createRepaymentSchedule($project);
                        $projectLifecycleManager->createPaymentSchedule($project);
                        $projectLifecycleManager->saveInterestRate($project);

                        $mailerManager->sendFundedAndFinishedToBorrower($project);
                        $acceptedBidsNotificationSender->sendBidAccepted($project);
                    } else {
                        $projectLifecycleManager->treatFundFailed($project);
                        $projectLifecycleManager->saveInterestRate($project);

                        $mailerManager->sendFundFailedToBorrower($project);
                        $mailerManager->sendFundFailedToLender($project);
                    }

                    $now          = new \DateTime();
                    $slackManager = $this->getContainer()->get('unilend.service.slack_manager');
                    $message      = $slackManager->getProjectName($project) . ' - Cloturé le ' . $now->format('d/m/Y à H:i') . ' (' . $loan->getNbPreteurs($project->getIdProject()) . ' prêteurs - ' . str_replace('.', ',', round($projectRepository->getAverageInterestRate($project, false), 2)) . '%)';
                    $slackManager->sendMessage($message);

                    $mailerManager->sendProjectFinishedToStaff($project);
                }
            } catch (\Exception $exception) {
                $logger->critical('An exception occurred during funding of project ' . $project->getIdProject() . ' with message: ' . $exception->getMessage(), [
                    'id_project' => $project->getIdProject(),
                    'class'      => __CLASS__,
                    'function'   => __FUNCTION__,
                    'file'       => $exception->getFile(),
                    'line'       => $exception->getLine()
                ]);
            }
        }

        if ($hasProjectFinished) {
            $cacheDriver = $entityManager->getConfiguration()->getResultCacheImpl();
            $cacheDriver->delete(CacheKeys::LIST_PROJECTS);
        }
    }
}
