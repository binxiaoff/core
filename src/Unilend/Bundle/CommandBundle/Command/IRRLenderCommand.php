<?php
namespace Unilend\Bundle\CommandBundle\Command;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
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
        /** @var IRRManager $irrManager */
        $irrManager = $this->getContainer()->get('unilend.service.irr_manager');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\LenderManager $lenderManager */
        $lenderManager  = $this->getContainer()->get('unilend.service.lender_manager');
        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('unilend.service.entity_manager');
        /** @var \lenders_account_stats $lenderAccountsStats */
        $lenderAccountsStats = $entityManager->getRepository('lenders_account_stats');
        /** @var /lenders_accounts_stats_queue $lenderAccountsStatsQueue */
        $lenderAccountsStatsQueue = $entityManager->getRepository('lenders_accounts_stats_queue');
        /** @var LoggerInterface $logger */
        $logger = $this->getContainer()->get('monolog.logger.console');
        $irrManager->setLogger($logger);

        $lendersWithLatePayments = $lenderAccountsStats->getLendersWithLatePaymentsForIRR();
        $lenderManager->addLendersToLendersAccountsStatQueue($lendersWithLatePayments);

        $amountOfLenderAccounts = $input->getArgument('quantity');

        if (empty($amountOfLenderAccounts) || false === is_numeric($amountOfLenderAccounts)) {
            $amountOfLenderAccounts = 100;
            $logger->error('Argument with amount of lender accounts for which IRR should be calculated is missing', ['class' => __CLASS__, 'function' => __FUNCTION__]);
        }

        $startTime              = microtime(true);
        $calculatedIRRs         = 0;

        foreach ($lenderAccountsStatsQueue->select(null, 'added DESC', null, $amountOfLenderAccounts) as $lender) {
            $irrManager->addIRRLender($lender['id_lender_account']);
            $lenderAccountsStatsQueue->delete($lender['id_lender_account'], 'id_lender_account');
            $calculatedIRRs += 1;
        }

        $logger->info('IRR calculation time for ' . $calculatedIRRs . ' lenders: ' . round((microtime(true) - $startTime) / 60, 2) . ' minutes', ['class' => __CLASS__, 'function' => __FUNCTION__]);
    }
}
