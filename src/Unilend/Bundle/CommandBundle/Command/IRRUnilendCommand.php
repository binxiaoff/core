<?php
namespace Unilend\Bundle\CommandBundle\Command;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\IRRManager;

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
        /** @var IRRManager $iRRManager */
        $iRRManager = $this->getContainer()->get('unilend.service.irr_manager');
        /** @var LoggerInterface $logger */
        $logger = $this->getContainer()->get('monolog.logger.console');
        $iRRManager->setLogger($logger);

        $yesterday = new \DateTime('Yesterday');

        if ($iRRManager->IRRUnilendNeedsToBeRecalculated($yesterday)) {
            try {
                $iRRManager->addIRRUnilend();
                $iRRManager->addIRRForAllRiskPeriodCohort();
            } catch (\Exception $e) {
                $logger->error('Could not update Unilend IRR. Message: ' . $e->getMessage(), array('class' => __CLASS__, 'function' => __FUNCTION__));
            }
        }
    }
}
