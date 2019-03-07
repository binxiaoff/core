<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Doctrine\ORM\ORMException;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\{Input\InputInterface, Input\InputOption, Output\OutputInterface};
use Unilend\Bundle\CoreBusinessBundle\Entity\{ClientAddress, CompanyAddress, Loans, Pays, ProjectsStatus};

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

        $logger        = $this->getContainer()->get('monolog.logger.console');
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        $projects      = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->findBy([
            'status' => [
                ProjectsStatus::REMBOURSEMENT,
                ProjectsStatus::REMBOURSE,
                ProjectsStatus::REMBOURSEMENT_ANTICIPE,
                ProjectsStatus::PROBLEME,
                ProjectsStatus::LOSS
            ]
        ]);

        if (count($projects) > 0) {
            $limit = $input->getOption('limit-loans');
            $limit = $limit ? $limit : 100;

            $companyRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies');
            $loanRepository    = $entityManager->getRepository('UnilendCoreBusinessBundle:Loans');
            $loans             = $loanRepository->findBy(
                ['status' => Loans::STATUS_ACCEPTED, 'fichierDeclarationcontratpret' => null, 'idProject' => $projects],
                ['idLoan' => 'ASC'],
                $limit
            );

            foreach ($loans as $loan) {
                $project         = $loan->getProject();
                $borrowerCompany = $project->getIdCompany();
                $lender          = $loan->getWallet()->getIdClient();

                if (null === $borrowerCompany->getIdAddress()) {
                    throw new \Exception('Borrower of loan ' . $loan->getIdLoan() . ' has no main address');
                }

                if ($lender->isNaturalPerson()) {
                    /** @var ClientAddress $validatedLenderAddress */
                    $validatedLenderAddress = $lender->getIdAddress();
                } else {
                    /** @var CompanyAddress $validatedLenderAddress */
                    $lenderCompany          = $companyRepository->findOneBy(['idClientOwner' => $lender]);
                    $validatedLenderAddress = $lenderCompany->getIdAddress();
                }

                if (null === $validatedLenderAddress) {
                    $logger->error('Lender ' . $lender->getIdClient() . ' has no validated main address. His contract can not been generated. ', [
                        'class'      => __CLASS__,
                        'line'       => __LINE__,
                        'id_client'  => $lender->getIdClient(),
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
                $borrowerPath = $basePath . $loan->getAdded()->format('Y') . '/' . substr(trim($borrowerCompany->getIdAddress()->getZip()), 0, 2) . '/emprunteurs/' . $project->getSlug() . '/';
                $lenderPath   = $basePath . $loan->getAdded()->format('Y') . '/' . $lenderCode . '/preteurs/' . $project->getSlug() . '/';
                $fileName     = $borrowerCompany->getSiren() . '-' . $lender->getIdClient() . '-' . $loan->getIdLoan() . '.pdf';

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
                    $controller->_declarationContratPret_html($loan->getIdLoan());
                    $controller->WritePdf($borrowerPath . $fileName, 'dec_pret');

                    copy($borrowerPath . $fileName, $lenderPath . $fileName);
                } catch (\Exception $exception) {
                    $output->writeln('Could not generate the loan contract PDF (loan ' . $loan->getIdLoan() . ' - project ' . $project->getIdProject() . '). Message: ' . $exception->getMessage() . ' - File: ' . $exception->getFile() . ' - Line: ' . $exception->getLine());
                    $logger->error('Could not generate the loan contract PDF (loan ' . $loan->getIdLoan() . ' - project ' . $project->getIdProject() . '). Message: ' . $exception->getMessage(), [
                        'id_loan'    => $loan->getIdLoan(),
                        'id_project' => $project->getIdProject(),
                        'class'      => __CLASS__,
                        'function'   => __FUNCTION__,
                        'file'       => $exception->getFile(),
                        'line'       => $exception->getLine()
                    ]);
                    continue;
                }

                $loan->setFichierDeclarationcontratpret(str_replace($basePath, '', $borrowerPath) . $fileName);

                try {
                    $entityManager->flush($loan);
                } catch (ORMException $exception) {
                    $output->writeln('Could not update loan contract PDF (loan ' . $loan->getIdLoan() . ' - project ' . $project->getIdProject() . '). Message: ' . $exception->getMessage() . ' - File: ' . $exception->getFile() . ' - Line: ' . $exception->getLine());
                    $logger->error('Could not update loan contract PDF (loan ' . $loan->getIdLoan() . ' - project ' . $project->getIdProject() . '). Message: ' . $exception->getMessage(), [
                        'id_loan'    => $loan->getIdLoan(),
                        'id_project' => $project->getIdProject(),
                        'class'      => __CLASS__,
                        'function'   => __FUNCTION__,
                        'file'       => $exception->getFile(),
                        'line'       => $exception->getLine()
                    ]);
                    continue;
                }

                $output->writeln('Loan contract PDF generated (loan ' . $loan->getIdLoan() . ')');
                $logger->info('Loan contract PDF generated (loan ' . $loan->getIdLoan() . ')', [
                    'id_loan'    => $loan->getIdLoan(),
                    'id_project' => $project->getIdProject(),
                    'class'      => __CLASS__,
                    'function'   => __FUNCTION__
                ]);
            }
        }
    }
}
