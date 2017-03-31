<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Unilend\Bundle\CoreBusinessBundle\Entity\LenderStatisticQueue;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Repository\LenderStatisticQueueRepository;
use Unilend\Bundle\CoreBusinessBundle\Repository\ProjectsStatusHistoryRepository;
use Unilend\Bundle\CoreBusinessBundle\Repository\WalletRepository;
use Unilend\Bundle\CoreBusinessBundle\Service\IRRManager;
use Unilend\librairies\CacheKeys;

class IRRLenderCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('irr:lender')
            ->setDescription('Calculate the IRR for Lenders with changes in their portfolio')
            ->addArgument('quantity', InputArgument::REQUIRED, 'For how many lenders per iteration do you want to recalculate the IRR?')
            ->setHelp(<<<EOF
The <info>IRR:lender</info> command calculates the IRR for lenders in the lender_statistic_queue.
The preferred amount are 100 lenders.
EOF
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var IRRManager $irrManager */
        $irrManager = $this->getContainer()->get('unilend.service.irr_manager');
        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        /** @var LenderStatisticQueueRepository $lenderStatisticQueueRepository */
        $lenderStatisticQueueRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:LenderStatisticQueue');

        $cachePool  = $this->getContainer()->get('memcache.default');
        $cachedItem = $cachePool->getItem(CacheKeys::LENDER_STAT_QUEUE_UPDATED);
        if (false === $cachedItem->isHit() ) {
            $this->addLendersToLenderStatisticQueue();
        }

        /** @var LoggerInterface $logger */
        $logger = $this->getContainer()->get('monolog.logger.console');
        $irrManager->setLogger($logger);

        $amountOfLenderAccounts = (int) $input->getArgument('quantity');

        if (empty($amountOfLenderAccounts) || false === is_numeric($amountOfLenderAccounts)) {
            $amountOfLenderAccounts = 100;
            $logger->error('Argument with amount of lender accounts for which IRR should be calculated is missing', ['class' => __CLASS__, 'function' => __FUNCTION__]);
        }

        $startTime              = microtime(true);
        $calculatedIRRs         = 0;

        /** @var LenderStatisticQueue $queueEntry */
        foreach ($lenderStatisticQueueRepository->getLenderFromQueue($amountOfLenderAccounts) as $queueEntry) {
            $wallet = $queueEntry->getIdWallet();
            $match  = $entityManager->getRepository('UnilendCoreBusinessBundle:AccountMatching')->findOneBy(['idWallet' => $wallet->getId()]);
            $irrManager->addIRRLender($wallet, $match->getIdLenderAccount()->getIdLenderAccount());
            $entityManager->remove($queueEntry);
            $entityManager->flush();
            $calculatedIRRs += 1;
        }

        $logger->info('IRR calculation time for ' . $calculatedIRRs . ' lenders: ' . round((microtime(true) - $startTime) / 60, 2) . ' minutes', ['class' => __CLASS__, 'function' => __FUNCTION__]);
    }


    private function addLendersToLenderStatisticQueue()
    {
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');

        /** @var WalletRepository $walletRepository */
        $walletRepository        = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet');
        foreach ($walletRepository->getLendersWalletsWithLatePaymentsForIRR() as $lender) {
            $this->addLenderToStatisticQueue($lender);
        }
        $entityManager->flush();

        /** @var ProjectsStatusHistoryRepository $projectStatusHistoryRepository */
        $projectStatusHistoryRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsStatusHistory');
        $yesterday                      = new \DateTime('NOW - 1 day');
        $projectStatusChanges           = $projectStatusHistoryRepository->getProjectStatusChangesOnDate($yesterday, \projects_status::$runningRepayment);

        /** @var \projects $project */
        $project = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('projects');
        if (false === empty($projectStatusChanges)) {
            foreach ($projectStatusChanges as $projectStatusChange) {
                foreach ($project->getLoansAndLendersForProject($projectStatusChange->getIdProject()) as $lender) {
                    $this->addLenderToStatisticQueue($lender);
                }
            }
        }
        $entityManager->flush();

        $cachePool  = $this->getContainer()->get('memcache.default');
        $cachedItem = $cachePool->getItem(CacheKeys::LENDER_STAT_QUEUE_UPDATED);
        $cachedItem->set(true)->expiresAfter(CacheKeys::LONG_TIME);
        $cachePool->save($cachedItem);
    }

    /**
     * @param $lender
     */
    private function addLenderToStatisticQueue($lender)
    {
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');

        if ($lender instanceof Wallet) {
            $lenderWallet = $lender;
        }

        if (is_array($lender) && array_key_exists('id_lender', $lender)) {
           $lenderWallet = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->find($lender['id_lender']);
           if (null === $lenderWallet) {
               $lenderWallet = $entityManager->getRepository('UnilendCoreBusinessBundle:AccountMatching')->findOneBy(['id_lender_account' => $lender['id_lender']]);
            }
        }

        if (null === $entityManager->getRepository('UnilendCoreBusinessBundle:LenderStatisticQueue')->findOneBy(['idWallet' => $lenderWallet->getId()])) {
            $lenderInQueue = new LenderStatisticQueue();
            $lenderInQueue->setIdWallet($lenderWallet);
            $entityManager->persist($lenderInQueue);
        }
    }
}
