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
        $entityManager           = $this->getContainer()->get('doctrine.orm.entity_manager');
        $projectRepaymentManager = $this->getContainer()->get('unilend.service.project_repayment_manager');
        $slackManager            = $this->getContainer()->get('unilend.service.slack_manager');
        $logger                  = $this->getContainer()->get('monolog.logger.console');
        $stopWatch               = $this->getContainer()->get('debug.stopwatch');

        $repaymentScheduleRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Echeanciers');

        $repaymentDate = new \DateTime();
        /** @var ProjectRepaymentTask[] $projectRepaymentTask */
        $projectRepaymentTask = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectRepaymentTask')->getProjectsToRepay($repaymentDate, 1);

        foreach ($projectRepaymentTask as $task) {
            try {
                $stopWatch->start('autoRepayment');
                $project = $task->getIdProject();
                if ($task->getSequence()) {
                    /** @var Echeanciers $repaymentSchedule */
                    $repaymentSchedule = $repaymentScheduleRepository->findOneBy(['idProject' => $project, 'ordre' => $task->getSequence()]);
                    if ($repaymentSchedule->getDateEcheance()->setTime(0, 0, 0) > $repaymentDate) {
                        continue;
                    }
                }
                $taskLog        = $projectRepaymentManager->repay($task);
                $stopWatchEvent = $stopWatch->stop('autoRepayment');

                if ($taskLog) {
                    $message = $slackManager->getProjectName($project) .
                        ' - Remboursement effectué en '
                        . round($stopWatchEvent->getDuration() / 1000, 1) . ' secondes (' . $taskLog->getRepaymentNb() . ' prêts, échéance #' . $task->getSequence() . ').';
                } else {
                    $message = $slackManager->getProjectName($project) .
                        ' - Remboursement non effectué. Veuille voir avec l\'équipe technique pour en savoir plus.';
                }
                $slackManager->sendMessage($message);
            } catch (\Exception $exception) {
                $logger->error('Errors occur during the automatic repayment command. Error message : ' . $exception->getMessage(), ['Method' => __METHOD__]);
                continue;
            }
        }
    }
}
