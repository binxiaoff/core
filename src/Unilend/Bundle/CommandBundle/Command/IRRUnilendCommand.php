<?php
namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class IRRUnilendCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('irr:unilend')
            ->setDescription('Calculate the IRR of the whole platform');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $iRRManager = $this->getContainer()->get('unilend.service.irr_manager');
        $logger     = $this->getContainer()->get('monolog.logger.console');
        $iRRManager->setLogger($logger);

        $yesterday = new \DateTime('Yesterday');

        if ($iRRManager->IRRUnilendNeedsToBeRecalculated($yesterday)) {
            try {
                $iRRManager->addIRRUnilend();
                $iRRManager->addIRRForAllRiskPeriodCohort();
                $iRRManager->addOptimisticUnilendIRR();
                $iRRManager->addOptimisticUnilendIRRAllRiskPeriodCohort();
            } catch (\Exception $exception) {
                $logger->error('Could not update Unilend IRR. Message: ' . $exception->getMessage(), ['file' => $exception->getFile(), 'line' => $exception->getLine()]);
            }
        }
    }
}
