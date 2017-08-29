<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Echeanciers;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRepaymentTask;

class AutomaticLenderRepaymentCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('lender:repayment')
            ->setDescription('generates repayments for projects with automatic repayment process and sends invoice to the borrower');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entityManager                = $this->getContainer()->get('doctrine.orm.entity_manager');
        $projectRepaymentManager      = $this->getContainer()->get('unilend.service_repayment.project_repayment_manager');
        $projectEarlyRepaymentManager = $this->getContainer()->get('unilend.service_repayment.project_early_repayment_manager');
        $slackManager                 = $this->getContainer()->get('unilend.service.slack_manager');
        $logger                       = $this->getContainer()->get('monolog.logger.console');
        $stopWatch                    = $this->getContainer()->get('debug.stopwatch');

        $repaymentScheduleRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Echeanciers');

        $repaymentDate = new \DateTime();
        /** @var ProjectRepaymentTask[] $projectRepaymentTask */
        $projectRepaymentTask = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectRepaymentTask')->getProjectsToRepay($repaymentDate, 1);

        foreach ($projectRepaymentTask as $task) {
            try {
                $stopWatch->start('autoRepayment');
                $project = $task->getIdProject();
                switch ($task->getType()) {
                    case ProjectRepaymentTask::TYPE_REGULAR:
                    case ProjectRepaymentTask::TYPE_LATE:
                        $taskLog = $projectRepaymentManager->repay($task);
                        break;
                    case ProjectRepaymentTask::TYPE_EARLY:
                        $taskLog = $projectEarlyRepaymentManager->repay($task);
                        break;
                    default:
                        continue 2;
                }

                $stopWatchEvent = $stopWatch->stop('autoRepayment');

                if ($taskLog) {
                    switch ($task->getType()) {
                        case ProjectRepaymentTask::TYPE_REGULAR:
                        case ProjectRepaymentTask::TYPE_LATE:
                            $message = $slackManager->getProjectName($project)
                                . ' - Remboursement effectué en '
                                . round($stopWatchEvent->getDuration() / 1000, 1) . ' secondes (' . $taskLog->getRepaymentNb() . ' prêts, échéance #' . $task->getSequence() . ').';
                            break;
                        case ProjectRepaymentTask::TYPE_EARLY:
                            $message = $slackManager->getProjectName($project)
                                . ' - Remboursement anticipé effectué en '
                                . round($stopWatchEvent->getDuration() / 1000, 1) . ' secondes (' . $taskLog->getRepaymentNb() . ' prêts.';
                            break;
                        default:
                            continue 2;
                    }
                } else {
                    $message = $slackManager->getProjectName($project) .
                        ' - Remboursement non effectué. Veuille contacter l\'équipe technique pour en savoir plus.';
                }
                $slackManager->sendMessage($message);
            } catch (\Exception $exception) {
                $logger->error('Errors occur during the automatic repayment command. Error message : ' . $exception->getMessage(), ['Method' => __METHOD__]);
                continue;
            }
        }
    }
}
