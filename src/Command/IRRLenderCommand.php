<?php

namespace Unilend\Command;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Entity\{CompanyStatusHistory, LenderStatisticQueue, Projects, ProjectsStatusHistory, Wallet};
use Unilend\Repository\WalletRepository;
use Unilend\Service\IRRManager;
use Unilend\CacheKeys;

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

        $cachePool  = $this->getContainer()->get('cache.app');
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
        foreach ($entityManager->getRepository(LenderStatisticQueue::class)->findBy([], ['added' => 'DESC'], $amountOfLenderAccounts) as $queueEntry) {
            $wallet = $queueEntry->getIdWallet();
            $irrManager->addIRRLender($wallet);
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
        $walletRepository = $entityManager->getRepository(Wallet::class);
        foreach ($walletRepository->getLendersWalletsWithLatePaymentsForIRR() as $lender) {
            $this->addLenderToStatisticQueue($lender);
        }
        $entityManager->flush();

        $projectStatusHistoryRepository = $entityManager->getRepository(ProjectsStatusHistory::class);
        $companyStatusHistoryRepository = $entityManager->getRepository(CompanyStatusHistory::class);
        $yesterday                      = new \DateTime('NOW - 1 day');
        $projectStatusChanges           = $projectStatusHistoryRepository->getProjectStatusChangesOnDate($yesterday, IRRManager::PROJECT_STATUS_TRIGGERING_CHANGE);
        $companyStatusChanges           = $companyStatusHistoryRepository->getCompanyStatusChangesOnDate($yesterday, IRRManager::COMPANY_STATUS_TRIGGERING_CHANGE);

        /** @var \projects $project */
        $project = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('projects');
        foreach ($this->getProjects($projectStatusChanges, $companyStatusChanges) as $projectStatusChange) {
            foreach ($project->getLoansAndLendersForProject($projectStatusChange->getIdProject()) as $lender) {
                $this->addLenderToStatisticQueue($lender);
                $entityManager->flush();
            }
        }

        $cachePool  = $this->getContainer()->get('cache.app');
        $cachedItem = $cachePool->getItem(CacheKeys::LENDER_STAT_QUEUE_UPDATED);
        $cachedItem->set(true)->expiresAfter(CacheKeys::LONG_TIME);
        $cachePool->save($cachedItem);
    }

    /**
     * @param int|Wallet $lender
     */
    private function addLenderToStatisticQueue($lender)
    {
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        $lenderWallet  = null;

        if ($lender instanceof Wallet) {
            $lenderWallet = $lender;
        }

        if (is_array($lender) && array_key_exists('id_lender', $lender)) {
           $lenderWallet = $entityManager->getRepository(Wallet::class)->find($lender['id_lender']);
           if (null === $lenderWallet) {
               /** @var Wallet $lenderWallet */
               $lenderWallet = $entityManager->getRepository(Wallet::class)->findOneBy(['id' => $lender['id_lender']]);
            }
        }

        if (null !== $lenderWallet && null === $entityManager->getRepository(LenderStatisticQueue::class)->findOneBy(['idWallet' => $lenderWallet->getId()])) {
            $lenderInQueue = new LenderStatisticQueue();
            $lenderInQueue->setIdWallet($lenderWallet);
            $entityManager->persist($lenderInQueue);
        }
    }

    /**
     * @param ProjectsStatusHistory[] $projectStatusHistory
     * @param CompanyStatusHistory[] $companyStatusHistory
     *
     * @return Projects[]
     */
    private function getProjects(array $projectStatusHistory, array $companyStatusHistory)
    {
        $entityManager      = $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        $projectsRepository = $entityManager->getRepository(Projects::class);

        $projects = [];

        foreach ($projectStatusHistory as $statusHistory) {
            $projects[$statusHistory->getIdProject()] = $projectsRepository->find($statusHistory->getIdProject());
        }
        foreach ($companyStatusHistory as $statusHistory) {
            /** @var Projects[] $companyProjects */
            $companyProjects = $projectsRepository->findFundedButNotRepaidProjectsByCompany($statusHistory->getIdCompany());
            foreach ($companyProjects as $project) {
                $projects[$project->getIdProject()] = $project;
            }
        }

        return $projects;
    }
}
