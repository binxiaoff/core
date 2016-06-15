<?php
namespace Unilend\Bundle\CommandBundle\Command;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Unilend\Bridge\Doctrine\DBAL\Connection;
use Unilend\Bundle\CoreBusinessBundle\Service\IRRManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class IRRLenderCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('irr:lender')
            ->setDescription('Calculate the IRR for Lenders with changes in their portfolio')
            ->addArgument('quantity', InputArgument::REQUIRED, 'For how many lenders per iteration do you want to recalculate the IRR?')
            ->setHelp(<<<EOF
The <info>IRR:lender</info> command calculates the IRR for lenders in the lenders_accounts_stats_queue.
The preferred amount are 100 lenders.
EOF
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var IRRManager $oIRRManager */
        $oIRRManager = $this->getContainer()->get('unilend.service.irr_manager');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\LenderManager $oLenderManager */
        $oLenderManager  = $this->getContainer()->get('unilend.service.lender_manager');
        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('unilend.service.entity_manager');
        /** @var \lenders_account_stats $oLendersAccountsStats */
        $oLendersAccountsStats = $entityManager->getRepository('lenders_account_stats');
        /** @var /lenders_accounts_stats_queue $oLendersAccountsStatsQueue */
        $oLendersAccountsStatsQueue = $entityManager->getRepository('lenders_accounts_stats_queue');
        /** @var LoggerInterface $logger */
        $logger = $this->getContainer()->get('monolog.logger.console');
        $oIRRManager->setLogger($logger);

        $this->fillProjectLastStatusMaterialized();

        $aLendersWithLatePayments = $oLendersAccountsStats->getLendersWithLatePaymentsForIRRUsingProjectsLastStatusHistoryMaterialized();
        $oLenderManager->addLendersToLendersAccountsStatQueue($aLendersWithLatePayments);

        $iAmountOfLenderAccounts = $input->getArgument('quantity');
        $fTimeStart              = microtime(true);
        $aIRRsCalculated         = 0;

        foreach ($oLendersAccountsStatsQueue->select(null, 'added DESC', null, $iAmountOfLenderAccounts) as $aLender) {
            try {
                $oIRRManager->addIRRLender($aLender);
                $oLendersAccountsStatsQueue->delete($aLender['id_lender_account'], 'id_lender_account');
                $aIRRsCalculated += 1;

            } catch (\Exception $eIRRException) {
                $logger->error('Could not calculate TRI for lender id_lender_account=' . $aLender['id_lender_account'] . ' Exception message: '  . $eIRRException->getMessage(), array('class' => __CLASS__, 'function' => __FUNCTION__));
            }
        }
        $logger->info('Calculation time for ' . $aIRRsCalculated . ' lenders : ' . round((microtime(true) - $fTimeStart)/60, 2) . ' minutes', array('class' => __CLASS__, 'function' => __FUNCTION__));

        $this->emptyProjectLastStatusMaterialized();
    }

    private function fillProjectLastStatusMaterialized()
    {
        /** @var Connection $bdd */
        $bdd = $this->getContainer()->get('doctrine.dbal.default_connection');

        $bdd->query('TRUNCATE projects_last_status_history_materialized');
        $bdd->query('INSERT INTO projects_last_status_history_materialized
                                    SELECT MAX(id_project_status_history) AS id_project_status_history, id_project
                                    FROM projects_status_history
                                    GROUP BY id_project');
        $bdd->query('OPTIMIZE TABLE projects_last_status_history_materialized');
    }

    private function emptyProjectLastStatusMaterialized()
    {
        /** @var Connection $bdd */
        $bdd = $this->getContainer()->get('doctrine.dbal.default_connection');
        $bdd->query('TRUNCATE projects_last_status_history_materialized');
    }
}
