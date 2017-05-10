<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Virements;
use Unilend\Bundle\CoreBusinessBundle\Repository\OperationRepository;
use Unilend\core\Loader;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Psr\Log\LoggerInterface;

class FeedsDailyStateCommand extends ContainerAwareCommand
{
    const ROW_HEIGHT   = 20;
    const COLUMN_WIDTH = 16;

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('feeds:daily_state')
            ->setDescription('Extract daily fiscal state')
            ->addArgument(
                'day',
                InputArgument::OPTIONAL,
                'Day of the state to export (format: Y-m-d)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $requestedDate = \DateTime::createFromFormat('Y-m-d', $input->getArgument('day'));
        $firstDay      = new \DateTime('first day of ' . $requestedDate->format('Y-m'));

        /** @var \PHPExcel $document */
        $document = new \PHPExcel();
        $document->getDefaultStyle()->getFont()->setName('Arial');
        $document->getDefaultStyle()->getFont()->setSize(11);
        $activeSheet = $document->setActiveSheetIndex(0);
        $activeSheet->getDefaultColumnDimension()->setWidth(self::COLUMN_WIDTH);
        $activeSheet->getDefaultRowDimension()->setRowHeight(self::ROW_HEIGHT);

        $this->addHeaders($activeSheet, $requestedDate, 1);

        $maxCoordinates          = $activeSheet->getHighestRowAndColumn();
        $previousMonthBalanceRow = $maxCoordinates['row'];
        $firstDateRow            = $previousMonthBalanceRow + 1;
        $coordinatesDay         = $this->addDates($activeSheet, $firstDay, $requestedDate, $firstDateRow);

        $maxCoordinates = $activeSheet->getHighestRowAndColumn();
        $maxColumn      = \PHPExcel_Cell::columnIndexFromString($maxCoordinates['column']);
        $separationRow  = $maxCoordinates['row'] + 1;
        $activeSheet->mergeCellsByColumnAndRow(0, $separationRow, $maxColumn, $separationRow);
        $daySectionTotalRow = $separationRow + 1;
        $activeSheet->getStyle('A' . $daySectionTotalRow)->getFont()->setBold(true);
        $separationRow      = $daySectionTotalRow + 1;
        $activeSheet->mergeCellsByColumnAndRow(0, $separationRow, $maxColumn, $separationRow);
        $monthHeaderStart = $separationRow + 1;

        $this->addHeaders($activeSheet, $requestedDate, $monthHeaderStart);
        $maxCoordinates         = $activeSheet->getHighestRowAndColumn();
        $previousYearBalanceRow = $maxCoordinates['row'];
        $firstMonthRow          = $previousYearBalanceRow + 1;
        $coordinatesMonth = $this->addMonths($activeSheet, $requestedDate, $firstMonthRow);

        $maxCoordinates   = $activeSheet->getHighestRowAndColumn();
        $maxColumn        = \PHPExcel_Cell::columnIndexFromString($maxCoordinates['column']);
        $separationRow    = $maxCoordinates['row'] + 1;
        $activeSheet->mergeCellsByColumnAndRow(0, $separationRow, $maxColumn, $separationRow);
        $monthSectionTotalRow = $separationRow + 1;
        $activeSheet->getStyle('A' . $monthSectionTotalRow)->getFont()->setBold(true);

        $this->addGeneralStyleToWorksheet($activeSheet);

        $specificRows = [
            'previousMonth'    => $previousMonthBalanceRow,
            'previousYear'     => $previousYearBalanceRow,
            'firstDay'         => $firstDateRow,
            'firstMonth'       => $firstMonthRow,
            'totalDay'         => $daySectionTotalRow,
            'totalMonth'       => $monthSectionTotalRow,
            'coordinatesDay'   => $coordinatesDay,
            'coordinatesMonth' => $coordinatesMonth
        ];

        /* Start data part */

        $this->addMovementData($activeSheet, $firstDay, $requestedDate, $specificRows);
        $this->addWireTransferData($activeSheet, $firstDay, $requestedDate, $specificRows);


        $filePath = $this->getContainer()->getParameter('path.sftp') . 'sfpmei/emissions/etat_quotidien/Unilend_etat_' . $requestedDate->format('Ymd') . '.xlsx';
        /** @var \PHPExcel_Writer_CSV $writer */
        $writer = \PHPExcel_IOFactory::createWriter($document, 'Excel2007');
        $writer->save(str_replace(__FILE__, $filePath ,__FILE__));
    }


    /**
     * @param \PHPExcel_Worksheet $activeSheet
     * @param \DateTime           $firstDay
     * @param \DateTime           $requestedDate
     * @param array               $specificRows
     */
    private function addMovementData(\PHPExcel_Worksheet $activeSheet, \DateTime $firstDay, \DateTime $requestedDate, array $specificRows)
    {
        $entityManager       = $this->getContainer()->get('doctrine.orm.entity_manager');
        $operationRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Operation');

        $movements = array_merge([
            OperationType::BORROWER_PROVISION,
            OperationType::LENDER_PROVISION,
            OperationType::BORROWER_WITHDRAW,
            OperationType::BORROWER_COMMISSION,
            OperationType::LENDER_WITHDRAW,
            OperationType::UNILEND_PROMOTIONAL_OPERATION_PROVISION,
            OperationType::LENDER_LOAN,
            OperationType::CAPITAL_REPAYMENT,
            OperationType::GROSS_INTEREST_REPAYMENT,
            OperationType::UNILEND_PROMOTIONAL_OPERATION
        ], OperationType::TAX_TYPES_FR);

        $dailyMovements   = $operationRepository->sumMovementsForDailyState($firstDay, $requestedDate, $movements);
        $monthlyMovements = $operationRepository->sumMovementsForDailyStateByMonth($requestedDate->format('Y'), $movements);

        $this->addMovementsLine($activeSheet, $dailyMovements, $specificRows['firstDay'], $specificRows['totalDay']);
        $this->addMovementsLine($activeSheet, $monthlyMovements, $specificRows['firstMonth'], $specificRows['totalMonth']);
    }

    private function addWireTransferData(\PHPExcel_Worksheet $activeSheet, \DateTime $firstDay, \DateTime $requestedDate, array $specificRows)
    {
        $entityManager             = $this->getContainer()->get('doctrine.orm.entity_manager');
        $wireTransferOutRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Virements');
        $directDebitRepository     = $entityManager->getRepository('UnilendCoreBusinessBundle:Prelevements');
        $operationRepository       = $entityManager->getRepository('UnilendCoreBusinessBundle:Operation');

        $taxWithdrawTypes = [
            OperationType::TAX_FR_CRDS_WITHDRAW,
            OperationType::TAX_FR_CSG_WITHDRAW,
            OperationType::TAX_FR_ADDITIONAL_CONTRIBUTIONS_WITHDRAW,
            OperationType::TAX_FR_SOCIAL_DEDUCTIONS_WITHDRAW,
            OperationType::TAX_FR_SOLIDARITY_DEDUCTIONS_WITHDRAW,
            OperationType::TAX_FR_STATUTORY_CONTRIBUTIONS_WITHDRAW,
            OperationType::TAX_FR_INCOME_TAX_DEDUCTED_AT_SOURCE_WITHDRAW
        ];

        $wireTransfersDay = [
            'out'         => $wireTransferOutRepository->sumWireTransferOutByDay($firstDay, $requestedDate, Virements::STATUS_SENT),
            'unilend'     => $wireTransferOutRepository->sumWireTransferOutByDay($firstDay, $requestedDate, Virements::STATUS_SENT, Virements::TYPE_UNILEND),
            'taxes'       => $operationRepository->sumMovementsForDailyState($firstDay, $requestedDate, $taxWithdrawTypes),
            'directDebit' => $directDebitRepository->sumDirectDebitByDay($firstDay, $requestedDate)
        ];
        $this->addWireTransferLines($activeSheet, $wireTransfersDay, $specificRows['totalDay'], $specificRows['coordinatesDay']);

        $wireTransfersMonth = [
            'out'         => $wireTransferOutRepository->sumWireTransferOutByMonth($requestedDate->format('Y'), Virements::STATUS_SENT),
            'unilend'     => $wireTransferOutRepository->sumWireTransferOutByMonth($requestedDate->format('Y'), Virements::STATUS_SENT, Virements::TYPE_UNILEND),
            'taxes'       => $operationRepository->sumMovementsForDailyStateByMonth($requestedDate->format('Y'), $taxWithdrawTypes),
            'directDebit' => $directDebitRepository->sumDirectDebitByMonth($requestedDate->format('Y'))
        ];
        $this->addWireTransferLines($activeSheet, $wireTransfersMonth, $specificRows['totalMonth'], $specificRows['coordinatesMonth']);
    }

