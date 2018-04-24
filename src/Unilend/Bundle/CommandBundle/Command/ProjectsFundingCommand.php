<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Cache\Adapter\Memcache\MemcacheCachePool;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\librairies\CacheKeys;

class ProjectsFundingCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('projects:funding')
            ->setDescription('Check projects that are in FUNDING status');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        ini_set('memory_limit', '1G');

        /** @var EntityManager $entityManagerSimulator */
        $entityManagerSimulator = $this->getContainer()->get('unilend.service.entity_manager');
        $mailerManager = $this->getContainer()->get('unilend.service.email_manager');
        $logger = $this->getContainer()->get('monolog.logger.console');
        $projectManager          = $this->getContainer()->get('unilend.service.project_manager');
        $projectLifecycleManager = $this->getContainer()->get('unilend.service.project_lifecycle_manager');

        /** @var \projects $project */
        $project = $entityManagerSimulator->getRepository('projects');
        /** @var \loans $loan */
        $loan = $entityManagerSimulator->getRepository('loans');

        $hasProjectFinished = false;
        $projects           = $project->selectProjectsByStatus([\projects_status::EN_FUNDING], '', [], '', '', false);

        foreach ($projects as $projectTable) {
            if ($project->get($projectTable['id_project'])) {
                $output->writeln('Project : ' . $project->title);

                try {
                    $currentDate      = new \DateTime();
                    $endDate          = $projectManager->getProjectEndDate($project);
                    $isFunded         = $projectManager->isFunded($project);
                    $isRateMinReached = $projectManager->isRateMinReached($project);

                    if ($isFunded) {
                        if (
                            false === $isRateMinReached
                            && $endDate > $currentDate
                            && ($project->date_funded === '0000-00-00 00:00:00' || empty($project->date_funded)) // To answer that this email was not already sent
                        ) {
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
                        if ($project->date_fin === '0000-00-00 00:00:00') {
                            $project->date_fin = $currentDate->format('Y-m-d H:i:s');
                            $project->update();
                        }

                        $hasProjectFinished = true;

                        if ($isFunded) {
                            $projectLifecycleManager->buildLoans($project);
                            $projectLifecycleManager->createRepaymentSchedule($project);
                            $projectLifecycleManager->createPaymentSchedule($project);
                            $projectLifecycleManager->saveInterestRate($project);

                            $mailerManager->sendFundedAndFinishedToBorrower($project);
                            $mailerManager->sendBidAccepted($project);
                        } else {
                            $projectLifecycleManager->treatFundFailed($project);
                            $projectLifecycleManager->saveInterestRate($project);

                            $mailerManager->sendFundFailedToBorrower($project);
                            $mailerManager->sendFundFailedToLender($project);
                        }

                        $now          = new \DateTime();
                        $slackManager = $this->getContainer()->get('unilend.service.slack_manager');
                        $message      = $slackManager->getProjectName($project) . ' - Cloturé le ' . $now->format('d/m/Y à H:i') . ' (' . $loan->getNbPreteurs($project->id_project) . ' prêteurs - ' . str_replace('.', ',', round($project->getAverageInterestRate(), 2)) . '%)';
                        $slackManager->sendMessage($message);

                        $mailerManager->sendProjectFinishedToStaff($project);
                    }
                } catch (\Exception $exception) {
                    $logger->critical('An exception occurred during funding of project ' . $project->id_project . ' with message: ' . $exception->getMessage(), [
                        'method' => __METHOD__,
                        'file'   => $exception->getFile(),
                        'line'   => $exception->getLine()
                    ]);
                }
            }
        }

        if ($hasProjectFinished) {
            /** @var MemcacheCachePool $cachePool */
            $cachePool = $this->getContainer()->get('memcache.default');
            $cachePool->deleteItem(CacheKeys::LIST_PROJECTS);
        }
    }
}
