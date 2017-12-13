<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;

class QueriesLenderRevenueCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('unilend:feeds_out:ifu_revenue:generate')
            ->setDescription('Generate the lenders revenue information for those who are the beneficiaries in a given year')
            ->addOption('year', null, InputOption::VALUE_REQUIRED, 'Optional. Define the year to export in format YYYY')
            ->setHelp(<<<EOF
The <info>unilend:feeds_out:ifu_revenue:generate</info> command generate a csv which contains the lenders revenue information for those who are the beneficiaries in a given year.
Usage <info>bin/console unilend:feeds_out:ifu_revenue:generate [-year=2017]</info>
The <info>year</info> is optional. By default, it generates the file of the last year if we are on January, February or March, otherwise it generates the file for the current year.
EOF
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $ifuManager = $this->getContainer()->get('unilend.service.ifu_manager');

        $year = $input->getOption('year');
        if (empty($year)) {
            $year = $ifuManager->getYear();
        }

        $yesterday = new \DateTime('yesterday');

        $filePath          = $ifuManager->getStorageRootPath();
        $filename          = 'requete_revenus_' . date('Ymd') . '.csv';
        $yesterdayFilename = 'requete_revenus_' . $yesterday->format('Ymd') . '.csv';

        $file          = $filePath . DIRECTORY_SEPARATOR . $filename;
        $yesterdayFile = $filePath . DIRECTORY_SEPARATOR . $yesterdayFilename;

        if (file_exists($yesterdayFile)) {
            unlink($yesterdayFile);
        }
        if (file_exists($file)) {
            unlink($file);
        }

        /** @var \PHPExcel $csvFile */
        $csvFile     = new \PHPExcel();
        $activeSheet = $csvFile->setActiveSheetIndex(0);
        $row         = 1;

        $activeSheet->setCellValueByColumnAndRow(0, $row, 'Code Entreprise');
        $activeSheet->setCellValueByColumnAndRow(1, $row, 'CodeBénéficiaire');
        $activeSheet->setCellValueByColumnAndRow(2, $row, 'CodeV');
        $activeSheet->setCellValueByColumnAndRow(3, $row, 'Date');
        $activeSheet->setCellValueByColumnAndRow(4, $row, 'Montant');
        $activeSheet->setCellValueByColumnAndRow(5, $row, 'Monnaie');
        $row++;

        $operationRepository  = $this->getContainer()->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Operation');
        $walletsWithMovements = $this->getContainer()->get('unilend.service.ifu_manager')->getWallets($year);

        /** @var Wallet $wallet */
        foreach ($walletsWithMovements as $wallet) {
            $sumLoans = $operationRepository->sumDebitOperationsByTypeAndYear($wallet, [OperationType::LENDER_LOAN], null, $year);
            if ($sumLoans > 0) {
                $this->addCommonCellValues($activeSheet, $row, $year, $wallet);
                $activeSheet->setCellValueByColumnAndRow(2, $row, '117');
                $activeSheet->setCellValueByColumnAndRow(4, $row, round($sumLoans));
                $row += 1;
            }

            $grossInterest               = $operationRepository->sumCreditOperationsByTypeAndYear($wallet, [OperationType::GROSS_INTEREST_REPAYMENT], null, $year);
            $grossInterestRegularization = $operationRepository->sumDebitOperationsByTypeAndYear($wallet, [OperationType::GROSS_INTEREST_REPAYMENT_REGULARIZATION], null, $year);
            $grossInterest               = round(bcsub($grossInterest, $grossInterestRegularization, 4));
            if ($grossInterest > 0) {
                $this->addCommonCellValues($activeSheet, $row, $year, $wallet);
                $activeSheet->setCellValueByColumnAndRow(2, $row, '53');
                $activeSheet->setCellValueByColumnAndRow(4, $row, $grossInterest);
                $row += 1;
            }

            $deductedAtSource               = $operationRepository->sumDebitOperationsByTypeAndYear($wallet, [OperationType::TAX_FR_RETENUES_A_LA_SOURCE], null, $year);
            $deductedAtSourceRegularization = $operationRepository->sumCreditOperationsByTypeAndYear($wallet, [OperationType::TAX_FR_RETENUES_A_LA_SOURCE_REGULARIZATION], null, $year);
            $deductedAtSource               = round(bcsub($deductedAtSource, $deductedAtSourceRegularization, 4));
            if ($deductedAtSource > 0) {
                $this->addCommonCellValues($activeSheet, $row, $year, $wallet);
                $activeSheet->setCellValueByColumnAndRow(2, $row, '2');
                $activeSheet->setCellValueByColumnAndRow(4, $row, $deductedAtSource);
                $row += 1;
            }

            $statutoryContributions               = $operationRepository->sumDebitOperationsByTypeAndYear($wallet, [OperationType::TAX_FR_PRELEVEMENTS_OBLIGATOIRES], null, $year);
            $statutoryContributionsRegularization = $operationRepository->sumCreditOperationsByTypeAndYear($wallet, [OperationType::TAX_FR_PRELEVEMENTS_OBLIGATOIRES_REGULARIZATION], null, $year);
            $statutoryContributions               = round(bcsub($statutoryContributions, $statutoryContributionsRegularization, 4));
            if ($statutoryContributions > 0) {
                $this->addCommonCellValues($activeSheet, $row, $year, $wallet);
                $activeSheet->setCellValueByColumnAndRow(2, $row, '54');
                $activeSheet->setCellValueByColumnAndRow(4, $row, $statutoryContributions);
                $row += 1;
            }

            $capitalRepayments               = $operationRepository->sumCreditOperationsByTypeAndYear($wallet, [OperationType::CAPITAL_REPAYMENT], null, $year);
            $capitalRepaymentsRegularization = $operationRepository->sumDebitOperationsByTypeAndYear($wallet, [OperationType::CAPITAL_REPAYMENT_REGULARIZATION], null, $year);
            $capitalRepayments               = round(bcsub($capitalRepayments, $capitalRepaymentsRegularization, 4));
            if ($capitalRepayments > 0) {
                $this->addCommonCellValues($activeSheet, $row, $year, $wallet);
                $activeSheet->setCellValueByColumnAndRow(2, $row, '118');
                $activeSheet->setCellValueByColumnAndRow(4, $row, $capitalRepayments);
                $row += 1;
            }

            $interestWhileInFrance            = $operationRepository->getGrossInterestPaymentsInFrance($wallet, $year);
            $regularizedInterestWhileInFrance = $operationRepository->getRegularizedGrossInterestPaymentsInFrance($wallet, $year);
            $interestWhileInFrance            = round(bcsub($interestWhileInFrance, $regularizedInterestWhileInFrance, 4));
            if ($interestWhileInFrance > 0) {
                $this->addCommonCellValues($activeSheet, $row, $year, $wallet);
                $activeSheet->setCellValueByColumnAndRow(2, $row, '66');
                $activeSheet->setCellValueByColumnAndRow(4, $row, $interestWhileInFrance);
                $row += 1;
            }

            if (in_array($wallet->getIdClient()->getType(), [Clients::TYPE_PERSON, Clients::TYPE_PERSON_FOREIGNER])) {
                $netInterestWhileInEEA            = $operationRepository->sumNetInterestRepaymentsNotInEeaExceptFrance($wallet, $year);
                $regularizedNetInterestWhileInEEA = $operationRepository->sumRegularizedNetInterestRepaymentsNotInEeaExceptFrance($wallet, $year);
                $netInterestWhileInEEA            = round(bcsub($netInterestWhileInEEA, $regularizedNetInterestWhileInEEA, 4));
                if ($netInterestWhileInEEA > 0) {
                    $this->addCommonCellValues($activeSheet, $row, $year, $wallet);
                    $activeSheet->setCellValueByColumnAndRow(2, $row, '81');
                    $activeSheet->setCellValueByColumnAndRow(4, $row, $netInterestWhileInEEA);
                    $row += 1;
                }
            }

            if (in_array($wallet->getIdClient()->getType(), [Clients::TYPE_PERSON, Clients::TYPE_PERSON_FOREIGNER])) {
                $capitalWhileInEEA            = $operationRepository->sumRegularizedCapitalRepaymentsInEeaExceptFrance($wallet, $year);
                $regularizedCapitalWhileInEEA = $operationRepository->sumCapitalRepaymentsInEeaExceptFrance($wallet, $year);
                $capitalWhileInEEA            = round(bcsub($capitalWhileInEEA, $regularizedCapitalWhileInEEA, 4));
                if ($capitalWhileInEEA > 0) {
                    $this->addCommonCellValues($activeSheet, $row, $year, $wallet);
                    $activeSheet->setCellValueByColumnAndRow(2, $row, '82');
                    $activeSheet->setCellValueByColumnAndRow(4, $row, $capitalWhileInEEA);
                    $row += 1;
                }
            }
        }
        /** @var \PHPExcel_Writer_CSV $writer */
        $writer = \PHPExcel_IOFactory::createWriter($csvFile, 'CSV');
        $writer->setUseBOM(true);
        $writer->setDelimiter(';');
        $writer->save(str_replace(__FILE__, $file, __FILE__));
    }

    /**
     * @param \PHPExcel_Worksheet $activeSheet
     * @param int                 $row
     * @param int                 $year
     * @param Wallet              $wallet
     */
    private function addCommonCellValues(\PHPExcel_Worksheet $activeSheet, $row, $year, Wallet $wallet)
    {
        $commonValues = [
            'CodeEntreprise' => 1, //official code of SFPMEI
            'Date'           => '31/12/' . $year,
            'Monnaie'        => 'EUR',
        ];

        $activeSheet->setCellValueByColumnAndRow(0, $row, $commonValues['CodeEntreprise']);
        $activeSheet->setCellValueByColumnAndRow(1, $row, $wallet->getWireTransferPattern());
        $activeSheet->setCellValueByColumnAndRow(3, $row, $commonValues['Date']);
        $activeSheet->setCellValueByColumnAndRow(5, $row, $commonValues['Monnaie']);
    }
}