    /**
     * @param \PHPExcel_Worksheet $activeSheet
     * @param \DateTime           $date
     * @param int                 $startRow
     */
    private function addHeaders(\PHPExcel_Worksheet $activeSheet, \DateTime $date, $startRow)
    {
        $mainSectionRow       = $startRow + 1;
        $secondarySectionRow  = $mainSectionRow + 1;
        $previousBalanceRow   = $secondarySectionRow + 1;

        $activeSheet->mergeCells('A' . $startRow . ':AH' . $startRow)->setCellValue('A' . $startRow, 'UNILEND');
        $activeSheet->getStyle('A' . $startRow . ':AH' . $startRow)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $activeSheet->getStyle('A' . $startRow)->getFont()->setBold(true)->setSize(18)->setItalic(true);

        $activeSheet->getStyle('A' . $mainSectionRow . ':AH' . $mainSectionRow)->getFont()->setBold(true);
        $activeSheet->getStyle('A' . $mainSectionRow . ':AH' . $mainSectionRow)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $activeSheet->getRowDimension($mainSectionRow)->setRowHeight(self::ROW_HEIGHT * 2);
        $activeSheet->getRowDimension($secondarySectionRow)->setRowHeight(self::ROW_HEIGHT * 2);

        if (2 == $mainSectionRow) {
            $activeSheet->mergeCells('A' . $mainSectionRow . ':A' . $secondarySectionRow)->setCellValue('A' . $mainSectionRow, $date->format('d/m/Y'));
            $activeSheet->mergeCells('A' . $previousBalanceRow . ':P' . $previousBalanceRow)->setCellValue('A' . $previousBalanceRow, 'Début du mois');
        } else {
            $activeSheet->mergeCells('A' . $mainSectionRow . ':A' . $secondarySectionRow)->setCellValue('A' . $mainSectionRow, $date->format('Y'));
            $activeSheet->mergeCells('A' . $previousBalanceRow . ':P' . $previousBalanceRow)->setCellValue('A' . $previousBalanceRow, 'Début d\'année');
        }
        $activeSheet->mergeCells('B' . $mainSectionRow . ':D' . $mainSectionRow)->setCellValue('B' . $mainSectionRow, 'Chargements comptes prêteurs');
        $activeSheet->setCellValue('E' . $mainSectionRow, 'Chargement offres');
        $activeSheet->setCellValue('F' . $mainSectionRow, 'Echeances Emprunteur');
        $activeSheet->setCellValue('G' . $mainSectionRow, 'Octroi prêt');
        $activeSheet->setCellValue('H' . $mainSectionRow, 'Commission octroi prêt');
        $activeSheet->setCellValue('I' . $mainSectionRow, 'Commission restant dû');
        $activeSheet->mergeCells('J' . $mainSectionRow . ':P' . $mainSectionRow)->setCellValue('J' . $mainSectionRow, 'Retenues fiscales');
        $activeSheet->setCellValue('Q' . $mainSectionRow, 'Remboursement aux prêteurs');
        $activeSheet->setCellValue('R' . $mainSectionRow, '');
        $activeSheet->mergeCells('S' . $mainSectionRow . ':X' . $mainSectionRow)->setCellValue('S' . $mainSectionRow, 'Soldes');
        $activeSheet->mergeCells('Y' . $mainSectionRow . ':AD' . $mainSectionRow)->setCellValue('Y' . $mainSectionRow, 'Mouvements internes');
        $activeSheet->mergeCells('AE' . $mainSectionRow . ':AG' . $mainSectionRow)->setCellValue('AE' . $mainSectionRow, 'Virements');
        $activeSheet->setCellValue('AH' . $mainSectionRow, 'Prélèvements');
        $activeSheet->setCellValue('B' . $secondarySectionRow,'Carte bancaire');
        $activeSheet->setCellValue('C' . $secondarySectionRow,'Virement');
        $activeSheet->setCellValue('D' . $secondarySectionRow, 'Prélèvement');
        $activeSheet->setCellValue('E' . $secondarySectionRow, 'Virement');
        $activeSheet->setCellValue('F' . $secondarySectionRow, 'Virement');
        $activeSheet->setCellValue('G' . $secondarySectionRow, 'Prélèvement');
        $activeSheet->setCellValue('H' . $secondarySectionRow, 'Virement');
        $activeSheet->setCellValue('I' . $secondarySectionRow, 'Virement');
        $activeSheet->setCellValue('J' . $secondarySectionRow, 'Prélèvements obligatoires');
        $activeSheet->setCellValue('K' . $secondarySectionRow, 'Retenues à la source');
        $activeSheet->setCellValue('L' . $secondarySectionRow, 'CSG');
        $activeSheet->setCellValue('M' . $secondarySectionRow, 'Prélèvements sociaux');
        $activeSheet->setCellValue('N' . $secondarySectionRow, 'Contributions additionnelles');
        $activeSheet->setCellValue('O' . $secondarySectionRow, 'Prélèvements solidarité');
        $activeSheet->setCellValue('P' . $secondarySectionRow, 'CRDS');
        $activeSheet->setCellValue('Q' . $secondarySectionRow, 'Virement');
        $activeSheet->setCellValue('R' . $secondarySectionRow, 'Total mouvements');
        $activeSheet->setCellValue('S' . $secondarySectionRow, 'Solde théorique');
        $activeSheet->setCellValue('T' . $secondarySectionRow, 'Solde réel');
        $activeSheet->setCellValue('U' . $secondarySectionRow, 'Ecart global');
        $activeSheet->setCellValue('V' . $secondarySectionRow, 'Solde Promotions');
        $activeSheet->setCellValue('W' . $secondarySectionRow, 'Solde Unilend');
        $activeSheet->setCellValue('X' . $secondarySectionRow, 'Solde Admin. Fiscale');
        $activeSheet->setCellValue('Y' . $secondarySectionRow, 'Offre promo');
        $activeSheet->setCellValue('Z' . $secondarySectionRow, 'Octroi prêt');
        $activeSheet->setCellValue('AA' . $secondarySectionRow, 'Retour prêteur (Capital)');
        $activeSheet->setCellValue('AB' . $secondarySectionRow, 'Retour prêteur (Intêréts nets)');
        $activeSheet->setCellValue('AC' . $secondarySectionRow, 'Affectation Ech. Empr.');
        $activeSheet->setCellValue('AD' . $secondarySectionRow, 'Ecart fiscal');
        $activeSheet->setCellValue('AE' . $secondarySectionRow, 'Fichier virements');
        $activeSheet->setCellValue('AF' . $secondarySectionRow, 'Dont SFF PME');
        $activeSheet->setCellValue('AG' . $secondarySectionRow, 'Administration Fiscale');
        $activeSheet->setCellValue('AH' . $secondarySectionRow, 'Fichier prélèvements');
        $activeSheet->mergeCells('Y' . $previousBalanceRow . ':AH' . $previousBalanceRow);

        $activeSheet->getStyle('B' . $mainSectionRow . ':AH' . $secondarySectionRow)->getAlignment()->setWrapText(true);

    }

    /**
     * @param \PHPExcel_Worksheet $activeSheet
     * @param \DateTime           $firstDay
     * @param \DateTime           $requestedDate
     * @param int                 $row
     *
     * @return array
     */
    private function addDates(\PHPExcel_Worksheet $activeSheet, \DateTime $firstDay, \DateTime $requestedDate, $row)
    {
        $lastDay = new \DateTime('last day of ' . $requestedDate->format('Y-m'));
        $lastDay->add(new \DateInterval('P1D')); // first day of the next month for the interval
        $dayInterval   = \DateInterval::createFromDateString('1 day');
        $month         = new \DatePeriod($firstDay, $dayInterval, $lastDay);
        $coordinatesDay = [];

        /** @var \DateTime $day */
        foreach ($month as $day) {
            $activeSheet->setCellValueExplicitByColumnAndRow(0, $row, \PHPExcel_Shared_Date::PHPToExcel($day), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->getCellByColumnAndRow(0, $row)->getStyle()->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY);
            $coordinatesDay[$day->format('Y-m-d')] = $row;
            $row++;
        }

        return $coordinatesDay;
    }

    /**
     * @param \PHPExcel_Worksheet $activeSheet
     * @param \DateTime           $requestedDate
     * @param int                 $row
     *
     * @return array
     */
    private function addMonths(\PHPExcel_Worksheet $activeSheet, \DateTime $requestedDate, $row)
    {
        $monthInterval    = \DateInterval::createFromDateString('1 month');
        $year             = new \DatePeriod(new \Datetime('First day of January ' . $requestedDate->format('Y')), $monthInterval, new \DateTime('Last day of december ' . $requestedDate->format('Y')));
        $coordinatesMonth = [];

        /** @var \DateTime $month */
        foreach ($year as $month) {
            $activeSheet->setCellValueByColumnAndRow(0, $row, strftime('%B', $month->getTimestamp()));
            $activeSheet->getStyle('A' . $row)->getFont()->setBold(true);
            $coordinatesMonth[$month->format('n')] = $row;
            $row ++;
        }

        return $coordinatesMonth;
    }

    /**
     * @param \PHPExcel_Worksheet $activeSheet
     */
    private function addGeneralStyleToWorksheet(\PHPExcel_Worksheet $activeSheet)
    {
        $style = [
            'borders' => [
                'allborders' => [
                    'style' => \PHPExcel_Style_Border::BORDER_MEDIUM,
                    'color' => ['argb' => \PHPExcel_Style_Color::COLOR_BLACK]
                ]
            ]
        ];

        $maxCoordinates = $activeSheet->getHighestRowAndColumn();
        $activeSheet->getStyle('A1:AH' . $maxCoordinates['row'])->applyFromArray($style);
        $activeSheet->getStyle('B1:BH' . $maxCoordinates['row'])->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
        $activeSheet->getStyle('A1:AH' . $maxCoordinates['row'])->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
    }

