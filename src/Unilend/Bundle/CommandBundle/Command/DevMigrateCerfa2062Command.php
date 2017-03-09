<?php
namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class DevMigrateCerfa2062Command extends ContainerAwareCommand
{
    /** @var OutputInterface */
    private $output;
    /** @var EntityManager */
    private $entityManager;
    /** @var \loans */
    private $borrowerCompany;
    /** @var \clients */
    private $client;
    /** @var \clients_adresses */
    private $clientAddress;
    /** @var \lenders_accounts */
    private $lender;
    /** @var \companies */
    private $lenderCompany;
    /** @var \loans */
    private $loan;
    /** @var \projects */
    private $project;

    protected function configure()
    {
        $this
            ->setName('dev:migrate:cerfa_2062')
            ->setDescription('Migrate CERFA 2062 files to new directories');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output          = $output;
        $this->entityManager   = $this->getContainer()->get('unilend.service.entity_manager');
        $this->borrowerCompany = $this->entityManager->getRepository('companies');
        $this->client          = $this->entityManager->getRepository('clients');
        $this->clientAddress   = $this->entityManager->getRepository('clients_adresses');
        $this->lender          = $this->entityManager->getRepository('lenders_accounts');
        $this->lenderCompany   = $this->entityManager->getRepository('companies');
        $this->loan            = $this->entityManager->getRepository('loans');
        $this->project         = $this->entityManager->getRepository('projects');

        $oldStatementsPath     = $this->getContainer()->get('kernel')->getRootDir() . '/../protected/declarationContratPret/';
        $newStatementsBasePath = $this->getContainer()->get('kernel')->getRootDir() . '/../protected/pdf/cerfa/2062/';

        foreach (array_diff(scandir($oldStatementsPath), array('.', '..')) as $yearDirectory) {
            foreach (array_diff(scandir($oldStatementsPath . $yearDirectory), array('.', '..')) as $subYearPath) {
                if (is_dir($oldStatementsPath . $yearDirectory . '/' . $subYearPath)) {
                    foreach (array_diff(scandir($oldStatementsPath . $yearDirectory . '/' . $subYearPath), ['.', '..']) as $path) {
                        $this->moveStatement($oldStatementsPath . $yearDirectory . '/' . $subYearPath . '/' . $path, $newStatementsBasePath);
                    }
                } else {
                    $this->moveStatement($oldStatementsPath . $yearDirectory . '/' . $subYearPath, $newStatementsBasePath);
                }
            }
        }
    }

    private function moveStatement($path, $newStatementsBasePath)
    {
        if (1 === preg_match('#.*/Unilend_declarationContratPret_(?<loan>[0-9]+)\.pdf$#', $path, $matches)) {
            if (
                $this->loan->get($matches['loan'])
                && $this->project->get($this->loan->id_project, 'id_project')
                && $this->lender->get($this->loan->id_lender, 'id_lender_account')
                && $this->client->get($this->lender->id_client_owner, 'id_client')
                && $this->borrowerCompany->get($this->project->id_company, 'id_company')
            ) {
                if (in_array($this->client->type, [Clients::TYPE_PERSON, Clients::TYPE_PERSON_FOREIGNER])) {
                    $this->clientAddress->get($this->client->id_client, 'id_client');

                    if ($this->clientAddress->id_pays > 1) {
                        $lenderCode = '99';
                    } else {
                        $lenderCode = substr(trim($this->clientAddress->cp), 0, 2);
                    }
                } else {
                    $this->lenderCompany->get($this->lender->id_company_owner, 'id_company');
                    $lenderCode = substr(trim($this->lenderCompany->zip), 0, 2);
                }

                $borrowerPath = $newStatementsBasePath . substr($this->loan->added, 0, 4) . '/' . substr(trim($this->borrowerCompany->zip), 0, 2) . '/emprunteurs/' . $this->project->slug . '/';
                $lenderPath   = $newStatementsBasePath . substr($this->loan->added, 0, 4) . '/' . $lenderCode . '/preteurs/' . $this->project->slug . '/';
                $fileName     = $this->borrowerCompany->siren . '-' . $this->lender->id_client_owner . '-' . $this->loan->id_loan . '.pdf';

                if (false === is_dir($borrowerPath)) {
                    mkdir($borrowerPath, 0755, true);
                }

                if (false === is_dir($lenderPath)) {
                    mkdir($lenderPath, 0755, true);
                }

                rename($path, $borrowerPath . $fileName);
                copy($borrowerPath . $fileName, $lenderPath . $fileName);

                $this->loan->fichier_declarationContratPret = str_replace($newStatementsBasePath, '', $borrowerPath) . $fileName;
                $this->loan->update();

                $this->output->writeln('Loan ' . $matches['loan'] . ' moved');
            } else {
                $this->output->writeln('<error>Loan could not be loaded: ' . $matches['loan'] . '</error>');
            }
        } else {
            $this->output->writeln('<error>File name does not match pattern: ' . $path . '</error>');
        }
    }
}
