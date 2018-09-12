<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\{Input\InputInterface, Input\InputOption, Output\OutputInterface};
use Unilend\Bundle\CoreBusinessBundle\Entity\{ClientAddress, CompanyAddress, Loans, Pays, ProjectsStatus};
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;

class GenerateLoanContractCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('lender:loan_contract')
            ->setDescription('Generates loan contract pdf document')
            ->setHelp(<<<EOF
The <info>lender:loan_contract</info> command generates the loan contract pdf document for the lenders.
<info>php bin/console lender:loan_contract</info>
EOF
            )
            ->addOption('limit-loans', 'l', InputOption::VALUE_REQUIRED, 'Number of loans to process');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $sRootDir = $this->getContainer()->getParameter('kernel.root_dir');

        require_once $sRootDir . '/../core/command.class.php';
        require_once $sRootDir . '/../core/controller.class.php';
        require_once $sRootDir . '/../apps/default/bootstrap.php';
        require_once $sRootDir . '/../apps/default/controllers/pdf.php';

        /** @var EntityManagerSimulator $entityManagerSimulator */
        $entityManagerSimulator = $this->getContainer()->get('unilend.service.entity_manager');
        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        /** @var LoggerInterface $logger */
        $logger = $this->getContainer()->get('monolog.logger.console');

        /** @var \loans $loan */
        $loan = $entityManagerSimulator->getRepository('loans');
        /** @var \projects $project */
        $project = $entityManagerSimulator->getRepository('projects');

        $status = [
            ProjectsStatus::REMBOURSEMENT,
            ProjectsStatus::REMBOURSE,
            ProjectsStatus::REMBOURSEMENT_ANTICIPE,
            ProjectsStatus::PROBLEME,
            ProjectsStatus::LOSS
        ];

        $projects = $project->selectProjectsByStatus($status, '', [], '', '', false);

        if (count($projects) > 0) {
            $limit = $input->getOption('limit-loans');
            $limit = $limit ? $limit : 100;

            $loans = $loan->select('status = ' . Loans::STATUS_ACCEPTED .' AND fichier_declarationContratPret IS NULL AND id_project IN (' . implode(', ', array_column($projects, 'id_project')) . ')', 'id_loan ASC', 0, $limit);

            if (count($loans) > 0) {
                foreach ($loans as $loanArray) {
                    $loan->get($loanArray['id_loan'], 'id_loan');
                    $project->get($loan->id_project, 'id_project');
                    $borrowerCompany = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->find($project->id_company);
                    $wallet          = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->find($loan->id_lender);

                    if (null === $borrowerCompany->getIdAddress()) {
                        throw new \Exception('Borrower of loan ' . $loan->id_loan . ' has no main address');
                    }

                    if ($wallet->getIdClient()->isNaturalPerson()) {
                        /** @var ClientAddress $validatedLenderAddress */
                        $validatedLenderAddress = $wallet->getIdClient()->getIdAddress();
                    } else {
                        $lenderCompany = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findOneBy(['idClientOwner' => $wallet->getIdClient()]);
                        /** @var CompanyAddress $validatedLenderAddress */
                        $validatedLenderAddress = $lenderCompany->getIdAddress();
                    }

                    if (null === $validatedLenderAddress) {
                        $logger->error('Clienter lender ' . $wallet->getIdClient()->getIdClient() . ' has no validated main address. His contract can not been generated. ', [
                            'class'      => __CLASS__,
                            'line'       => __LINE__,
                            'id_client'  => $wallet->getIdClient()->getIdClient(),
                            'id_company' => isset($lenderCompany) ? $lenderCompany->getIdCompany() : 'lender is natural person'
                        ]);
                        continue;
                    }

                    if ($validatedLenderAddress->getIdCountry()->getIdPays() !== Pays::COUNTRY_FRANCE) {
                        $lenderCode = '99';
                    } else {
                        $lenderCode = substr(trim($validatedLenderAddress->getZip()), 0, 2);
                    }

                    $basePath     = $sRootDir . '/../protected/pdf/cerfa/2062/';
                    $borrowerPath = $basePath . substr($loan->added, 0, 4) . '/' . substr(trim($borrowerCompany->getIdAddress()->getZip()), 0, 2) . '/emprunteurs/' . $project->slug . '/';
                    $lenderPath   = $basePath . substr($loan->added, 0, 4) . '/' . $lenderCode . '/preteurs/' . $project->slug . '/';
                    $fileName     = $borrowerCompany->getSiren() . '-' . $wallet->getIdClient()->getIdClient() . '-' . $loan->id_loan . '.pdf';

                    if (false === is_dir($borrowerPath)) {
                        mkdir($borrowerPath, 0775, true);
                    }

                    if (false === is_dir($lenderPath)) {
                        mkdir($lenderPath, 0775, true);
                    }

                    $_SERVER['REQUEST_URI'] = '';

                    $command    = new \Command('pdf', 'declarationContratPret_html', [], 'fr');
                    $controller = new \pdfController($command, 'default');
                    $controller->setContainer($this->getContainer());
                    $controller->initialize();

                    try {
                        $controller->_declarationContratPret_html($loan->id_loan);
                        $controller->WritePdf($borrowerPath . $fileName, 'dec_pret');

                        copy($borrowerPath . $fileName, $lenderPath . $fileName);
                    } catch (\Exception $exception) {
                        $output->writeln('Could not generate the loan contract PDF (loan ' . $loan->id_loan . ' - project ' . $loan->id_project . ') - Message: ' . $exception->getMessage() . ' - File: ' . $exception->getFile() . ' - Line: ' . $exception->getLine());
                        $logger->error(
                            'Could not generate the loan contract PDF (loan ' . $loan->id_loan . ' - project ' . $loan->id_project . ') - Message: ' . $exception->getMessage() . ' - File: ' . $exception->getFile() . ' - Line: ' . $exception->getLine(),
                            ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $loan->id_project, 'id_loan' => $loan->id_loan]
                        );
                        continue;
                    }

                    $loan->fichier_declarationContratPret = str_replace($basePath, '', $borrowerPath) . $fileName;
                    $loan->update();

                    $output->writeln('Loan contract PDF generated (loan ' . $loan->id_loan . ')');
                    $logger->info(
                        'Loan contract PDF generated (loan ' . $loan->id_loan . ')',
                        ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $loan->id_project, 'id_loan' => $loan->id_loan]
                    );
                }
            }
        }
    }
}
