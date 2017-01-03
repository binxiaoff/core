<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
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
            ->addOption('project-id', null, InputOption::VALUE_OPTIONAL, 'Use with the action "provision". The project id of the debt collection.')
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
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
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
        $client = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($project->getIdCompany()->getIdClientOwner());
        if (null === $client) {
            $output->writeln('Client id: ' . $project->getIdCompany()->getIdClientOwner() . ' not found.');
            return;
        }
        $reception->setTypeRemb(Receptions::REPAYMENT_TYPE_RECOVERY)
                  ->setStatusBo(Receptions::STATUS_MANUALLY_ASSIGNED)
                  ->setIdClient($client)
                  ->setIdProject($project);
        $wallet = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->getWalletByType($client->getIdClient(), WalletType::BORROWER);
        $this->getContainer()->get('unilend.service.operation_manager')->provisionCollection($wallet, $reception);

        $entityManager->flush();
    }

    private function repayment(OutputInterface $output)
    {
        $entityManager    = $this->getContainer()->get('doctrine.orm.entity_manager');
        $operationManager = $this->getContainer()->get('unilend.service.operation_manager');
        $projectRepo      = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects');
        $walletRepo       = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet');

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
            $projectId = $aRow[1];
            $amount    = str_replace(',', '.', $aRow[2]);

            $wallet  = $walletRepo->findOneBy(['idClient' => $clientId]);
            $project = $projectRepo->find($projectId);
            if (null === $project) {
                $output->writeln('Project id: ' . $projectId . ' not found.');
                continue;
            }
            if ($wallet) {
                $operationManager->repaymentCollection($wallet, $amount, $project);
            }
        }
        fclose($rHandle);
    }
}
