<?php
namespace Unilend\apps\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\core\Console\Command;
use Unilend\Service\SalesforceManager;

class SalesforceExtractionCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('salesforce:extraction')
            ->setDescription('Extraction the data for sending to SalesForce via Data Loader')
            ->addArgument('extraction_type', InputArgument::REQUIRED, 'Which type of data do you like to extract?')
            ->setHelp(<<<EOF
The <info>salesforce:extraction</info> command creates the extractions for sending to SalesForce.
<info>php bin/console salesforce:extraction companies|borrowers|projects|lenders</info>
EOF
            );
    }

    protected function execute(InputInterface $oInput, OutputInterface $oOutput)
    {
        $sType = $oInput->getArgument('extraction_type');
        /** @var SalesforceManager $oSalesForceManager */
        $oSalesForceManager = $this->getContainer()->get('unilend.service.salesforce');
        switch ($sType) {
            case 'companies':
                $oSalesForceManager->extractCompanies();
                $oOutput->writeln('companies extracted');
                break;
            case 'borrowers':
                $oSalesForceManager->extractBorrowers();
                $oOutput->writeln('borrowers extracted');
                break;
            case 'projects':
                $oSalesForceManager->extractProjects();
                $oOutput->writeln('projects extracted');
                break;
            case 'lenders':
                $oSalesForceManager->extractLenders();
                $oOutput->writeln('lenders extracted');
                break;
        }
    }
}