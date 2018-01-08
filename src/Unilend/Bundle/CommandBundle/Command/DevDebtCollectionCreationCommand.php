<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRepaymentTask;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\Receptions;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Service\DebtCollectionMissionManager;

class DevDebtCollectionCreationCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('unilend:dev_tools:debt_collection:create')
            ->setDescription('Import manually the debt collection repayment')
            ->addArgument('action', InputArgument::REQUIRED, 'Which action do you want to take?')
            ->addOption('reception-id', null, InputOption::VALUE_REQUIRED, 'Use with the action "provision". The reception id of the provision.')
            ->addOption('project-id', null, InputOption::VALUE_REQUIRED, 'Use with the action "provision" and "repayment". The project id of the debt collection.')
            ->addOption('commission', null, InputOption::VALUE_REQUIRED, 'Use with the action "provision" and "repayment". The commission for the debt collection.')
            ->addOption('collector', null, InputOption::VALUE_REQUIRED, 'Use with the action "provision" and "repayment". The debt collector for the debt collection.')
            ->addOption('net-amount', null, null, 'Use with the action "provision". If net amount, generate operation to cancel debt collection provision.')
            ->setHelp(<<<EOF
The <info>unilend:dev_tools:debt_collection:create</info> create the transactions and operations for the debt collection.
<info>php bin/console unilend:dev_tools:debt_collection:create provision|repayment</info>
EOF
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $action = $input->getArgument('action');

        switch ($action) {
            case 'provision' :
                $this->provision($input, $output);
                break;
            case 'repayment' :
                $this->repayment($input, $output);
                break;
            default:
                $output->writeln('Invalid action.');
        }
        $output->writeln('done.');
    }

    private function provision(InputInterface $input, OutputInterface $output)
    {
        $receptionId      = $input->getOption('reception-id');
        $projectId        = $input->getOption('project-id');
        $commission       = filter_var($input->getOption('commission'), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $isNetAmount      = $input->getOption('net-amount');
        $entityManager    = $this->getContainer()->get('doctrine.orm.entity_manager');
        $walletRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet');
        $reception        = $entityManager->getRepository('UnilendCoreBusinessBundle:Receptions')->find($receptionId);
        if (null === $reception) {
            $output->writeln('Reception id: ' . $receptionId . ' not found.');
            return;
        }
        $project = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($projectId);
        if (null === $project) {
            $output->writeln('Project id: ' . $projectId . ' not found.');
            return;
        }
        $client = $project->getIdCompany()->getIdClientOwner();
        if (null === $client || empty($client->getIdClient())) {
            $output->writeln('Client not found for company ' . $project->getIdCompany()->getIdCompany());
            return;
        }
        $borrower = $walletRepository->getWalletByType($client, WalletType::BORROWER);
        if (null === $borrower) {
            $output->writeln('Borrower with client id : ' . $client->getIdClient() . ' not found.');
            return;
        }
        $clientCollector = $this->getCollector($input, $output);
        if (null === $clientCollector) {
            $output->writeln('Collector not found.');
            return;
        }
        $collector = $walletRepository->getWalletByType($clientCollector->getIdClient(), WalletType::DEBT_COLLECTOR);
        if (null === $collector) {
            $output->writeln('Collector\'s wallet not found.');
            return;
        }
        if ($commission <= 0) {
            $output->writeln('Invalid commission');
            return;
        }

        $entityManager->getConnection()->beginTransaction();
        try {
            $reception
                ->setTypeRemb(Receptions::REPAYMENT_TYPE_RECOVERY)
                ->setStatusBo(Receptions::STATUS_ASSIGNED_MANUAL)
                ->setIdClient($client)
                ->setIdProject($project)
                ->setAssignmentDate(new \DateTime());
            $this->getContainer()->get('unilend.service.operation_manager')->provisionBorrowerWallet($reception);
            $amount = round(bcdiv($reception->getMontant(), 100, 4), 2);
            if ($isNetAmount) {
                $this->getContainer()->get('unilend.service.operation_manager')->provisionCollection($collector, $borrower, $reception, $commission);
                // amount is always brut.
                $amount = round(bcadd($amount, $commission, 4), 2);
            }
            $user                 = $entityManager->getRepository('UnilendCoreBusinessBundle:Users')->find(Users::USER_ID_CRON);
            $projectRepaymentTask = new ProjectRepaymentTask();
            $projectRepaymentTask
                ->setIdProject($project)
                ->setCapital($amount)
                ->setInterest(0)
                ->setCommissionUnilend(0)
                ->setType(ProjectRepaymentTask::TYPE_CLOSE_OUT_NETTING)
                ->setStatus(ProjectRepaymentTask::STATUS_PENDING)
                ->setRepayAt(new \DateTime())
                ->setIdUserCreation($user)
                ->setIdWireTransferIn($reception);

            $entityManager->persist($projectRepaymentTask);

            $entityManager->flush();
            $entityManager->getConnection()->commit();
        } catch (\Exception $e) {
            $entityManager->getConnection()->rollBack();
            $output->writeln('Transaction rollbacked. Error : ' . $e->getMessage());
        }
    }

    private function repayment(InputInterface $input, OutputInterface $output)
    {
        $entityManager               = $this->getContainer()->get('doctrine.orm.entity_manager');
        $operationManager            = $this->getContainer()->get('unilend.service.operation_manager');
        $projectRepaymentTaskManager = $this->getContainer()->get('unilend.service_repayment.project_repayment_task_manager');
        $walletRepository            = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet');
        $projectId                   = $input->getOption('project-id');
        $commission                  = filter_var($input->getOption('commission'), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

        $project = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($projectId);
        if (null === $project) {
            $output->writeln('Project id: ' . $projectId . ' not found.');
            return;
        }
        $borrower = $walletRepository->getWalletByType($project->getIdCompany()->getIdClientOwner(), WalletType::BORROWER);
        if (null === $borrower) {
            $output->writeln('Borrower with client id : ' . $project->getIdCompany()->getIdClientOwner()->getIdClient() . ' not found.');
            return;
        }
        $clientCollector = $this->getCollector($input, $output);
        if (null === $clientCollector) {
            $output->writeln('Collector not found.');
            return;
        }
        $collector = $walletRepository->getWalletByType($clientCollector->getIdClient(), WalletType::DEBT_COLLECTOR);
        if (null === $collector) {
            $output->writeln('Collector\'s wallet not found.');
            return;
        }

        $protectedDir = $this->getContainer()->getParameter('path.protected');
        //Encode: UTF-8, new line : LF
        $fileName = $protectedDir . 'import/' . 'recouvrement.csv';
        if (false === file_exists($fileName)) {
            $output->writeln($protectedDir . 'import/' . 'recouvrement.csv not found');
            return;
        }
        if (false === ($rHandle = fopen($fileName, 'r'))) {
            $output->writeln($protectedDir . 'import/' . 'recouvrement.csv cannot be opened');
            return;
        }
        /** @var ProjectRepaymentTask $projectRepaymentTask */
        $projectRepaymentTask = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectRepaymentTask')->findOneBy([
            'idProject' => $project,
            'type'      => ProjectRepaymentTask::TYPE_CLOSE_OUT_NETTING,
            'status'    => ProjectRepaymentTask::STATUS_PENDING
        ]);
        if (null === $projectRepaymentTask) {
            $output->writeln('Repayment task not found.');
            return;
        }

        $statusHistory   = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsStatusHistory')->findStatusFirstOccurrence($projectId, ProjectsStatus::REMBOURSEMENT);
        $fundReleaseDate = $statusHistory->getAdded();
        $fundReleaseDate->setTime(0, 0, 0);
        $dateOfChange = new \DateTime(DebtCollectionMissionManager::DEBT_COLLECTION_CONDITION_CHANGE_DATE);
        $dateOfChange->setTime(0, 0, 0);

        $entityManager->getConnection()->beginTransaction();
        try {
            $repaidAmount = 0;
            $repaidNb     = 0;

            $projectRepaymentTaskLog = $projectRepaymentTaskManager->start($projectRepaymentTask);

            if ($fundReleaseDate >= $dateOfChange) {
                if ($commission <= 0) {
                    throw new \Exception('Invalid commission');
                }
                $operationManager->payDebtCollectionFee($borrower, $collector, $commission, $project);

                $amountToRepay = round(bcsub($projectRepaymentTask, $commission, 4), 2);
                $projectRepaymentTask->setCapital($amountToRepay);
            }

            while (($aRow = fgetcsv($rHandle, 0, ';')) !== false) {
                $clientId = $aRow[0];
                $amount   = str_replace(',', '.', $aRow[1]);
                $lender   = $walletRepository->findOneBy(['idClient' => $clientId]);
                if ($lender instanceof Wallet) {
                    $commissionLender = 0;
                    if ($fundReleaseDate < $dateOfChange && false === empty($aRow[2])) {
                        $commissionLender = str_replace(',', '.', $aRow[2]);
                    }
                    $operationManager->repaymentCollection($lender, $project, $amount, $projectRepaymentTaskLog);
                    if ($fundReleaseDate < $dateOfChange && false === empty($aRow[2])) {
                        $operationManager->payDebtCollectionFee($lender, $collector, $commissionLender, [$project, $projectRepaymentTaskLog]);
                    }
                }
                $repaidAmount = round(bcadd($repaidAmount, $amount, 4), 2);
                $repaidNb++;
            }

            $projectRepaymentTask->setStatus(ProjectRepaymentTask::STATUS_REPAID);

            $projectRepaymentTaskManager->end($projectRepaymentTaskLog, $repaidAmount, $repaidNb);

            $entityManager->flush();
            $entityManager->getConnection()->commit();
        } catch (\Exception $e) {
            $entityManager->getConnection()->rollBack();
            $output->writeln('Transaction rollbacked. Error : ' . $e->getMessage());
        }
        fclose($rHandle);
    }

    /**
     * We support only MCS or Progéris as debt collector
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return null|Clients
     */
    private function getCollector(InputInterface $input, OutputInterface $output)
    {
        $clientRepository = $this->getContainer()->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Clients');
        $collectorName    = $input->getOption('collector');
        switch (strtoupper($collectorName)) {
            case 'MCS':
                $clientHash = DebtCollectionMissionManager::CLIENT_HASH_MCS;
                break;
            case 'PROGERIS':
                $clientHash = DebtCollectionMissionManager::CLIENT_HASH_PROGERIS;
                break;
            default:
                $output->writeln('Debt collector : ' . $collectorName . 'is not supported');
                return null;
        }
        return $clientRepository->findOneBy(['hash' => $clientHash]);
    }
}
