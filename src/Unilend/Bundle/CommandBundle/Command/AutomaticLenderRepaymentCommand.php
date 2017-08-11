<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRepaymentTask;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;


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

        /** @var ProjectRepaymentTask[] $projectsToRepay */
        $projectsToRepay = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectRepaymentTask')->getProjectsToRepay(new \DateTime(), 1);

        foreach ($projectsToRepay as $projectRepayment) {
            try {
                $stopWatch->start('autoRepayment');
                $project        = $projectRepayment->getIdProject();
                $taskLog        = $projectRepaymentManager->repay($projectRepayment, Users::USER_ID_CRON);
                $stopWatchEvent = $stopWatch->stop('autoRepayment');

                if ($taskLog) {
                    $message = $slackManager->getProjectName($project) .
                        ' - Remboursement effectué en '
                        . round($stopWatchEvent->getDuration() / 1000, 1) . ' secondes (' . $taskLog->getRepaymentNb() . ' prêts, échéance #' . $taskLog->getSequence() . ').';
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
