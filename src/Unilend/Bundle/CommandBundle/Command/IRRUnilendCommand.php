<?php
namespace Unilend\Bundle\CommandBundle\Command;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bridge\Doctrine\DBAL\Connection;
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
        /** @var IRRManager $oIRRManager */
        $oIRRManager = $this->getContainer()->get('unilend.service.irr_manager');
        /** @var LoggerInterface $logger */
        $logger = $this->getContainer()->get('monolog.logger.console');
        $oIRRManager->setLogger($logger);

        $sYesterday = date('Y-m-d', strtotime('-1 day'));

        if ($oIRRManager->IRRUnilendNeedsToBeRecalculated($sYesterday)) {
            try {
                $oIRRManager->updateIRRUnilend();
            } catch (\Exception $e) {
                $logger->error('Could not update Unilend IRR. Message: ' . $e->getMessage(), array('class' => __CLASS__, 'function' => __FUNCTION__));
            }
        }
    }
}