    /**
     * @param \PHPExcel_Worksheet $activeSheet
     * @param array               $movements
     * @param int                 $row
     * @param int                 $totalRow
     */
    private function addMovementsLine(\PHPExcel_Worksheet $activeSheet, array $movements, $row, $totalRow)
    {
        $calculatedTotals = [
            'financialMovements'  => 0,
            'netInterest'         => 0,
            'repaymentAssignment' => 0,
            'fiscalDifference'    => 0
        ];
        foreach ($movements as $date => $line) {
            $lenderProvisionCreditCard   = empty($line['lender_provision_credit_card']) ? 0 : $line['lender_provision_credit_card'];
            $lenderProvisionWireTransfer = empty($line['lender_provision_wire_transfer_in']) ? 0 : $line['lender_provision_wire_transfer_in'];
            $promotionProvision          = empty($line[OperationType::UNILEND_PROMOTIONAL_OPERATION_PROVISION]) ? 0 : $line[OperationType::UNILEND_PROMOTIONAL_OPERATION_PROVISION];
            $borrowerProvision           = empty($line[OperationType::BORROWER_PROVISION]) ? 0 : $line[OperationType::BORROWER_PROVISION];
            $borrowerWithdraw            = empty($line[OperationType::BORROWER_WITHDRAW]) ? 0 : $line[OperationType::BORROWER_WITHDRAW];
            $borrowerCommissionProject   = empty($line['borrower_commission_project']) ? 0 : $line['borrower_commission_project'];
            $borrowerCommissionPayment   = empty($line['borrower_commission_payment']) ? 0 : $line['borrower_commission_payment'];
            $statutoryContributions      = empty($line[OperationType::TAX_FR_STATUTORY_CONTRIBUTIONS]) ? 0 : $line[OperationType::TAX_FR_STATUTORY_CONTRIBUTIONS];
            $incomeTax                   = empty($line[OperationType::TAX_FR_INCOME_TAX_DEDUCTED_AT_SOURCE]) ? 0 : $line[OperationType::TAX_FR_INCOME_TAX_DEDUCTED_AT_SOURCE];
            $csg                         = empty($line[OperationType::TAX_FR_CSG]) ? 0 : $line[OperationType::TAX_FR_CSG];
            $socialDeductions            = empty($line[OperationType::TAX_FR_SOCIAL_DEDUCTIONS]) ? 0 : $line[OperationType::TAX_FR_SOCIAL_DEDUCTIONS];
            $additionalContributions     = empty($line[OperationType::TAX_FR_ADDITIONAL_CONTRIBUTIONS]) ? 0 : $line[OperationType::TAX_FR_ADDITIONAL_CONTRIBUTIONS];
            $solidarityDeductions        = empty($line[OperationType::TAX_FR_SOLIDARITY_DEDUCTIONS]) ? 0 : $line[OperationType::TAX_FR_SOLIDARITY_DEDUCTIONS];
            $crds                        = empty($line[OperationType::TAX_FR_CRDS]) ? 0 : $line[OperationType::TAX_FR_CRDS];
            $lenderWithdraw              = empty($line[OperationType::LENDER_WITHDRAW]) ? 0 : $line[OperationType::LENDER_WITHDRAW];
            $promotionalOffers           = empty($line[OperationType::UNILEND_PROMOTIONAL_OPERATION]) ? 0 : $line[OperationType::UNILEND_PROMOTIONAL_OPERATION];
            $loans                       = empty($line[OperationType::LENDER_LOAN]) ? 0 : $line[OperationType::LENDER_LOAN];
            $capitalRepayment            = empty($line[OperationType::CAPITAL_REPAYMENT]) ? 0 : $line[OperationType::CAPITAL_REPAYMENT];
            $grossInterest               = empty($line[OperationType::GROSS_INTEREST_REPAYMENT]) ? 0 : $line[OperationType::GROSS_INTEREST_REPAYMENT];

            $totalIncoming               = bcadd($borrowerProvision, bcadd($promotionProvision, bcadd($lenderProvisionCreditCard, $lenderProvisionWireTransfer, 2), 2), 2);
            $totalTax                    = bcadd($crds, bcadd($solidarityDeductions, bcadd($additionalContributions, bcadd($socialDeductions, bcadd($csg, bcadd($statutoryContributions, $incomeTax, 2), 2), 2), 2), 2), 2);
            $totalOutgoing               = bcadd($totalTax, bcadd($borrowerCommissionPayment, bcadd($borrowerCommissionProject, bcadd($borrowerWithdraw, $lenderWithdraw, 2), 2), 2), 2);
            $totalFinancialMovementsLine = bcsub($totalIncoming, $totalOutgoing, 2);
            $netInterest                 = bcsub($grossInterest, $totalTax, 2);
            $repaymentAssignment         = bcadd($borrowerCommissionPayment, bcadd($capitalRepayment, $grossInterest, 2), 2);
            $fiscalDifference            = bcsub($borrowerProvision, $repaymentAssignment, 2);

            $calculatedTotals['financialMovements']  = bcadd($calculatedTotals['financialMovements'], $totalFinancialMovementsLine, 2);
            $calculatedTotals['netInterest']         = bcadd($calculatedTotals['netInterest'], $netInterest, 2);
            $calculatedTotals['repaymentAssignment'] = bcadd($calculatedTotals['repaymentAssignment'], $repaymentAssignment, 2);
            $calculatedTotals['fiscalDifference']    = bcadd($calculatedTotals['fiscalDifference'], $fiscalDifference, 2);

            /* Financial Movements */
            $activeSheet->setCellValueExplicitByColumnAndRow(1, $row, $lenderProvisionCreditCard, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicitByColumnAndRow(2, $row, $lenderProvisionWireTransfer, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicitByColumnAndRow(3, $row, 0, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicitByColumnAndRow(4, $row, $promotionProvision, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicitByColumnAndRow(5, $row, $borrowerProvision, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicitByColumnAndRow(6, $row, $borrowerWithdraw, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicitByColumnAndRow(7, $row, $borrowerCommissionProject, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicitByColumnAndRow(8, $row, $borrowerCommissionPayment, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicitByColumnAndRow(9, $row, $statutoryContributions, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicitByColumnAndRow(10, $row, $incomeTax, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicitByColumnAndRow(11, $row, $csg, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicitByColumnAndRow(12, $row, $socialDeductions, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicitByColumnAndRow(13, $row, $additionalContributions, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicitByColumnAndRow(14, $row, $solidarityDeductions, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicitByColumnAndRow(15, $row, $crds, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicitByColumnAndRow(16, $row, $lenderWithdraw, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicitByColumnAndRow(17, $row, $totalFinancialMovementsLine , \PHPExcel_Cell_DataType::TYPE_NUMERIC);

            /* Internal Movements */
            $activeSheet->setCellValueExplicitByColumnAndRow(24, $row, $promotionalOffers, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicitByColumnAndRow(25, $row, $loans, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicitByColumnAndRow(26, $row, $capitalRepayment, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicitByColumnAndRow(27, $row, $netInterest, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicitByColumnAndRow(28, $row, $repaymentAssignment, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicitByColumnAndRow(29, $row, $fiscalDifference, \PHPExcel_Cell_DataType::TYPE_NUMERIC);

            $row++;
        }
        $this->addTotalMovementsLine($activeSheet, $movements, $totalRow, $calculatedTotals);
    }

    /**
     * @param \PHPExcel_Worksheet $activeSheet
     * @param array               $movements
     * @param int                 $row
     * @param array               $$calculatedTotals
     */
    private function addTotalMovementsLine(\PHPExcel_Worksheet $activeSheet, array $movements, $row, array $calculatedTotals)
    {
        $activeSheet->setCellValueByColumnAndRow(0, $row, 'Total mois');
        $activeSheet->setCellValueExplicitByColumnAndRow(1, $row, array_sum(array_column($movements, 'lender_provision_credit_card')), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicitByColumnAndRow(2, $row, array_sum(array_column($movements,'lender_provision_wire_transfer_in')), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicitByColumnAndRow(3, $row, 0, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicitByColumnAndRow(4, $row, array_sum(array_column($movements, OperationType::UNILEND_PROMOTIONAL_OPERATION_PROVISION)), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicitByColumnAndRow(5, $row, array_sum(array_column($movements, OperationType::BORROWER_PROVISION)), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicitByColumnAndRow(6, $row, array_sum(array_column($movements, OperationType::BORROWER_WITHDRAW)), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicitByColumnAndRow(7, $row, array_sum(array_column($movements, 'borrower_commission_project')), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicitByColumnAndRow(8, $row, array_sum(array_column($movements, 'borrower_commission_payment')), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicitByColumnAndRow(9, $row, array_sum(array_column($movements, OperationType::TAX_FR_STATUTORY_CONTRIBUTIONS)), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicitByColumnAndRow(10, $row, array_sum(array_column($movements, OperationType::TAX_FR_INCOME_TAX_DEDUCTED_AT_SOURCE)), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicitByColumnAndRow(11, $row, array_sum(array_column($movements, OperationType::TAX_FR_CSG)), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicitByColumnAndRow(12, $row, array_sum(array_column($movements, OperationType::TAX_FR_SOCIAL_DEDUCTIONS)), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicitByColumnAndRow(13, $row, array_sum(array_column($movements, OperationType::TAX_FR_ADDITIONAL_CONTRIBUTIONS)), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicitByColumnAndRow(14, $row, array_sum(array_column($movements, OperationType::TAX_FR_SOLIDARITY_DEDUCTIONS)), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicitByColumnAndRow(15, $row, array_sum(array_column($movements, OperationType::TAX_FR_CRDS)), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicitByColumnAndRow(16, $row, array_sum(array_column($movements, OperationType::LENDER_WITHDRAW)), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicitByColumnAndRow(17, $row, $calculatedTotals['financialMovements'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicitByColumnAndRow(24, $row, array_sum(array_column($movements, OperationType::UNILEND_PROMOTIONAL_OPERATION)), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicitByColumnAndRow(25, $row, array_sum(array_column($movements, OperationType::LENDER_LOAN)), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicitByColumnAndRow(26, $row, array_sum(array_column($movements, OperationType::CAPITAL_REPAYMENT)), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicitByColumnAndRow(27, $row, $calculatedTotals['netInterest'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicitByColumnAndRow(28, $row, $calculatedTotals['repaymentAssignment'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicitByColumnAndRow(29, $row, $calculatedTotals['fiscalDifference'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);
    }

    private function addBalanceColumns(\PHPExcel_Worksheet $activeSheet, array $movements, array $balances, $row, \DateTime $firstDayOfTheMonth, \DateTime $date)
    {
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        /** @var OperationRepository $operationRepository */
        $operationRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Operation');

        $theoreticalBalance = 0;
        $availableBalance   = 0;
        $difference         = 0;
        $promotionalBalance = 0;
        $unilendBalance     = 0;
        $taxBalance         = 0;

        foreach ($balances as $balanceType => $balanceByDay) {
            if ($balanceType == 'lender_borrower') {
                $lenderBorrowerRow = $row;
                foreach ($balanceByDay as $day => $balance) {
                    $dataDay  = \DateTime::createFromFormat('Y-m-d', $day);
                    $dataDay->setTime(0,0,0);
                    if (2 == $dataDay->diff($firstDayOfTheMonth)->days) {
                        continue;
                    }
                    if (1 == $dataDay->diff($firstDayOfTheMonth)->days) {
                        $dataDayStart      = $dataDay;
                        $dataDayEnd        = $dataDay;
                        $movementsOfTheDay = $operationRepository->sumFinancialMovementsForDailyState($dataDayStart, $dataDayEnd);
                    } else {
                        $movementsOfTheDay = $movements[$day];
                    }

                    $previousDay = new \DateTime($day);
                    $previousDay->sub(new \DateInterval('P1D'));
                    $theoreticalBalance = bcadd($balanceByDay[$previousDay->format('Y-m-d')], array_sum($movementsOfTheDay), 2);
                    if ($day !== $date->format('Y-m-d')) {
                        $availableBalance = 'va venir plus tard';
                    } else {
                        $availableBalance = $balance;
                    }
                    $difference = bcsub($availableBalance, $theoreticalBalance, 2);
                    $activeSheet->setCellValueExplicitByColumnAndRow(18, $row, $theoreticalBalance, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                    $activeSheet->setCellValueExplicitByColumnAndRow(19, $row, $availableBalance, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                    $activeSheet->setCellValueExplicitByColumnAndRow(20, $row, $difference, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                    $lenderBorrowerRow++;
                }
            }
            if ($balanceType == 'unilend_promotional') {
                $unilendPromotionalRow = $row;
                foreach ($balanceByDay as $day => $balance) {
                    if ($day !== $date->format('Y-m-d')) {
                        $promotionalBalance = empty($balance[$day]) ? $promotionalBalance : $balances[$day];
                        $activeSheet->setCellValueExplicitByColumnAndRow(21, $row, $promotionalBalance, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                        $unilendPromotionalRow ++;
                    }
                }
            }
            if ($balanceType == 'unilend') {
                $unilendRow = $row;
                foreach ($balanceByDay as $day => $balance) {
                    if ($day !== $date->format('Y-m-d')) {
                        $unilendBalance = empty($balance[$day]) ? $unilendBalance : $balances[$day];
                        $activeSheet->setCellValueExplicitByColumnAndRow(22, $row, $unilendBalance, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                        $unilendRow ++;
                    }
                }
            }
            if ($balanceType == 'tax') {
                $taxRow = $row;
                foreach ($balanceByDay as $day => $balance) {
                    if ($day !== $date->format('Y-m-d')) {
                        $taxBalance = empty($balance[$day]) ? $taxBalance : $balances[$day];
                        $activeSheet->setCellValueExplicitByColumnAndRow(23, $row, $taxBalance, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                        $taxRow ++;
                    }
                }
            }
        }
    }

    /**
     * @param \PHPExcel_Worksheet $activeSheet
     * @param array               $wireTransfers
     * @param int                 $totalRow
     * @param array               $coordinates
     */
    private function addWireTransferLines(\PHPExcel_Worksheet $activeSheet, array $wireTransfers, $totalRow, array $coordinates)
    {
        $totalWireTransferOut        = 0;
        $totalUnilendWireTransferOut = 0;
        $totalTaxWireTransferOut     = 0;
        $totalDirectDebit            = 0;

        foreach ($coordinates as $date => $row) {
            $wireTransferOut        = empty($wireTransfers['out'][$date]) ? 0 : $wireTransfers['out'][$date];
            $unilendWireTransferOut = empty($wireTransfers['unilend'][$date]) ? 0 : $wireTransfers['unilend'][$date];
            $taxWireTransferOut     = empty($wireTransfers['taxes'][$date]) ? 0 : $taxWireTransferOut = is_array($wireTransfers['taxes'][$date]) ? array_sum($wireTransfers['taxes'][$date]) : $wireTransfers['taxes'][$date];
            $directDebit            = empty($wireTransfers['directDebit'][$date]) ? 0 : $wireTransfers['directDebit'][$date];

            $totalWireTransferOut        = bcadd($totalWireTransferOut, $wireTransferOut, 2);
            $totalUnilendWireTransferOut = bcadd($totalUnilendWireTransferOut, $unilendWireTransferOut, 2);
            $totalTaxWireTransferOut     = bcadd($totalTaxWireTransferOut, $taxWireTransferOut, 2);
            $totalDirectDebit            = bcadd($totalDirectDebit, $directDebit, 2);

            $activeSheet->setCellValueExplicitByColumnAndRow(30, $row, $wireTransferOut, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicitByColumnAndRow(31, $row, $unilendWireTransferOut, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicitByColumnAndRow(32, $row, $taxWireTransferOut, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicitByColumnAndRow(33, $row, $directDebit, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        }

        $activeSheet->setCellValueExplicitByColumnAndRow(30, $totalRow, $totalWireTransferOut, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicitByColumnAndRow(31, $totalRow, $totalUnilendWireTransferOut, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicitByColumnAndRow(32, $totalRow, $totalTaxWireTransferOut, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicitByColumnAndRow(33, $totalRow, $totalDirectDebit, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
    }



    /**
     * {@inheritdoc}
     */
    protected function executeOld(InputInterface $input, OutputInterface $output)
    {
        try {
            /** @var EntityManager $entityManager */
            $entityManager = $this->getContainer()->get('unilend.service.entity_manager');

            /** @var \ficelle $ficelle */
            $ficelle = Loader::loadLib('ficelle');
            /** @var \dates $dates */
            $dates = Loader::loadLib('dates');

            /** @var \transactions $transaction */
            $transaction = $entityManager->getRepository('transactions');
            /** @var \echeanciers $lenderRepayment */
            $lenderRepayment = $entityManager->getRepository('echeanciers');
            /** @var \echeanciers_emprunteur $borrowerRepayment */
            $borrowerRepayment = $entityManager->getRepository('echeanciers_emprunteur');
            /** @var \virements $bankTransfer */
            $bankTransfer = $entityManager->getRepository('virements');
            /** @var \prelevements $directDebit */
            $directDebit = $entityManager->getRepository('prelevements');
            /** @var \etat_quotidien $dailyState */
            $dailyState = $entityManager->getRepository('etat_quotidien');
            /** @var \bank_unilend $unilendBank */
            $unilendBank = $entityManager->getRepository('bank_unilend');
            /** @var \tax $tax */
            $tax = $entityManager->getRepository('tax');

            $time = $input->getArgument('day');

            if ($time) {
                $time = strtotime($time);

                if (false === $time) {
                    $output->writeln('<error>Wrong date format ("Y-m-d" expected)</error>');
                    return;
                }
            } else {
                $time = time();
            }

            // si on veut mettre a jour une date on met le jour ici mais attention ca va sauvegarder en BDD et sur l'etat quotidien fait ce matin a 1h du mat
            if (date('d', $time) == 1) {
                $mois = mktime(0, 0, 0, date('m', $time) - 1, 1, date('Y', $time));
            } else {
                $mois = mktime(0, 0, 0, date('m', $time), 1, date('Y', $time));
            }

            $dateTime  = (new \DateTime())->setTimestamp($mois);
            $aColumns  = $this->getColumns();
            $monthDays = $this->getMonthDays($mois);
            $limitDate = mktime(0, 0, 0, date('m', $time), date('d', $time), date('Y', $time));

            $transactionType = [
                \transactions_types::TYPE_LENDER_SUBSCRIPTION,
                \transactions_types::TYPE_LENDER_CREDIT_CARD_CREDIT,
                \transactions_types::TYPE_LENDER_BANK_TRANSFER_CREDIT,
                \transactions_types::TYPE_BORROWER_REPAYMENT,
                \transactions_types::TYPE_DIRECT_DEBIT,
                \transactions_types::TYPE_LENDER_WITHDRAWAL,
                \transactions_types::TYPE_BORROWER_BANK_TRANSFER_CREDIT,
                \transactions_types::TYPE_UNILEND_REPAYMENT,
                \transactions_types::TYPE_UNILEND_BANK_TRANSFER,
                \transactions_types::TYPE_FISCAL_BANK_TRANSFER,
                \transactions_types::TYPE_REGULATION_COMMISSION,
                \transactions_types::TYPE_BORROWER_REPAYMENT_REJECTION,
                \transactions_types::TYPE_WELCOME_OFFER,
                \transactions_types::TYPE_WELCOME_OFFER_CANCELLATION,
                \transactions_types::TYPE_UNILEND_WELCOME_OFFER_BANK_TRANSFER,
                \transactions_types::TYPE_BORROWER_ANTICIPATED_REPAYMENT,
                \transactions_types::TYPE_REGULATION_BANK_TRANSFER,
                \transactions_types::TYPE_RECOVERY_BANK_TRANSFER,
                \transactions_types::TYPE_LENDER_RECOVERY_REPAYMENT
            ];

            $dailyTransactions = $transaction->getDailyState($transactionType, $dateTime);
            $dailyWelcomeOffer = $transaction->getDailyWelcomeOffer($dateTime);

            $lrembPreteurs                = $unilendBank->sumMontantByDayMonths('type = 2 AND status = 1', $dateTime->format('m'), $dateTime->format('Y')); // Les remboursements preteurs
            $alimCB                       = $this->combineTransactionTypes(
                    true === isset($dailyTransactions[\transactions_types::TYPE_LENDER_CREDIT_CARD_CREDIT]) ? $dailyTransactions[\transactions_types::TYPE_LENDER_CREDIT_CARD_CREDIT] : [],
                    $dailyWelcomeOffer
                ) + $monthDays;
            $rembEmprunteur               = $this->combineTransactionTypes(
                    true === isset($dailyTransactions[\transactions_types::TYPE_BORROWER_REPAYMENT]) ? $dailyTransactions[\transactions_types::TYPE_BORROWER_REPAYMENT] : [],
                    true === isset($dailyTransactions[\transactions_types::TYPE_BORROWER_ANTICIPATED_REPAYMENT]) ? $dailyTransactions[\transactions_types::TYPE_BORROWER_ANTICIPATED_REPAYMENT] : []
                ) + $monthDays;
            $alimVirement                 = (true === isset($dailyTransactions[\transactions_types::TYPE_LENDER_BANK_TRANSFER_CREDIT]) ? $dailyTransactions[\transactions_types::TYPE_LENDER_BANK_TRANSFER_CREDIT] : []) + $monthDays;
            $alimPrelevement              = (true === isset($dailyTransactions[\transactions_types::TYPE_DIRECT_DEBIT]) ? $dailyTransactions[\transactions_types::TYPE_DIRECT_DEBIT] : []) + $monthDays;
            $rembEmprunteurRegularisation = (true === isset($dailyTransactions[\transactions_types::TYPE_REGULATION_BANK_TRANSFER]) ? $dailyTransactions[\transactions_types::TYPE_REGULATION_BANK_TRANSFER] : []) + $monthDays;
            $rejetrembEmprunteur          = (true === isset($dailyTransactions[\transactions_types::TYPE_BORROWER_REPAYMENT_REJECTION]) ? $dailyTransactions[\transactions_types::TYPE_BORROWER_REPAYMENT_REJECTION] : []) + $monthDays;
            $virementEmprunteur           = (true === isset($dailyTransactions[\transactions_types::TYPE_BORROWER_BANK_TRANSFER_CREDIT]) ? $dailyTransactions[\transactions_types::TYPE_BORROWER_BANK_TRANSFER_CREDIT] : []) + $monthDays;
            $virementUnilend              = (true === isset($dailyTransactions[\transactions_types::TYPE_UNILEND_BANK_TRANSFER]) ? $dailyTransactions[\transactions_types::TYPE_UNILEND_BANK_TRANSFER] : []) + $monthDays;
            $virementEtat                 = (true === isset($dailyTransactions[\transactions_types::TYPE_FISCAL_BANK_TRANSFER]) ? $dailyTransactions[\transactions_types::TYPE_FISCAL_BANK_TRANSFER] : []) + $monthDays;
            $retraitPreteur               = (true === isset($dailyTransactions[\transactions_types::TYPE_LENDER_WITHDRAWAL]) ? $dailyTransactions[\transactions_types::TYPE_LENDER_WITHDRAWAL] : []) + $monthDays;
            $regulCom                     = (true === isset($dailyTransactions[\transactions_types::TYPE_REGULATION_COMMISSION]) ? $dailyTransactions[\transactions_types::TYPE_REGULATION_COMMISSION] : []) + $monthDays;
            $offres_bienvenue             = (true === isset($dailyTransactions[\transactions_types::TYPE_WELCOME_OFFER]) ? $dailyTransactions[\transactions_types::TYPE_WELCOME_OFFER] : []) + $monthDays;
            $offres_bienvenue_retrait     = (true === isset($dailyTransactions[\transactions_types::TYPE_WELCOME_OFFER_CANCELLATION]) ? $dailyTransactions[\transactions_types::TYPE_WELCOME_OFFER_CANCELLATION] : []) + $monthDays;
            $unilend_bienvenue            = (true === isset($dailyTransactions[\transactions_types::TYPE_UNILEND_WELCOME_OFFER_BANK_TRANSFER]) ? $dailyTransactions[\transactions_types::TYPE_UNILEND_WELCOME_OFFER_BANK_TRANSFER] : []) + $monthDays;
            $virementRecouv               = (true === isset($dailyTransactions[\transactions_types::TYPE_RECOVERY_BANK_TRANSFER]) ? $dailyTransactions[\transactions_types::TYPE_RECOVERY_BANK_TRANSFER] : []) + $monthDays;
            $rembRecouvPreteurs           = (true === isset($dailyTransactions[\transactions_types::TYPE_LENDER_RECOVERY_REPAYMENT]) ? $dailyTransactions[\transactions_types::TYPE_LENDER_RECOVERY_REPAYMENT] : []) + $monthDays;
            $listPrel                     = [];

            foreach ($directDebit->select('type_prelevement = 1 AND status > 0 AND type = 1') as $prelev) {
                $addedXml     = strtotime($prelev['added_xml']);
                $added        = strtotime($prelev['added']);
                $dateaddedXml = date('Y-m', $addedXml);
                $date         = date('Y-m', $added);
                $i            = 1;

                // on enregistre dans la table la premier prelevement
                $listPrel[date('Y-m-d', $added)] += $prelev['montant'];

                // tant que la date de creation n'est pas egale on rajoute les mois entre
                while ($date != $dateaddedXml) {
                    $newdate = mktime(0, 0, 0, date('m', $added) + $i, date('d', $addedXml), date('Y', $added));
                    $date    = date('Y-m', $newdate);
                    $added   = date('Y-m-d', $newdate) . ' 00:00:00';

                    $listPrel[date('Y-m-d', $newdate)] += $prelev['montant'];

                    $i++;
                }
            }

            $oldDate           = mktime(0, 0, 0, $dateTime->format('m') - 1, 1, $dateTime->format('Y'));
            $oldDate           = date('Y-m', $oldDate);
            $etat_quotidienOld = $dailyState->getTotauxbyMonth($oldDate);

            if ($etat_quotidienOld != false) {
                $soldeDeLaVeille      = $etat_quotidienOld['totalNewsoldeDeLaVeille'];
                $soldeReel            = $etat_quotidienOld['totalNewSoldeReel'];
                $soldeSFFPME_old      = $etat_quotidienOld['totalSoldeSFFPME'];
                $soldeAdminFiscal_old = $etat_quotidienOld['totalSoldeAdminFiscal'];
                $soldePromotion_old   = isset($etat_quotidienOld['totalSoldePromotion']) ? $etat_quotidienOld['totalSoldePromotion'] : 0;
            } else {
                $soldeDeLaVeille      = 0;
                $soldeReel            = 0;
                $soldeSFFPME_old      = 0;
                $soldeAdminFiscal_old = 0;
                $soldePromotion_old   = 0;
            }

            $newsoldeDeLaVeille = $soldeDeLaVeille;
            $soldePromotion     = $soldePromotion_old;
            $oldecart           = $soldeDeLaVeille - $soldeReel;
            $soldeSFFPME        = $soldeSFFPME_old;
            $soldeAdminFiscal   = $soldeAdminFiscal_old;

            $totalAlimCB                              = 0;
            $totalAlimVirement                        = 0;
            $totalAlimPrelevement                     = 0;
            $totalRembEmprunteur                      = 0;
            $totalVirementEmprunteur                  = 0;
            $totalVirementCommissionUnilendEmprunteur = 0;
            $totalCommission                          = 0;
            $totalVirementUnilend_bienvenue           = 0;
            $totalAffectationEchEmpr                  = 0;
            $totalOffrePromo                          = 0;
            $totalOctroi_pret                         = 0;
            $totalCapitalPreteur                      = 0;
            $totalInteretNetPreteur                   = 0;
            $totalEcartMouvInternes                   = 0;
            $totalVirementsOK                         = 0;
            $totalVirementsAttente                    = 0;
            $totaladdsommePrelev                      = 0;
            $totalAdminFiscalVir                      = 0;
            $totalPrelevements_obligatoires           = 0;
            $totalRetenues_source                     = 0;
            $totalCsg                                 = 0;
            $totalPrelevements_sociaux                = 0;
            $totalContributions_additionnelles        = 0;
            $totalPrelevements_solidarite             = 0;
            $totalCrds                                = 0;
            $totalRetraitPreteur                      = 0;
            $totalSommeMouvements                     = 0;
            $totalNewSoldeReel                        = 0;
            $totalEcartSoldes                         = 0;
            $totalNewsoldeDeLaVeille                  = 0;
            $totalSoldePromotion                      = 0;
            $totalSoldeSFFPME                         = $soldeSFFPME_old;
            $totalSoldeAdminFiscal                    = $soldeAdminFiscal_old;

            $tableau = '
        <style>
            table th,table td{width:80px;height:20px;border:1px solid black;}
            table td.dates{text-align:center;}
            .right{text-align:right;}
            .center{text-align:center;}
            .boder-top{border-top:1px solid black;}
            .boder-bottom{border-bottom:1px solid black;}
            .boder-left{border-left:1px solid black;}
            .boder-right{border-right:1px solid black;}
        </style>

        <table border="0" cellpadding="0" cellspacing="0" style=" background-color:#fff; font:11px/13px Arial, Helvetica, sans-serif; color:#000;width: 2500px;">
            <tr>
                <th colspan="34" style="height:35px;font:italic 18px Arial, Helvetica, sans-serif; text-align:center;">UNILEND</th>
            </tr>
            <tr>
                <th rowspan="2">' . $dateTime->format('d-m-Y') . '</th>
                <th colspan="3">Chargements compte pr&ecirc;teurs</th>
                <th>Chargements offres</th>
                <th>Echeances<br />Emprunteur</th>
                <th>Octroi prêt</th>
                <th>Commissions<br />octroi pr&ecirc;t</th>
                <th>Commissions<br />restant d&ucirc;</th>
                <th colspan="7">Retenues fiscales</th>
                <th>Remboursements<br />aux pr&ecirc;teurs</th>
                <th>&nbsp;</th>
                <th colspan="6">Soldes</th>
                <th colspan="6">Mouvements internes</th>
                <th colspan="3">Virements</th>
                <th>Pr&eacute;l&egrave;vements</th>
            </tr>
            <tr>';

            foreach ($aColumns as $key => $value) {
                $tableau .= '<td class="center">' . $value . '</td>';
            }

            $tableau .= '
            </tr>
            <tr>
                <td colspan="18">D&eacute;but du mois</td>
                <td class="right">' . $ficelle->formatNumber($soldeDeLaVeille) . '</td>
                <td class="right">' . $ficelle->formatNumber($soldeReel) . '</td>
                <td class="right">' . $ficelle->formatNumber($oldecart) . '</td>
                <td class="right">' . $ficelle->formatNumber($soldePromotion_old) . '</td>
                <td class="right">' . $ficelle->formatNumber($soldeSFFPME_old) . '</td>
                <td class="right">' . $ficelle->formatNumber($soldeAdminFiscal_old) . '</td>
                <td colspan="10">&nbsp;</td>
            </tr>';

            foreach (array_keys($monthDays) as $date) {

                if (strtotime($date . ' 00:00:00') < $limitDate) {
                    $interetNetPreteur = bcdiv($transaction->getInterestsAmount($date . ' 00:00:00', $date . ' 23:59:59'), 100, 2);
                    $aDailyTax         = $tax->getDailyTax($date . ' 00:00:00', $date . ' 23:59:59');
                    $iTotalTaxAmount   = 0;

                    foreach ($aDailyTax as $iTaxTypeId => $iTaxAmount) {
                        $aDailyTax[$iTaxTypeId] = bcdiv($iTaxAmount, 100, 2);
                        $iTotalTaxAmount += $aDailyTax[$iTaxTypeId];
                    }
                    $dailyRepaidCapital = $lenderRepayment->getRepaidCapitalInDateRange(null, $date . ' 00:00:00', $date . ' 23:59:59');
                    $commission         = bcdiv($borrowerRepayment->getCostsAndVatAmount($date), 100, 2);
                    $commission         = bcadd($commission, $regulCom[$date]['montant'], 2);

                    $soldePromotion += $unilend_bienvenue[$date]['montant'];
                    $soldePromotion -= $offres_bienvenue[$date]['montant'];
                    $soldePromotion += -$offres_bienvenue_retrait[$date]['montant'];

                    $offrePromo = $offres_bienvenue[$date]['montant'] + $offres_bienvenue_retrait[$date]['montant'];

                    $entrees = $alimCB[$date]['montant'] + $alimVirement[$date]['montant'] + $alimPrelevement[$date]['montant'] + $rembEmprunteur[$date]['montant'] + $rembEmprunteurRegularisation[$date]['montant'] + $unilend_bienvenue[$date]['montant'] + $rejetrembEmprunteur[$date]['montant'] + $virementRecouv[$date]['montant'];
                    $sorties = abs($virementEmprunteur[$date]['montant']) + $virementEmprunteur[$date]['montant_unilend'] + $commission + $iTotalTaxAmount + abs($retraitPreteur[$date]['montant']);

                    $sommeMouvements = $entrees - $sorties;
                    $newsoldeDeLaVeille += $sommeMouvements;

                    $soldeReel      = bcadd($soldeReel, $this->getRealSold($dailyTransactions, $date), 2);
                    $soldeReel      = bcadd(
                        $soldeReel,
                        bcsub(
                            $this->getUnilendRealSold($dailyTransactions, $date),
                            bcadd($commission, $this->getStateRealSold($dailyTransactions, $date), 2),
                            2),
                        2);
                    $newSoldeReel   = $soldeReel; // on retire la commission des echeances du jour ainsi que la partie pour l'etat
                    $soldeTheorique = $newsoldeDeLaVeille;
                    $leSoldeReel    = $newSoldeReel;

                    if (strtotime($date . ' 00:00:00') > time()) {
                        $soldeTheorique = 0;
                        $leSoldeReel    = 0;
                    }

                    $ecartSoldes = $soldeTheorique - $leSoldeReel;
                    $soldeSFFPME += $virementEmprunteur[$date]['montant_unilend'] - $virementUnilend[$date]['montant'] + $commission;
                    $soldeAdminFiscal += $iTotalTaxAmount - $virementEtat[$date]['montant'];

                    $capitalPreteur = $dailyRepaidCapital + $rembRecouvPreteurs[$date]['montant'];

                    $affectationEchEmpr = isset($lrembPreteurs[$date]) ? $lrembPreteurs[$date]['montant'] + $lrembPreteurs[$date]['etat'] + $commission + $rembRecouvPreteurs[$date]['montant'] : 0;
                    $ecartMouvInternes  = round($affectationEchEmpr - $commission - $iTotalTaxAmount - $capitalPreteur - $interetNetPreteur, 2);
                    $octroi_pret        = abs($virementEmprunteur[$date]['montant']) + $virementEmprunteur[$date]['montant_unilend'];
                    $virementsOK        = $bankTransfer->sumVirementsbyDay($date, 'status = ' . Virements::STATUS_SENT);
                    $virementsAttente   = $virementUnilend[$date]['montant'];
                    $adminFiscalVir     = $virementEtat[$date]['montant'];
                    $prelevPonctuel     = $directDebit->sum('DATE(added_xml) = "' . $date . '" AND status > 0');

                    if (false === empty($listPrel[$date])) {
                        $sommePrelev = $prelevPonctuel + $listPrel[$date];
                    } else {
                        $sommePrelev = $prelevPonctuel;
                    }

                    $sommePrelev      = $sommePrelev / 100;
                    $leRembEmprunteur = $rembEmprunteur[$date]['montant'] + $rembEmprunteurRegularisation[$date]['montant'] + $rejetrembEmprunteur[$date]['montant'] + $virementRecouv[$date]['montant'];

                    $totalAlimCB += $alimCB[$date]['montant'];
                    $totalAlimVirement += $alimVirement[$date]['montant'];
                    $totalAlimPrelevement += $alimPrelevement[$date]['montant'];
                    $totalRembEmprunteur += $leRembEmprunteur; // update le 22/01/2015
                    $totalVirementEmprunteur += abs($virementEmprunteur[$date]['montant']);
                    $totalVirementCommissionUnilendEmprunteur += $virementEmprunteur[$date]['montant_unilend'];
                    $totalVirementUnilend_bienvenue += $unilend_bienvenue[$date]['montant'];
                    $totalCommission += $commission;

                    $totalPrelevements_obligatoires += isset($aDailyTax[\tax_type::TYPE_INCOME_TAX]) ? $aDailyTax[\tax_type::TYPE_INCOME_TAX] : 0.0;
                    $totalRetenues_source += isset($aDailyTax[\tax_type::TYPE_INCOME_TAX_DEDUCTED_AT_SOURCE]) ? $aDailyTax[\tax_type::TYPE_INCOME_TAX_DEDUCTED_AT_SOURCE] : 0.0;
                    $totalCsg += isset($aDailyTax[\tax_type::TYPE_CSG]) ? $aDailyTax[\tax_type::TYPE_CSG] : 0.0;
                    $totalPrelevements_sociaux += isset($aDailyTax[\tax_type::TYPE_SOCIAL_DEDUCTIONS]) ? $aDailyTax[\tax_type::TYPE_SOCIAL_DEDUCTIONS] : 0.0;
                    $totalContributions_additionnelles += isset($aDailyTax[\tax_type::TYPE_ADDITIONAL_CONTRIBUTION_TO_SOCIAL_DEDUCTIONS]) ? $aDailyTax[\tax_type::TYPE_ADDITIONAL_CONTRIBUTION_TO_SOCIAL_DEDUCTIONS] : 0.0;
                    $totalPrelevements_solidarite += isset($aDailyTax[\tax_type::TYPE_SOLIDARITY_DEDUCTIONS]) ? $aDailyTax[\tax_type::TYPE_SOLIDARITY_DEDUCTIONS] : 0.0;
                    $totalCrds += isset($aDailyTax[\tax_type::TYPE_CRDS]) ? $aDailyTax[\tax_type::TYPE_CRDS] : 0.0;

                    $totalRetraitPreteur += $retraitPreteur[$date]['montant'];
                    $totalSommeMouvements += $sommeMouvements;
                    $totalNewsoldeDeLaVeille = $newsoldeDeLaVeille; // Solde théorique
                    $totalNewSoldeReel       = $newSoldeReel;
                    $totalEcartSoldes        = $ecartSoldes;
                    $totalAffectationEchEmpr += $affectationEchEmpr;
                    $totalSoldePromotion = $soldePromotion;
                    $totalOffrePromo += $offrePromo;
                    $totalSoldeSFFPME      = $soldeSFFPME;
                    $totalSoldeAdminFiscal = $soldeAdminFiscal;
                    $totalOctroi_pret += $octroi_pret;
                    $totalCapitalPreteur += $capitalPreteur;
                    $totalInteretNetPreteur += $interetNetPreteur;
                    $totalEcartMouvInternes += $ecartMouvInternes;
                    $totalVirementsOK += $virementsOK;
                    $totalVirementsAttente += $virementsAttente;
                    $totaladdsommePrelev += $sommePrelev;
                    $totalAdminFiscalVir += $adminFiscalVir;

                    $tableau .= '
                <tr>
                    <td class="dates">' . date('d/m/Y', strtotime($date)) . '</td>
                    <td class="right">' . $ficelle->formatNumber($alimCB[$date]['montant']) . '</td>
                    <td class="right">' . $ficelle->formatNumber($alimVirement[$date]['montant']) . '</td>
                    <td class="right">' . $ficelle->formatNumber($alimPrelevement[$date]['montant']) . '</td>
                    <td class="right">' . $ficelle->formatNumber($unilend_bienvenue[$date]['montant']) . '</td>
                    <td class="right">' . $ficelle->formatNumber($leRembEmprunteur) . '</td>
                    <td class="right">' . $ficelle->formatNumber(abs($virementEmprunteur[$date]['montant'])) . '</td>
                    <td class="right">' . $ficelle->formatNumber($virementEmprunteur[$date]['montant_unilend']) . '</td>
                    <td class="right">' . $ficelle->formatNumber($commission) . '</td>
                    <td class="right">' . $ficelle->formatNumber(isset($aDailyTax[\tax_type::TYPE_INCOME_TAX]) ? $aDailyTax[\tax_type::TYPE_INCOME_TAX] : 0) . '</td>
                    <td class="right">' . $ficelle->formatNumber(isset($aDailyTax[\tax_type::TYPE_INCOME_TAX_DEDUCTED_AT_SOURCE]) ? $aDailyTax[\tax_type::TYPE_INCOME_TAX_DEDUCTED_AT_SOURCE] : 0.0) . '</td>
                    <td class="right">' . $ficelle->formatNumber(isset($aDailyTax[\tax_type::TYPE_CSG]) ? $aDailyTax[\tax_type::TYPE_CSG] : 0.0) . '</td>
                    <td class="right">' . $ficelle->formatNumber(isset($aDailyTax[\tax_type::TYPE_SOCIAL_DEDUCTIONS]) ? $aDailyTax[\tax_type::TYPE_SOCIAL_DEDUCTIONS] : 0.0) . '</td>
                    <td class="right">' . $ficelle->formatNumber(isset($aDailyTax[\tax_type::TYPE_ADDITIONAL_CONTRIBUTION_TO_SOCIAL_DEDUCTIONS]) ? $aDailyTax[\tax_type::TYPE_ADDITIONAL_CONTRIBUTION_TO_SOCIAL_DEDUCTIONS] : 0.0) . '</td>
                    <td class="right">' . $ficelle->formatNumber(isset($aDailyTax[\tax_type::TYPE_SOLIDARITY_DEDUCTIONS]) ? $aDailyTax[\tax_type::TYPE_SOLIDARITY_DEDUCTIONS] : 0.0) . '</td>
                    <td class="right">' . $ficelle->formatNumber(isset($aDailyTax[\tax_type::TYPE_CRDS]) ? $aDailyTax[\tax_type::TYPE_CRDS] : 0.0) . '</td>
                    <td class="right">' . $ficelle->formatNumber(abs($retraitPreteur[$date]['montant'])) . '</td>
                    <td class="right">' . $ficelle->formatNumber($sommeMouvements) . '</td>
                    <td class="right">' . $ficelle->formatNumber($soldeTheorique) . '</td>
                    <td class="right">' . $ficelle->formatNumber($leSoldeReel) . '</td>
                    <td class="right">' . $ficelle->formatNumber($ecartSoldes) . '</td>
                    <td class="right">' . $ficelle->formatNumber($soldePromotion) . '</td>
                    <td class="right">' . $ficelle->formatNumber($soldeSFFPME) . '</td>
                    <td class="right">' . $ficelle->formatNumber($soldeAdminFiscal) . '</td>
                    <td class="right">' . $ficelle->formatNumber($offrePromo) . '</td>
                    <td class="right">' . $ficelle->formatNumber($octroi_pret) . '</td>
                    <td class="right">' . $ficelle->formatNumber($capitalPreteur) . '</td>
                    <td class="right">' . $ficelle->formatNumber($interetNetPreteur) . '</td>
                    <td class="right">' . $ficelle->formatNumber($affectationEchEmpr) . '</td>
                    <td class="right">' . $ficelle->formatNumber($ecartMouvInternes) . '</td>
                    <td class="right">' . $ficelle->formatNumber($virementsOK) . '</td>
                    <td class="right">' . $ficelle->formatNumber($virementsAttente) . '</td>
                    <td class="right">' . $ficelle->formatNumber($adminFiscalVir) . '</td>
                    <td class="right">' . $ficelle->formatNumber($sommePrelev) . '</td>
                </tr>';
                } else {
                    $tableau .= '
                <tr>
                    <td class="dates">' . date('d/m/Y', strtotime($date)) . '</td>';
                    foreach ($aColumns as $value) {
                        $tableau .= '<td>&nbsp;</td>';
                    }
                    $tableau .= '</tr>';
                }
            }

            $tableau .= '
            <tr>
                <td colspan="33">&nbsp;</td>
            </tr>
            <tr>
                <th>Total mois</th>
                <th class="right">' . $ficelle->formatNumber($totalAlimCB) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalAlimVirement) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalAlimPrelevement) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalVirementUnilend_bienvenue) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalRembEmprunteur) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalVirementEmprunteur) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalVirementCommissionUnilendEmprunteur) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalCommission) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalPrelevements_obligatoires) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalRetenues_source) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalCsg) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalPrelevements_sociaux) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalContributions_additionnelles) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalPrelevements_solidarite) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalCrds) . '</th>
                <th class="right">' . $ficelle->formatNumber(abs($totalRetraitPreteur)) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalSommeMouvements) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalNewsoldeDeLaVeille) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalNewSoldeReel) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalEcartSoldes) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalSoldePromotion) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalSoldeSFFPME) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalSoldeAdminFiscal) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalOffrePromo) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalOctroi_pret) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalCapitalPreteur) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalInteretNetPreteur) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalAffectationEchEmpr) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalEcartMouvInternes) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalVirementsOK) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalVirementsAttente) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalAdminFiscalVir) . '</th>
                <th class="right">' . $ficelle->formatNumber($totaladdsommePrelev) . '</th>
            </tr>
        </table>';

            $table = [
                1  => ['name' => 'totalAlimCB', 'val' => $totalAlimCB],
                2  => ['name' => 'totalAlimVirement', 'val' => $totalAlimVirement],
                3  => ['name' => 'totalAlimPrelevement', 'val' => $totalAlimPrelevement],
                4  => ['name' => 'totalRembEmprunteur', 'val' => $totalRembEmprunteur],
                5  => ['name' => 'totalVirementEmprunteur', 'val' => $totalVirementEmprunteur],
                6  => ['name' => 'totalVirementCommissionUnilendEmprunteur', 'val' => $totalVirementCommissionUnilendEmprunteur],
                7  => ['name' => 'totalCommission', 'val' => $totalCommission],
                8  => ['name' => 'totalPrelevements_obligatoires', 'val' => $totalPrelevements_obligatoires],
                9  => ['name' => 'totalRetenues_source', 'val' => $totalRetenues_source],
                10 => ['name' => 'totalCsg', 'val' => $totalCsg],
                11 => ['name' => 'totalPrelevements_sociaux', 'val' => $totalPrelevements_sociaux],
                12 => ['name' => 'totalContributions_additionnelles', 'val' => $totalContributions_additionnelles],
                13 => ['name' => 'totalPrelevements_solidarite', 'val' => $totalPrelevements_solidarite],
                14 => ['name' => 'totalCrds', 'val' => $totalCrds],
                15 => ['name' => 'totalRetraitPreteur', 'val' => $totalRetraitPreteur],
                16 => ['name' => 'totalSommeMouvements', 'val' => $totalSommeMouvements],
                17 => ['name' => 'totalNewsoldeDeLaVeille', 'val' => $totalNewsoldeDeLaVeille],
                18 => ['name' => 'totalNewSoldeReel', 'val' => $totalNewSoldeReel],
                19 => ['name' => 'totalEcartSoldes', 'val' => $totalEcartSoldes],
                20 => ['name' => 'totalOctroi_pret', 'val' => $totalOctroi_pret],
                21 => ['name' => 'totalCapitalPreteur', 'val' => $totalCapitalPreteur],
                22 => ['name' => 'totalInteretNetPreteur', 'val' => $totalInteretNetPreteur],
                23 => ['name' => 'totalEcartMouvInternes', 'val' => $totalEcartMouvInternes],
                24 => ['name' => 'totalVirementsOK', 'val' => $totalVirementsOK],
                25 => ['name' => 'totalVirementsAttente', 'val' => $totalVirementsAttente],
                26 => ['name' => 'totaladdsommePrelev', 'val' => $totaladdsommePrelev],
                27 => ['name' => 'totalSoldeSFFPME', 'val' => $totalSoldeSFFPME],
                28 => ['name' => 'totalSoldeAdminFiscal', 'val' => $totalSoldeAdminFiscal],
                29 => ['name' => 'totalAdminFiscalVir', 'val' => $totalAdminFiscalVir],
                30 => ['name' => 'totalAffectationEchEmpr', 'val' => $totalAffectationEchEmpr],
                31 => ['name' => 'totalVirementUnilend_bienvenue', 'val' => $totalVirementUnilend_bienvenue],
                32 => ['name' => 'totalSoldePromotion', 'val' => $totalSoldePromotion],
                33 => ['name' => 'totalOffrePromo', 'val' => $totalOffrePromo]
            ];

            $dailyState->createEtat_quotidient($table, $dateTime->format('m'), $dateTime->format('Y'));

            $oldDate           = mktime(0, 0, 0, 12, date('d', $time), $dateTime->format('Y') - 1);
            $oldDate           = date('Y-m', $oldDate);
            $etat_quotidienOld = $dailyState->getTotauxbyMonth($oldDate);

            if ($etat_quotidienOld != false) {
                $soldeDeLaVeille      = $etat_quotidienOld['totalNewsoldeDeLaVeille'];
                $soldeReel            = $etat_quotidienOld['totalNewSoldeReel'];
                $soldeSFFPME_old      = $etat_quotidienOld['totalSoldeSFFPME'];
                $soldeAdminFiscal_old = $etat_quotidienOld['totalSoldeAdminFiscal'];
                $soldePromotion_old   = isset($etat_quotidienOld['totalSoldePromotion']) ? $etat_quotidienOld['totalSoldePromotion'] : 0;
            } else {
                $soldeDeLaVeille      = 0;
                $soldeReel            = 0;
                $soldeSFFPME_old      = 0;
                $soldeAdminFiscal_old = 0;
                $soldePromotion_old   = 0;
            }

            $oldecart = $soldeDeLaVeille - $soldeReel;

            $tableau .= '
        <table border="0" cellpadding="0" cellspacing="0" style=" background-color:#fff; font:11px/13px Arial, Helvetica, sans-serif; color:#000;width: 2500px;">
            <tr>
                <th colspan="34" style="font:italic 18px Arial, Helvetica, sans-serif; text-align:center;">&nbsp;</th>
            </tr>
            <tr>
                <th colspan="34" style="height:35px;font:italic 18px Arial, Helvetica, sans-serif; text-align:center;">UNILEND</th>
            </tr>
            <tr>
                <th rowspan="2">' . $dateTime->format('Y') . '</th>
                <th colspan="3">Chargements compte pr&ecirc;teurs</th>
                <th>Chargements offres</th>
                <th>Echeances<br />Emprunteur</th>
                <th>Octroi pr&ecirc;t</th>
                <th>Commissions<br />octroi pr&ecirc;t</th>
                <th>Commissions<br />restant &ucirc;</th>
                <th colspan="7">Retenues fiscales</th>
                <th>Remboursements<br />aux pr&ecirc;teurs</th>
                <th>&nbsp;</th>
                <th colspan="6">Soldes</th>
                <th colspan="6">Mouvements internes</th>
                <th colspan="3">Virements</th>
                <th>Pr&eacute;l&egrave;vements</th>
            </tr>
            <tr>';

            foreach ($aColumns as $key => $value) {
                $tableau .= '<td class="center">' . $value . '</td>';
            }

            $tableau .= '
            </tr>
            <tr>
                <td colspan="18">D&eacute;but d\'ann&eacute;e</td>
                <td class="right">' . $ficelle->formatNumber($soldeDeLaVeille) . '</td>
                <td class="right">' . $ficelle->formatNumber($soldeReel) . '</td>
                <td class="right">' . $ficelle->formatNumber($oldecart) . '</td>
                <td class="right">' . $ficelle->formatNumber($soldePromotion_old) . '</td>
                <td class="right">' . $ficelle->formatNumber($soldeSFFPME_old) . '</td>
                <td class="right">' . $ficelle->formatNumber($soldeAdminFiscal_old) . '</td>
                <td colspan="10">&nbsp;</td>
            </tr>';

            foreach ($aColumns as $sKey => $value) {
                $$sKey = 0;
            }

            for ($i = 1; $i <= 12; $i++) {
                if (strlen($i) < 2) {
                    $numMois = '0' . $i;
                } else {
                    $numMois = $i;
                }

                $lemois = $dailyState->getTotauxbyMonth($dateTime->format('Y') . '-' . $numMois);

                if (false === empty($lemois)) {

                    foreach ($lemois as $key => $value) {
                        if (false === in_array($key, array('totalNewsoldeDeLaVeille', 'totalNewSoldeReel', 'totalEcartSoldes', 'totalSoldeSFFPME', 'totalSoldeAdminFiscal', 'totalSoldePromotion'))) {
                            $$key += $value;
                        } else {
                            $$key = $value;
                        }
                    }
                }

                $tableau .= '
                <tr>
                    <th>' . $dates->tableauMois['fr'][$i] . '</th>';

                if (false === empty($lemois)) {
                    foreach ($aColumns as $key => $value) {
                        if ('totalRetraitPreteur' === $key) {
                            $amount = abs(isset($lemois[$key]) ? $lemois[$key] : 0);
                        } else {
                            $amount = isset($lemois[$key]) ? $lemois[$key] : 0;
                        }
                        $tableau .= '<td class="right">' . $ficelle->formatNumber($amount) . '</td>';
                    }
                } else {
                    for ($index = 0; $index++; $index < 33) {
                        $tableau .= '<td>&nbsp;</td>';
                    }
                }

                $tableau .= '</tr>';
            }
            $tableau .= '<tr>
                <th>Total ann&eacute;e</th>';
            foreach ($aColumns as $key => $value) {
                if ('totalRetraitPreteur' === $key) {
                    $amount = abs($$key);
                } else {
                    $amount = $$key;
                }
                $tableau .= '<th class="right">' . $ficelle->formatNumber($amount) . '</th>';
            }
            $tableau .= '
            </tr>
        </table>';

            file_put_contents($this->getContainer()->getParameter('path.sftp') . 'sfpmei/emissions/etat_quotidien/Unilend_etat_' . date('Ymd', $time) . '.xls', $tableau);

            /** @var \settings $oSettings */
            $oSettings = $entityManager->getRepository('settings');
            $oSettings->get('Adresse notification etat quotidien', 'type');

            /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
            $message = $this->getContainer()->get('unilend.swiftmailer.message_provider')->newMessage('notification-etat-quotidien', [], false);
            $message
                ->setTo(explode(';', trim($oSettings->value)))
                ->attach(\Swift_Attachment::fromPath($this->getContainer()->getParameter('path.sftp') . 'sfpmei/emissions/etat_quotidien/Unilend_etat_' . date('Ymd', $time) . '.xls'));

            /** @var \Swift_Mailer $mailer */
            $mailer = $this->getContainer()->get('mailer');
            $mailer->send($message);
        } catch (\Exception $exception) {
            /** @var LoggerInterface $logger */
            $logger = $this->getContainer()->get('monolog.logger.console');
            $logger->error('An error occured while generating daily state at line : ' . $exception->getLine() . '. Error message : ' . $exception->getMessage(), array('class' => __CLASS__, 'function' => __FUNCTION__));
        }
    }

    /**
     * @return array
     */
    private function getColumns()
    {
        return [
            'totalAlimCB'                              => 'Carte<br />bancaire',
            'totalAlimVirement'                        => 'Virement',
            'totalAlimPrelevement'                     => 'Pr&eacute;l&egrave;vement',
            'totalVirementUnilend_bienvenue'           => 'Virement',
            'totalRembEmprunteur'                      => 'Pr&eacute;l&egrave;vement',
            'totalVirementEmprunteur'                  => 'Virement',
            'totalVirementCommissionUnilendEmprunteur' => 'Virement',
            'totalCommission'                          => 'Virement',
            'totalPrelevements_obligatoires'           => 'Pr&eacute;l&egrave;vements<br />obligatoires',
            'totalRetenues_source'                     => 'Retenues &agrave; la<br />source',
            'totalCsg'                                 => 'CSG',
            'totalPrelevements_sociaux'                => 'Pr&eacute;l&egrave;vements<br />sociaux',
            'totalContributions_additionnelles'        => 'Contributions<br />additionnelles',
            'totalPrelevements_solidarite'             => 'Pr&eacute;l&egrave;vements<br />solidarit&eacute;',
            'totalCrds'                                => 'CRDS',
            'totalRetraitPreteur'                      => 'Virement',
            'totalSommeMouvements'                     => 'Total<br />mouvements',
            'totalNewsoldeDeLaVeille'                  => 'Solde<br />th&eacute;orique',
            'totalNewSoldeReel'                        => 'Solde<br />r&eacute;el',
            'totalEcartSoldes'                         => 'Ecart<br />global',
            'totalSoldePromotion'                      => 'Solde<br />Promotions',
            'totalSoldeSFFPME'                         => 'Solde<br />SFF PME',
            'totalSoldeAdminFiscal'                    => 'Solde Admin.<br>Fiscale',
            'totalOffrePromo'                          => 'Offre promo',
            'totalOctroi_pret'                         => 'Octroi pr&ecirc;t',
            'totalCapitalPreteur'                      => 'Retour pr&ecirc;teur<br />(Capital)',
            'totalInteretNetPreteur'                   => 'Retour pr&ecirc;teur<br />(Int&eacute;r&ecirc;ts nets)',
            'totalAffectationEchEmpr'                  => 'Affectation<br />Ech. Empr.',
            'totalEcartMouvInternes'                   => 'Ecart<br />fiscal',
            'totalVirementsOK'                         => 'Fichier<br />virements',
            'totalVirementsAttente'                    => 'Dont<br />SFF PME',
            'totalAdminFiscalVir'                      => 'Administration<br />Fiscale',
            'totaladdsommePrelev'                      => 'Fichier<br />pr&eacute;l&egrave;vements',
        ];
    }

    /**
     * @param array $a
     * @param array $b
     * @return array
     */
    private function combineTransactionTypes(array $a, array $b)
    {
        foreach (array_intersect_key($a, $b) as $date => $row) {
            $a[$date]['montant']         = bcadd($a[$date]['montant'], $b[$date]['montant'], 2);
            $a[$date]['montant_unilend'] = bcadd($a[$date]['montant_unilend'], $b[$date]['montant_unilend'], 2);
            $a[$date]['montant_etat']    = bcadd($a[$date]['montant_etat'], $b[$date]['montant_etat'], 2);
        }
        return $a + $b;
    }

    /**
     * @param int $time
     * @return array
     */
    private function getMonthDays($time)
    {
        $monthDays = [];
        $nbDays    = date('t', $time);
        $date      = (new \DateTime())->setTimestamp($time);
        $data      = [
            'montant'         => 0,
            'montant_unilend' => 0,
            'montant_etat'    => 0
        ];
        $di        = new \DateInterval('P1D');
        $i         = 1;

        while ($i <= $nbDays) {
            $monthDays[$date->format('Y-m-d')] = $data;
            $date->add($di);
            $i++;
        }
        return $monthDays;
    }

    /**
     * @param array $transactions
     * @param string $date
     * @return string
     */
    private function getRealSold(array $transactions, $date)
    {
        $sold     = 0;
        $realType = array(
            \transactions_types::TYPE_LENDER_SUBSCRIPTION,
            \transactions_types::TYPE_LENDER_CREDIT_CARD_CREDIT,
            \transactions_types::TYPE_LENDER_BANK_TRANSFER_CREDIT,
            \transactions_types::TYPE_BORROWER_REPAYMENT,
            \transactions_types::TYPE_DIRECT_DEBIT,
            \transactions_types::TYPE_LENDER_WITHDRAWAL,
            \transactions_types::TYPE_BORROWER_REPAYMENT_REJECTION,
            \transactions_types::TYPE_UNILEND_WELCOME_OFFER_BANK_TRANSFER,
            \transactions_types::TYPE_BORROWER_ANTICIPATED_REPAYMENT,
            \transactions_types::TYPE_REGULATION_BANK_TRANSFER,
            \transactions_types::TYPE_RECOVERY_BANK_TRANSFER
        );

        foreach ($realType as $transactionType) {
            if (isset($transactions[$transactionType][$date])) {
                $sold =
                    bcadd(
                        $sold,
                        $transactions[$transactionType][$date]['montant'],
                        2
                    );
            }
        }
        return $sold;
    }

    /**
     * @param array $transactions
     * @param string $date
     * @return string
     */
    private function getUnilendRealSold(array $transactions, $date)
    {
        $sold = 0;
        if (isset($transactions[\transactions_types::TYPE_BORROWER_BANK_TRANSFER_CREDIT][$date])) {
            $sold =
                bcsub(
                    $transactions[\transactions_types::TYPE_BORROWER_BANK_TRANSFER_CREDIT][$date]['montant'],
                    $transactions[\transactions_types::TYPE_BORROWER_BANK_TRANSFER_CREDIT][$date]['montant_unilend'],
                    2
                );
        }
        return $sold;
    }

    /**
     * @param array $transactions
     * @param $date
     * @return string
     */
    private function getStateRealSold(array $transactions, $date)
    {
        $sold = 0;
        if (isset($transactions[\transactions_types::TYPE_UNILEND_REPAYMENT][$date])) {
            $sold = $transactions[\transactions_types::TYPE_UNILEND_REPAYMENT][$date]['montant_etat'];
        }
        return $sold;
    }
}
