<?php
namespace Unilend\Bundle\CommandBundle\Command;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\core\Loader;

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

        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('unilend.service.entity_manager');
        /** @var LoggerInterface $logger */
        $logger = $this->getContainer()->get('monolog.logger.console');

        /** @var \loans $loan */
        $loan = $entityManager->getRepository('loans');
        /** @var \projects $project */
        $project = $entityManager->getRepository('projects');
        /** load for class constants */
        $entityManager->getRepository('projects_status');

        $status = array(
            \projects_status::REMBOURSEMENT,
            \projects_status::REMBOURSE,
            \projects_status::PROBLEME,
            \projects_status::RECOUVREMENT,
            \projects_status::DEFAUT,
            \projects_status::REMBOURSEMENT_ANTICIPE,
            \projects_status::PROBLEME_J_X,
            \projects_status::PROCEDURE_SAUVEGARDE,
            \projects_status::REDRESSEMENT_JUDICIAIRE,
            \projects_status::LIQUIDATION_JUDICIAIRE
        );

        $projects = $project->selectProjectsByStatus($status, '', [], '', '', false);

        if (count($projects) > 0) {
            $limit = $input->getOption('limit-loans');
            $limit = $limit ? $limit : 100;

            $loans = $loan->select('status = 0 AND fichier_declarationContratPret = "" AND id_project IN (' . implode(', ', array_column($projects, 'id_project')) . ')', 'id_loan ASC', 0, $limit);

            if (count($loans) > 0) {
                /** @var \companies $borrowerCompany */
                $borrowerCompany = $entityManager->getRepository('companies');
                /** @var \clients $client */
                $client = $entityManager->getRepository('clients');
                /** @var \clients_adresses $clientAddress */
                $clientAddress = $entityManager->getRepository('clients_adresses');
                /** @var \lenders_accounts $lender */
                $lender = $entityManager->getRepository('lenders_accounts');
                /** @var \companies $lenderCompany */
                $lenderCompany = $entityManager->getRepository('companies');

                foreach ($loans as $loanArray) {
                    $loan->get($loanArray['id_loan'], 'id_loan');
                    $project->get($loan->id_project, 'id_project');
                    $lender->get($loan->id_lender, 'id_lender_account');
                    $client->get($lender->id_client_owner, 'id_client');
                    $borrowerCompany->get($project->id_company, 'id_company');

                    if (in_array($client->type, array(Clients::TYPE_PERSON, Clients::TYPE_PERSON_FOREIGNER))) {
                        $clientAddress->get($client->id_client, 'id_client');

                        if ($clientAddress->id_pays > 1) {
                            $lenderCode = '99';
                        } else {
                            $lenderCode = substr(trim($clientAddress->cp), 0, 2);
                        }
                    } else {
                        $lenderCompany->get($lender->id_company_owner, 'id_company');
                        $lenderCode = substr(trim($lenderCompany->zip), 0, 2);
                    }

                    $basePath     = $sRootDir . '/../protected/pdf/cerfa/2062/';
                    $borrowerPath = $basePath . substr($loan->added, 0, 4) . '/' . substr(trim($borrowerCompany->zip), 0, 2) . '/emprunteurs/' . $project->slug . '/';
                    $lenderPath   = $basePath . substr($loan->added, 0, 4) . '/' . $lenderCode . '/preteurs/' . $project->slug . '/';
                    $fileName     = $borrowerCompany->siren . '-' . $lender->id_client_owner . '-' . $loan->id_loan . '.pdf';

                    if (false === is_dir($borrowerPath)) {
                        mkdir($borrowerPath, 0775, true);
                    }

                    if (false === is_dir($lenderPath)) {
                        mkdir($lenderPath, 0775, true);
                    }

                    $_SERVER['REQUEST_URI'] = '';

                    $command    = new \Command('pdf', 'declarationContratPret_html', array(), 'fr');
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
                            array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $loan->id_project, 'id_loan' => $loan->id_loan)
                        );
                        continue;
                    }

                    $loan->fichier_declarationContratPret = str_replace($basePath, '', $borrowerPath) . $fileName;
                    $loan->update();

                    $output->writeln('Loan contract PDF generated (loan ' . $loan->id_loan . ')');
                    $logger->info(
                        'Loan contract PDF generated (loan ' . $loan->id_loan . ')',
                        array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $loan->id_project, 'id_loan' => $loan->id_loan)
                    );
                }
            }
        }
    }
}
