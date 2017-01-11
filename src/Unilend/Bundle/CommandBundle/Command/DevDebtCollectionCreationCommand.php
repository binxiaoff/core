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
            ->setDescription('Retake the Altares result for the given projects')
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
                $this->repayment($output);
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
        $commission    = $input->getOption('commission');
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        $clientRepo    = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients');
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
        if (false === filter_var($commission, FILTER_VALIDATE_INT) || $commission <= 0) {
            $output->writeln('Invalid commission');
            return;
        }

        $reception->setTypeRemb(Receptions::REPAYMENT_TYPE_RECOVERY)
                  ->setStatusBo(Receptions::STATUS_MANUALLY_ASSIGNED)
                  ->setIdClient($client)
                  ->setIdProject($project);
        $borrower  = $clientRepo->getWalletByType($client->getIdClient(), WalletType::BORROWER);
        $collector = $clientRepo->getWalletByType($clientCollector->getIdClient(), WalletType::DEBT_COLLECTOR);
        $this->getContainer()->get('unilend.service.operation_manager')->provisionCollection($collector, $borrower, $reception, $commission);

        $entityManager->flush();
    }

    private function repayment(InputInterface $input, OutputInterface $output)
    {
        $entityManager    = $this->getContainer()->get('doctrine.orm.entity_manager');
        $operationManager = $this->getContainer()->get('unilend.service.operation_manager');
        $projectRepo      = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects');
        $walletRepo       = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet');
        $clientRepo       = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients');

        $projectId  = $input->getOption('project-id');
        $commission = $input->getOption('commission');

        $project = $projectRepo->find($projectId);
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
        $borrower        = $clientRepo->getWalletByType($project->getIdCompany()->getIdClientOwner(), WalletType::BORROWER);
        $collector       = $clientRepo->getWalletByType($clientCollector->getIdClient(), WalletType::DEBT_COLLECTOR);
        if (null === $clientCollector) {
            $output->writeln('Borrower with client id : '. $project->getIdCompany()->getIdClientOwner() .' not found.');
            return;
        }
        if (null === $collector) {
            $output->writeln('Collector with client hash : 2f9f590e-d689-11e6-b3d7-005056a378e2 not found.');
            return;
        }

        if ($fundReleaseDate < $dateOfChange) {
            if (false === filter_var($commission, FILTER_VALIDATE_INT) || $commission <= 0) {
                $output->writeln('Invalid commission');
                return;
            }
            $operationManager->payCollectionCommissionByBorrower($borrower, $collector, $commission);
        }

        //Encode: UTF-8, new line : LF
        $fileName = $this->getContainer()->getParameter('path.protected') . 'import/' . 'recouvrement.csv';
        if (false === file_exists($fileName)) {
            $output->writeln($this->getContainer()->getParameter('path.protected') . 'import/' . 'recouvrement.csv not found');
            return;
        }
        if (false === ($rHandle = fopen($fileName, 'r'))) {
            $output->writeln($this->getContainer()->getParameter('path.protected') . 'import/' . 'recouvrement.csv cannot be opened');
            return;
        }
        while (($aRow = fgetcsv($rHandle, 0, ';')) !== false) {
            $clientId  = $aRow[0];
            $amount    = str_replace(',', '.', $aRow[1]);
            $lender    = $walletRepo->findOneBy(['idClient' => $clientId]);
            if ($lender) {
                $operationManager->repaymentCollection($lender, $project, $amount);
                if ($fundReleaseDate >= $dateOfChange && false === empty($aRow[2])) {
                    $commissionLender = str_replace(',', '.', $aRow[2]);
                    $operationManager->payCollectionCommissionByLender($lender, $collector, $commissionLender);
                }
            }
        }
        fclose($rHandle);
    }
}
