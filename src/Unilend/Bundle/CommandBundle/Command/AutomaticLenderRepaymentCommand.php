<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsRemb;


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

        /** @var ProjectsRemb[] $projectsToRepay */
        $projectsToRepay = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsRemb')->getProjectsToRepay(new \DateTime(), 1);

        foreach ($projectsToRepay as $projectRepayment) {
            try {
                $stopWatch->start('autoRepayment');
                $project        = $projectRepayment->getIdProject();
                $repaymentNb    = $projectRepaymentManager->repay($project, $projectRepayment->getOrdre());
                $stopWatchEvent = $stopWatch->stop('autoRepayment');

                $message = $slackManager->getProjectName($project) .
                    ' - Remboursement automatique effectué en '
                    . round($stopWatchEvent->getDuration() / 1000, 1) . ' secondes (' . $repaymentNb . ' prêts, échéance #' . $projectRepayment->getOrdre() . ').';
                $slackManager->sendMessage($message);
            } catch (\Exception $exception) {
                $logger->error('Errors occur during the automatic repayment command. Error message : ' . $exception->getMessage(), ['Method' => __METHOD__]);
                continue;
            }
        }
    }
}
