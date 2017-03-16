<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\Receptions;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;

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
            ->addOption('reception-id', null, InputOption::VALUE_OPTIONAL, 'Use with the action "provision". The reception id of the provision.')
            ->addOption('project-id', null, InputOption::VALUE_OPTIONAL, 'Use with the action "provision" and "repayment". The project id of the debt collection.')
            ->addOption('commission', null, InputOption::VALUE_OPTIONAL, 'Use with the action "provision" and "repayment". The commission for the debt collection.')
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
        $receptionId   = $input->getOption('reception-id');
        $projectId     = $input->getOption('project-id');
        $commission    = filter_var($input->getOption('commission'), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        $clientRepo    = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients');
        $walletRepo    = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet');
        $reception     = $entityManager->getRepository('UnilendCoreBusinessBundle:Receptions')->find($receptionId);
        if (null === $reception) {
            $output->writeln('Reception id: ' . $receptionId . ' not found.');
            return;
        }
        $project = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($projectId);
        if (null === $project) {
            $output->writeln('Project id: ' . $projectId . ' not found.');
            return;
        }
        $client = $clientRepo->find($project->getIdCompany()->getIdClientOwner());
        if (null === $client) {
            $output->writeln('Client id: ' . $project->getIdCompany()->getIdClientOwner() . ' not found.');
            return;
        }
        // We support only MCS as debt collector
        $clientCollector = $clientRepo->findOneBy(['hash' => '2f9f590e-d689-11e6-b3d7-005056a378e2']);
        if (null === $clientCollector) {
            $output->writeln('Client hash: 2f9f590e-d689-11e6-b3d7-005056a378e2 not found.');
            return;
        }
        if ($commission <= 0) {
            $output->writeln('Invalid commission');
            return;
        }
        $entityManager->getConnection()->beginTransaction();
        try {
            $reception->setTypeRemb(Receptions::REPAYMENT_TYPE_RECOVERY)
                      ->setStatusBo(Receptions::STATUS_MANUALLY_ASSIGNED)
                      ->setIdClient($client)
                      ->setIdProject($project)
                      ->setAssignmentDate(new \DateTime());
            $borrower  = $walletRepo->getWalletByType($client->getIdClient(), WalletType::BORROWER);
            $collector = $walletRepo->getWalletByType($clientCollector->getIdClient(), WalletType::DEBT_COLLECTOR);
            $this->getContainer()->get('unilend.service.operation_manager')->provisionCollection($collector, $borrower, $reception, $commission);

            $entityManager->flush();
            $entityManager->getConnection()->commit();
        } catch (\Exception $e) {
            $entityManager->getConnection()->rollBack();
            $output->writeln('Transaction rollbacked. Error : ' . $e->getMessage());
        }
    }

    private function repayment(InputInterface $input, OutputInterface $output)
    {
        $entityManager    = $this->getContainer()->get('doctrine.orm.entity_manager');
        $operationManager = $this->getContainer()->get('unilend.service.operation_manager');
        $projectRepo      = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects');
        $walletRepo       = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet');
        $clientRepo       = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients');
        $projectId        = $input->getOption('project-id');
        $commission       = filter_var($input->getOption('commission'), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $project          = $projectRepo->find($projectId);

        if (null === $project) {
            $output->writeln('Project id: ' . $projectId . ' not found.');
            return;
        }

        $statusHistory   = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsStatusHistory')->findStatusFirstOccurrence($projectId, ProjectsStatus::REMBOURSEMENT);
        $fundReleaseDate = $statusHistory->getAdded();
        $fundReleaseDate->setTime(0, 0, 0);
        $dateOfChange = new \DateTime(Projects::DEBT_COLLECTION_CONDITION_CHANGEMENT_DATE);
        $dateOfChange->setTime(0, 0, 0);

        $clientCollector = $clientRepo->findOneBy(['hash' => '2f9f590e-d689-11e6-b3d7-005056a378e2']);
        $borrower        = $walletRepo->getWalletByType($project->getIdCompany()->getIdClientOwner(), WalletType::BORROWER);
        $collector       = $walletRepo->getWalletByType($clientCollector->getIdClient(), WalletType::DEBT_COLLECTOR);

        if (null === $clientCollector) {
            $output->writeln('Borrower with client id : ' . $project->getIdCompany()->getIdClientOwner() . ' not found.');
            return;
        }
        if (null === $collector) {
            $output->writeln('Collector with client hash : 2f9f590e-d689-11e6-b3d7-005056a378e2 not found.');
            return;
        }
        $entityManager->getConnection()->beginTransaction();
        try {
            if ($fundReleaseDate >= $dateOfChange) {
                if ($commission <= 0) {
                    throw new \Exception('Invalid commission');
                }
                $operationManager->payCollectionCommissionByBorrower($borrower, $collector, $commission, $project);
            }

            //Encode: UTF-8, new line : LF
            $fileName = $this->getContainer()->getParameter('path.protected') . 'import/' . 'recouvrement.csv';
            if (false === file_exists($fileName)) {
                throw new \Exception($this->getContainer()->getParameter('path.protected') . 'import/' . 'recouvrement.csv not found');
            }
            if (false === ($rHandle = fopen($fileName, 'r'))) {
                throw new \Exception($this->getContainer()->getParameter('path.protected') . 'import/' . 'recouvrement.csv cannot be opened');
            }

            while (($aRow = fgetcsv($rHandle, 0, ';')) !== false) {
                $clientId = $aRow[0];
                $amount   = str_replace(',', '.', $aRow[1]);
                $lender   = $walletRepo->findOneBy(['idClient' => $clientId]);
                if ($lender) {
                    $commissionLender = 0;
                    if ($fundReleaseDate < $dateOfChange && false === empty($aRow[2])) {
                        $commissionLender = str_replace(',', '.', $aRow[2]);
                    }
                    $operationManager->repaymentCollection($lender, $project, $amount, $commissionLender);
                    if ($fundReleaseDate < $dateOfChange && false === empty($aRow[2])) {
                        $operationManager->payCollectionCommissionByLender($lender, $collector, $commissionLender, $project);
                    }
                }
            }
            fclose($rHandle);
            $entityManager->getConnection()->commit();
        } catch (\Exception $e) {
            $entityManager->getConnection()->rollBack();
            $output->writeln('Transaction rollbacked. Error : ' . $e->getMessage());
        }
    }
}
