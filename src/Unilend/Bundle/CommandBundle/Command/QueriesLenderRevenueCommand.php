<?php


namespace Unilend\Bundle\CommandBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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
            ->setName('queries:lender_revenue')
            ->setDescription('Extract revenue information for all lenders in a given year')
            ->addArgument(
                'year',
                InputArgument::REQUIRED,
                'year to export'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $year              = $input->getArgument('year');
        $filePath          = $this->getContainer()->getParameter('path.protected') . '/' . 'requete_revenus' . date('Ymd') . '.csv';
        $yesterday         = new \DateTime('yesterday');
        $yesterdayFilePath = $this->getContainer()->getParameter('path.protected') . '/' . 'requete_revenus' . $yesterday->format('Ymd') . '.csv';

        if (file_exists($yesterdayFilePath)){
            unlink($yesterdayFilePath);
        }
        if (file_exists($filePath)){
            unlink($filePath);
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
        $activeSheet->setCellValueByColumnAndRow(6, $row, 'idClient');
        $row ++;

        $operationTypes       = [
            OperationType::LENDER_LOAN,
            OperationType::CAPITAL_REPAYMENT,
            OperationType::GROSS_INTEREST_REPAYMENT
        ];
        $operationRepository  = $this->getContainer()->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Operation');
        $walletRepository     = $this->getContainer()->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Wallet');
        $walletsWithMovements = $walletRepository->getLenderWalletsWithOperationsInYear($operationTypes, $year);

        /** @var Wallet $wallet */
        foreach ($walletsWithMovements as $wallet) {
            $sumLoans = $operationRepository->sumDebitOperationsByTypeAndYear($wallet, [OperationType::LENDER_LOAN], null, $year);
            if ($sumLoans > 0) {
                $this->addCommonCellValues($activeSheet, $row, $year, $wallet);
                $activeSheet->setCellValueByColumnAndRow(2, $row, '117');
                $activeSheet->setCellValueByColumnAndRow(4, $row, number_format($sumLoans, 2, ',', ''));
                $row += 1;
            }

            $grossInterest = $operationRepository->sumCreditOperationsByTypeAndYear($wallet, [OperationType::GROSS_INTEREST_REPAYMENT], null, $year);
            if ($grossInterest > 0) {
                $this->addCommonCellValues($activeSheet, $row, $year, $wallet);
                $activeSheet->setCellValueByColumnAndRow(2, $row, '53');
                $activeSheet->setCellValueByColumnAndRow(4, $row, number_format($grossInterest, 2, ',', ''));
                $row += 1;
            }

            $deductedAtSource = $operationRepository->sumDebitOperationsByTypeAndYear($wallet, [OperationType::TAX_FR_INCOME_TAX_DEDUCTED_AT_SOURCE], null, $year);
            if ($deductedAtSource > 0) {
                $this->addCommonCellValues($activeSheet, $row, $year, $wallet);
                $activeSheet->setCellValueByColumnAndRow(2, $row, '2');
                $activeSheet->setCellValueByColumnAndRow(4, $row, number_format($deductedAtSource, 2, ',', ''));
                $row += 1;
            }

            $statutoryContributions = $operationRepository->sumDebitOperationsByTypeAndYear($wallet, [OperationType::TAX_FR_STATUTORY_CONTRIBUTIONS], null, $year);
            if ($statutoryContributions > 0) {
                $this->addCommonCellValues($activeSheet, $row, $year, $wallet);
                $activeSheet->setCellValueByColumnAndRow(2, $row, '54');
                $activeSheet->setCellValueByColumnAndRow(4, $row, number_format($statutoryContributions, 2, ',', ''));
                $row += 1;
            }

            $capitalRepayments = $operationRepository->sumCreditOperationsByTypeAndYear($wallet, [OperationType::CAPITAL_REPAYMENT], null, $year);
            if ($capitalRepayments > 0) {
                $this->addCommonCellValues($activeSheet, $row, $year, $wallet);
                $activeSheet->setCellValueByColumnAndRow(2, $row, '118');
                $activeSheet->setCellValueByColumnAndRow(4, $row, number_format($capitalRepayments, 2, ',', ''));
                $row += 1;
            }

            $interestWhileInFrance = $operationRepository->getGrossInterestPaymentsInFrance($wallet, $year);
            if ($interestWhileInFrance  > 0) {
                $this->addCommonCellValues($activeSheet, $row, $year, $wallet);
                $activeSheet->setCellValueByColumnAndRow(2, $row, '66');
                $activeSheet->setCellValueByColumnAndRow(4, $row, number_format($interestWhileInFrance, 2, ',', ''));
                $row += 1;
            }

            $netInterestWhileInEEA = (in_array($wallet->getIdClient()->getType(), [Clients::TYPE_PERSON, Clients::TYPE_PERSON_FOREIGNER])) ? $operationRepository->sumNetInterestRepaymentsNotInEeaExceptFrance($wallet, $year) : 0;
            if ($netInterestWhileInEEA > 0) {
                $this->addCommonCellValues($activeSheet, $row, $year, $wallet);
                $activeSheet->setCellValueByColumnAndRow(2, $row, '81');
                $activeSheet->setCellValueByColumnAndRow(4, $row, number_format($netInterestWhileInEEA, 2, ',', ''));
                $row += 1;
            }

            $capitalWhileInEEA = (in_array($wallet->getIdClient()->getType(), [Clients::TYPE_PERSON, Clients::TYPE_PERSON_FOREIGNER])) ? $operationRepository->sumCapitalRepaymentsInEeaExceptFrance($wallet, $year) : 0;
            if ($capitalWhileInEEA > 0) {
                $this->addCommonCellValues($activeSheet, $row, $year, $wallet);
                $activeSheet->setCellValueByColumnAndRow(2, $row, '82');
                $activeSheet->setCellValueByColumnAndRow(4, $row, number_format($capitalWhileInEEA, 2, ',', ''));
                $row += 1;
            }

        }
        /** @var \PHPExcel_Writer_CSV $writer */
        $writer = \PHPExcel_IOFactory::createWriter($csvFile, 'CSV');
        $writer->setUseBOM(true);
        $writer->setDelimiter(';');
        $writer->save(str_replace(__FILE__, $filePath ,__FILE__));
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
            'Monnaie'        => 'EURO',
        ];

        $activeSheet->setCellValueByColumnAndRow(0, $row, $commonValues['CodeEntreprise']);
        $activeSheet->setCellValueByColumnAndRow(1, $row, $wallet->getWireTransferPattern());
        $activeSheet->setCellValueByColumnAndRow(3, $row, $commonValues['Date']);
        $activeSheet->setCellValueByColumnAndRow(5, $row, $commonValues['Monnaie']);
        $activeSheet->setCellValueByColumnAndRow(6, $row, $wallet->getIdClient()->getIdClient());
    }
}
