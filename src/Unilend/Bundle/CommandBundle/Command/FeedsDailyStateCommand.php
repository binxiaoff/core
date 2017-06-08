<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\DailyStateBalanceHistory;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Virements;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;

class FeedsDailyStateCommand extends ContainerAwareCommand
{
    const ROW_HEIGHT     = 20;
    const COLUMN_WIDTH   = 16;
    const DAY_HEADER_ROW = 2;

    const DATE_COLUMN                           = 'A';
    const LENDER_PROVISION_CARD_COLUMN          = 'B';
    const LENDER_PROVISION_WIRE_TRANSFER_COLUMN = 'C';
    const LENDER_PROVISION_DIRECT_DEBIT_COLUMN  = 'D';
    const PROMOTION_OFFER_PROVISION_COLUMN      = 'E';
    const BORROWER_PROVISION_COLUMN             = 'F';
    const BORROWER_WITHDRAW_COLUMN              = 'G';
    const PROJECT_COMMISSION_COLUMN             = 'H';
    const REPAYMENT_COMMISSION_COLUMN           = 'I';
    const STATUTORY_CONTRIBUTIONS_COLUMN        = 'J';
    const INCOME_TAX_COLUMN                     = 'K';
    const CSG_COLUMN                            = 'L';
    const SOCIAL_DEDUCTIONS_COLUMN              = 'M';
    const ADDITIONAL_CONTRIBUTIONS_COLUMN       = 'N';
    const SOLIDARITY_DEDUCTIONS_COLUMN          = 'O';
    const CRDS_COLUMN                           = 'P';
    const LENDER_WITHDRAW_COLUMN                = 'Q';
    const TOTAL_FINANCIAL_MOVEMENTS_COLUMN      = 'R';
    const THEORETICAL_BALANCE_COLUMN            = 'S';
    const BALANCE_COLUMN                        = 'T';
    const BALANCE_DIFFERENCE_COLUMN             = 'U';
    const UNILEND_PROMOTIONAL_BALANCE_COLUMN    = 'V';
    const UNILEND_BALANCE_COLUMN                = 'W';
    const TAX_BALANCE_COLUMN                    = 'X';
    const PROMOTION_OFFER_DISTRIBUTION_COLUMN   = 'Y';
    const LENDER_LOAN_COLUMN                    = 'Z';
    const CAPITAL_REPAYMENT_COLUMN              = 'AA';
    const NET_INTEREST_COLUMN                   = 'AB';
    const PAYMENT_ASSIGNMENT_COLUMN             = 'AC';
    const FISCAL_DIFFERENCE_COLUMN              = 'AD';
    const WIRE_TRANSFER_OUT_COLUMN              = 'AE';
    const UNILEND_WITHDRAW_COLUMN               = 'AF';
    const TAX_WITHDRAW_COLUMN                   = 'AG';
    const DIRECT_DEBIT_COLUMN                   = 'AH';
    const LAST_COLUMN                           = self::DIRECT_DEBIT_COLUMN;

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
        $date = $input->getArgument('day');

        if (false === empty($date)) {
            if (preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $date)) {
                $requestedDate = \DateTime::createFromFormat('Y-m-d', $date);
                if (null === $requestedDate) {
                    $output->writeln('<error>Wrong date format ("Y-m-d" expected)</error>');
                    return;
                }
            } else {
                $output->writeln('<error>Wrong date format ("Y-m-d" expected)</error>');
                return;
            }
        } else {
            $requestedDate = new \DateTime('yesterday');
        }

        $firstDay = new \DateTime('first day of ' . $requestedDate->format('F Y'));

        /** @var \PHPExcel $document */
        $document = new \PHPExcel();
        $document->getDefaultStyle()->getFont()->setName('Arial');
        $document->getDefaultStyle()->getFont()->setSize(11);
        $activeSheet = $document->setActiveSheetIndex(0);

        $specificRows['headerStartDay'] = 1;
        $this->addHeaders($activeSheet, $requestedDate, $specificRows['headerStartDay']);

        $maxCoordinates                 = $activeSheet->getHighestRowAndColumn();
        $specificRows['previousMonth']  = $maxCoordinates['row'];
        $specificRows['firstDay']       = $specificRows['previousMonth'] + 1;
        $specificRows['coordinatesDay'] = $this->addDates($activeSheet, $firstDay, $requestedDate, $specificRows['firstDay']);

        $maxCoordinates = $activeSheet->getHighestRowAndColumn();
        $separationRow  = $maxCoordinates['row'] + 1;
        $activeSheet->mergeCells(self::DATE_COLUMN . $separationRow . ':' . $maxCoordinates['column'] . $separationRow);
        $specificRows['totalDay'] = $separationRow + 1;
        $separationRow            = $specificRows['totalDay'] + 1;
        $activeSheet->mergeCells(self::DATE_COLUMN . $separationRow . ':' . $maxCoordinates['column'] . $separationRow);
        $specificRows['headerStartMonth'] = $separationRow + 1;

        $this->addHeaders($activeSheet, $requestedDate, $specificRows['headerStartMonth']);
        $maxCoordinates                   = $activeSheet->getHighestRowAndColumn();
        $specificRows['previousYear']     = $maxCoordinates['row'];
        $specificRows['firstMonth']       = $specificRows['previousYear'] + 1;
        $specificRows['coordinatesMonth'] = $this->addMonths($activeSheet, $requestedDate, $specificRows['firstMonth']);

        $separationRow  = $specificRows['firstMonth'] + 12;
        $activeSheet->mergeCells(self::DATE_COLUMN . $separationRow . ':' . $maxCoordinates['column'] . $separationRow);
        $specificRows['totalMonth'] = $specificRows['firstMonth'] + 13;
        $this->applyStyleToWorksheet($activeSheet, $specificRows);

        $this->addMovementData($activeSheet, $firstDay, $requestedDate, $specificRows);
        $this->addBalanceData($activeSheet, $firstDay, $requestedDate, $specificRows);
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
            OperationType::BORROWER_PROVISION_CANCEL,
            OperationType::BORROWER_WITHDRAW,
            OperationType::BORROWER_COMMISSION,
            OperationType::LENDER_PROVISION,
            OperationType::LENDER_WITHDRAW,
            OperationType::LENDER_LOAN,
            OperationType::CAPITAL_REPAYMENT,
            OperationType::GROSS_INTEREST_REPAYMENT,
            OperationType::UNILEND_PROMOTIONAL_OPERATION_PROVISION,
            OperationType::UNILEND_PROMOTIONAL_OPERATION,
            OperationType::UNILEND_PROMOTIONAL_OPERATION_CANCEL,
            OperationType::UNILEND_LENDER_REGULARIZATION,
            OperationType::UNILEND_BORROWER_REGULARIZATION,
            OperationType::UNILEND_PROVISION,
            OperationType::UNILEND_BORROWER_COMMERCIAL_GESTURE
        ], OperationType::TAX_TYPES_FR);

        $dailyMovements   = $operationRepository->sumMovementsForDailyState($firstDay, $requestedDate, $movements);
        $monthlyMovements = $operationRepository->sumMovementsForDailyStateByMonth($requestedDate, $movements);

        $this->addMovementLines($activeSheet, $dailyMovements, $specificRows['firstDay'], $specificRows['totalDay']);
        $this->addMovementLines($activeSheet, $monthlyMovements, $specificRows['firstMonth'], $specificRows['totalMonth']);
    }

    /**
     * @param \PHPExcel_Worksheet $activeSheet
     * @param \DateTime           $firstDay
     * @param \DateTime           $requestedDate
     * @param array               $specificRows
     */
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
            'taxes'       => $operationRepository->sumMovementsForDailyStateByMonth($requestedDate, $taxWithdrawTypes),
            'directDebit' => $directDebitRepository->sumDirectDebitByMonth($requestedDate->format('Y'))
        ];
        $this->addWireTransferLines($activeSheet, $wireTransfersMonth, $specificRows['totalMonth'], $specificRows['coordinatesMonth']);
    }

    /**
     * @param \PHPExcel_Worksheet $activeSheet
     * @param \DateTime           $firstDay
     * @param \DateTime           $requestedDate
     * @param array               $specificRows
     */
    private function addBalanceData(\PHPExcel_Worksheet $activeSheet, \DateTime $firstDay, \DateTime $requestedDate, array $specificRows)
    {
        $entityManager                  = $this->getContainer()->get('doctrine.orm.entity_manager');
        $walletBalanceHistoryRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:WalletBalanceHistory');

        $previousDay                    = $firstDay->sub(\DateInterval::createFromDateString('1 day'));
        $previousBalanceHistory         = $entityManager->getRepository('UnilendCoreBusinessBundle:DailyStateBalanceHistory')->findOneBy(['date' => $previousDay->format('Y-m-d')]);
        $this->addBalanceLine($activeSheet, $previousBalanceHistory, $specificRows['previousMonth'], $specificRows);

        foreach ($specificRows['coordinatesDay'] as $date => $row) {
            $dateTime = \DateTime::createFromFormat('Y-m-d', $date);
            $dateTime->setTime(0, 0, 0);
            $requestedDate->setTime(0, 0, 0);
            if ($dateTime > $requestedDate) {
                continue;
            }

            $balanceHistory = $entityManager->getRepository('UnilendCoreBusinessBundle:DailyStateBalanceHistory')->findOneBy(['date' => $date]);
            if (null === $balanceHistory) {
                $balanceDate = ($date == $requestedDate->format('Y-m-d')) ? $requestedDate : $dateTime;

                $balanceHistory = new DailyStateBalanceHistory();
                $balanceHistory->setLenderBorrowerBalance($walletBalanceHistoryRepository->sumBalanceForDailyState($balanceDate, [WalletType::LENDER, WalletType::BORROWER]));
                $balanceHistory->setUnilendBalance($walletBalanceHistoryRepository->sumBalanceForDailyState($balanceDate, [WalletType::UNILEND]));
                $balanceHistory->setUnilendPromotionalBalance($walletBalanceHistoryRepository->sumBalanceForDailyState($balanceDate, [WalletType::UNILEND_PROMOTIONAL_OPERATION]));
                $balanceHistory->setTaxBalance($walletBalanceHistoryRepository->sumBalanceForDailyState($balanceDate, WalletType::TAX_FR_WALLETS));
                $balanceHistory->setDate($balanceDate->format('Y-m-d'));

                $entityManager->persist($balanceHistory);
                $entityManager->flush($balanceHistory);
            }
            $this->addBalanceLine($activeSheet, $balanceHistory, $row, $specificRows);

            if ($date === $requestedDate->format('Y-m-d')) {
                $this->addBalanceLine($activeSheet, $balanceHistory, $specificRows['totalDay'], $specificRows, true);
            }
        }

        $previousYear = new \DateTime('Last day of december ' . $requestedDate->format('Y'));
        $previousYear->sub(\DateInterval::createFromDateString('1 year'));
        $previousBalanceHistory = $entityManager->getRepository('UnilendCoreBusinessBundle:DailyStateBalanceHistory')->findOneBy(['date' => $previousYear->format('Y-m-d')]);
        $this->addBalanceLine($activeSheet, $previousBalanceHistory, $specificRows['previousYear'], $specificRows);

        foreach ($specificRows['coordinatesMonth'] as $month => $row) {
            $lastDayOfMonth = \DateTime::createFromFormat('n-Y', $month . '-' . $requestedDate->format('Y'));
            if ($month <= $requestedDate->format('n')) {
                if ($month == $requestedDate->format('n')) {
                    $balanceHistory = $entityManager->getRepository('UnilendCoreBusinessBundle:DailyStateBalanceHistory')->findOneBy(['date' => $requestedDate->format('Y-m-d')]);
                    $this->addBalanceLine($activeSheet, $balanceHistory, $row, $specificRows, true);
                }
                $balanceHistory = $entityManager->getRepository('UnilendCoreBusinessBundle:DailyStateBalanceHistory')->findOneBy(['date' => $lastDayOfMonth->format('Y-m-t')]);
                if (null !== $balanceHistory) {
                    $this->addBalanceLine($activeSheet, $balanceHistory, $row, $specificRows);
                }
            }
        }
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

        $activeSheet->mergeCells(self::DATE_COLUMN . $startRow . ':' . self::LAST_COLUMN . $startRow)
            ->setCellValue(self::DATE_COLUMN . $startRow, 'UNILEND');

        if (2 == $mainSectionRow) {
            $activeSheet->mergeCells(self::DATE_COLUMN . $mainSectionRow . ':' . self::DATE_COLUMN . $secondarySectionRow)
                ->setCellValue(self::DATE_COLUMN . $mainSectionRow, $date->format('d/m/Y'));
            $activeSheet->mergeCells(self::DATE_COLUMN . $previousBalanceRow . ':' . self::TOTAL_FINANCIAL_MOVEMENTS_COLUMN . $previousBalanceRow)
                ->setCellValue(self::DATE_COLUMN . $previousBalanceRow, 'Début du mois');
        } else {
            $activeSheet->mergeCells(self::DATE_COLUMN . $mainSectionRow . ':' . self::DATE_COLUMN . $secondarySectionRow)
                ->setCellValue(self::DATE_COLUMN . $mainSectionRow, $date->format('Y'));
            $activeSheet->mergeCells(self::DATE_COLUMN . $previousBalanceRow . ':' . self::TOTAL_FINANCIAL_MOVEMENTS_COLUMN . $previousBalanceRow)
                ->setCellValue(self::DATE_COLUMN . $previousBalanceRow, 'Début d\'année');
        }

        $activeSheet->mergeCells(self::LENDER_PROVISION_CARD_COLUMN . $mainSectionRow . ':' . self::LENDER_PROVISION_DIRECT_DEBIT_COLUMN . $mainSectionRow)
            ->setCellValue(self::LENDER_PROVISION_CARD_COLUMN . $mainSectionRow, 'Chargements comptes prêteurs');
        $activeSheet->setCellValue(self::PROMOTION_OFFER_PROVISION_COLUMN . $mainSectionRow, 'Chargement offres');
        $activeSheet->setCellValue(self::BORROWER_PROVISION_COLUMN . $mainSectionRow, 'Echeances Emprunteur');
        $activeSheet->setCellValue(self::BORROWER_WITHDRAW_COLUMN . $mainSectionRow, 'Octroi prêt');
        $activeSheet->setCellValue(self::PROJECT_COMMISSION_COLUMN . $mainSectionRow, 'Commission octroi prêt');
        $activeSheet->setCellValue(self::REPAYMENT_COMMISSION_COLUMN . $mainSectionRow, 'Commission restant dû');
        $activeSheet->mergeCells(self::STATUTORY_CONTRIBUTIONS_COLUMN . $mainSectionRow . ':' . self::CRDS_COLUMN . $mainSectionRow)
            ->setCellValue(self::STATUTORY_CONTRIBUTIONS_COLUMN . $mainSectionRow, 'Retenues fiscales');
        $activeSheet->setCellValue(self::LENDER_WITHDRAW_COLUMN . $mainSectionRow, 'Remboursement aux prêteurs');
        $activeSheet->setCellValue(self::TOTAL_FINANCIAL_MOVEMENTS_COLUMN . $mainSectionRow, '');
        $activeSheet->mergeCells(self::THEORETICAL_BALANCE_COLUMN . $mainSectionRow . ':' . self::TAX_BALANCE_COLUMN . $mainSectionRow)
            ->setCellValue(self::THEORETICAL_BALANCE_COLUMN . $mainSectionRow, 'Soldes');
        $activeSheet->mergeCells(self::PROMOTION_OFFER_DISTRIBUTION_COLUMN . $mainSectionRow . ':' . self::FISCAL_DIFFERENCE_COLUMN . $mainSectionRow)
            ->setCellValue(self::PROMOTION_OFFER_DISTRIBUTION_COLUMN . $mainSectionRow, 'Mouvements internes');
        $activeSheet->mergeCells(self::WIRE_TRANSFER_OUT_COLUMN . $mainSectionRow . ':' . self::TAX_WITHDRAW_COLUMN . $mainSectionRow)
            ->setCellValue(self::WIRE_TRANSFER_OUT_COLUMN . $mainSectionRow, 'Virements');
        $activeSheet->setCellValue(self::DIRECT_DEBIT_COLUMN . $mainSectionRow, 'Prélèvements');
        $activeSheet->setCellValue(self::LENDER_PROVISION_CARD_COLUMN . $secondarySectionRow,'Carte bancaire');
        $activeSheet->setCellValue(self::LENDER_PROVISION_WIRE_TRANSFER_COLUMN . $secondarySectionRow,'Virement');
        $activeSheet->setCellValue(self::LENDER_PROVISION_DIRECT_DEBIT_COLUMN . $secondarySectionRow, 'Prélèvement');
        $activeSheet->setCellValue(self::PROMOTION_OFFER_PROVISION_COLUMN . $secondarySectionRow, 'Virement');
        $activeSheet->setCellValue(self::BORROWER_PROVISION_COLUMN . $secondarySectionRow, 'Virement');
        $activeSheet->setCellValue(self::BORROWER_WITHDRAW_COLUMN . $secondarySectionRow, 'Prélèvement');
        $activeSheet->setCellValue(self::PROJECT_COMMISSION_COLUMN . $secondarySectionRow, 'Virement');
        $activeSheet->setCellValue(self::REPAYMENT_COMMISSION_COLUMN . $secondarySectionRow, 'Virement');
        $activeSheet->setCellValue(self::STATUTORY_CONTRIBUTIONS_COLUMN . $secondarySectionRow, 'Prélèvements obligatoires');
        $activeSheet->setCellValue(self::INCOME_TAX_COLUMN . $secondarySectionRow, 'Retenues à la source');
        $activeSheet->setCellValue(self::CSG_COLUMN . $secondarySectionRow, 'CSG');
        $activeSheet->setCellValue(self::SOCIAL_DEDUCTIONS_COLUMN . $secondarySectionRow, 'Prélèvements sociaux');
        $activeSheet->setCellValue(self::ADDITIONAL_CONTRIBUTIONS_COLUMN . $secondarySectionRow, 'Contributions additionnelles');
        $activeSheet->setCellValue(self::SOLIDARITY_DEDUCTIONS_COLUMN . $secondarySectionRow, 'Prélèvements solidarité');
        $activeSheet->setCellValue(self::CRDS_COLUMN . $secondarySectionRow, 'CRDS');
        $activeSheet->setCellValue(self::LENDER_WITHDRAW_COLUMN . $secondarySectionRow, 'Virement');
        $activeSheet->setCellValue(self::TOTAL_FINANCIAL_MOVEMENTS_COLUMN . $secondarySectionRow, 'Total mouvements');
        $activeSheet->setCellValue(self::THEORETICAL_BALANCE_COLUMN . $secondarySectionRow, 'Solde théorique');
        $activeSheet->setCellValue(self::BALANCE_COLUMN . $secondarySectionRow, 'Solde réel');
        $activeSheet->setCellValue(self::BALANCE_DIFFERENCE_COLUMN . $secondarySectionRow, 'Ecart global');
        $activeSheet->setCellValue(self::UNILEND_PROMOTIONAL_BALANCE_COLUMN . $secondarySectionRow, 'Solde Promotions');
        $activeSheet->setCellValue(self::UNILEND_BALANCE_COLUMN . $secondarySectionRow, 'Solde Unilend');
        $activeSheet->setCellValue(self::TAX_BALANCE_COLUMN . $secondarySectionRow, 'Solde Admin. Fiscale');
        $activeSheet->setCellValue(self::PROMOTION_OFFER_DISTRIBUTION_COLUMN . $secondarySectionRow, 'Offre promo');
        $activeSheet->setCellValue(self::LENDER_LOAN_COLUMN . $secondarySectionRow, 'Octroi prêt');
        $activeSheet->setCellValue(self::CAPITAL_REPAYMENT_COLUMN . $secondarySectionRow, 'Retour prêteur (Capital)');
        $activeSheet->setCellValue(self::NET_INTEREST_COLUMN . $secondarySectionRow, 'Retour prêteur (Intêréts nets)');
        $activeSheet->setCellValue(self::PAYMENT_ASSIGNMENT_COLUMN . $secondarySectionRow, 'Affectation Ech. Empr.');
        $activeSheet->setCellValue(self::FISCAL_DIFFERENCE_COLUMN . $secondarySectionRow, 'Ecart fiscal');
        $activeSheet->setCellValue(self::WIRE_TRANSFER_OUT_COLUMN . $secondarySectionRow, 'Fichier virements');
        $activeSheet->setCellValue(self::UNILEND_WITHDRAW_COLUMN . $secondarySectionRow, 'Dont SFF PME');
        $activeSheet->setCellValue(self::TAX_WITHDRAW_COLUMN . $secondarySectionRow, 'Administration Fiscale');
        $activeSheet->setCellValue(self::DIRECT_DEBIT_COLUMN . $secondarySectionRow, 'Fichier prélèvements');
        $activeSheet->mergeCells(self::PROMOTION_OFFER_DISTRIBUTION_COLUMN. $previousBalanceRow . ':' . self::LAST_COLUMN . $previousBalanceRow);
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
        $dayInterval    = \DateInterval::createFromDateString('1 day');
        $month          = new \DatePeriod($firstDay, $dayInterval, $lastDay);
        $coordinatesDay = [];

        /** @var \DateTime $day */
        foreach ($month as $day) {
            $activeSheet->setCellValueExplicit(self::DATE_COLUMN . $row, \PHPExcel_Shared_Date::PHPToExcel($day), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->getStyle(self::DATE_COLUMN . $row)->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY);
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
            $activeSheet->setCellValue(self::DATE_COLUMN . $row, strftime('%B', $month->getTimestamp()));
            $activeSheet->getStyle(self::DATE_COLUMN . $row)->getFont()->setBold(true);
            $coordinatesMonth[$month->format('n')] = $row;
            $row++;
        }

        return $coordinatesMonth;
    }

    /**
     * @param \PHPExcel_Worksheet $activeSheet
     * @param array               $specificRows
     */
    private function applyStyleToWorksheet(\PHPExcel_Worksheet $activeSheet, array $specificRows)
    {
        $style = [
            'borders' => [
                'allborders' => [
                    'style' => \PHPExcel_Style_Border::BORDER_MEDIUM,
                    'color' => ['argb' => \PHPExcel_Style_Color::COLOR_BLACK]
                ]
            ]
        ];

        $activeSheet->getDefaultColumnDimension()->setWidth(self::COLUMN_WIDTH);
        for ($i = 1; $i <= $specificRows['totalMonth']; $i++) {
            $activeSheet->getRowDimension($i)->setRowHeight(self::ROW_HEIGHT);
        }
        $activeSheet->getStyle(self::DATE_COLUMN . 1 . ':' . self::LAST_COLUMN . $specificRows['totalMonth'])->applyFromArray($style);
        $activeSheet->getStyle(self::LENDER_PROVISION_CARD_COLUMN . 2 . ':' . self::LAST_COLUMN . $specificRows['totalMonth'])
            ->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
        $activeSheet->getStyle(self::DATE_COLUMN . 1 . ':' . self::LAST_COLUMN . $specificRows['totalMonth'])
            ->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);

        $this->formatHeader($activeSheet, $specificRows['headerStartDay']);
        $this->formatHeader($activeSheet, $specificRows['headerStartMonth']);

        $activeSheet->getStyle(self::DATE_COLUMN . $specificRows['totalMonth'] . ':' . self::LAST_COLUMN . $specificRows['totalMonth'])->getFont()->setBold(true);
        $activeSheet->getStyle(self::DATE_COLUMN . $specificRows['totalDay'] . ':' . self::LAST_COLUMN . $specificRows['totalDay'])->getFont()->setBold(true);

        $activeSheet->setCellValue(self::DATE_COLUMN . $specificRows['totalDay'], 'Total mois');
        $activeSheet->setCellValue(self::DATE_COLUMN . $specificRows['totalMonth'], 'Total année');
    }

    /**
     * @param \PHPExcel_Worksheet $activeSheet
     * @param int                 $headerStartRow
     */
    private function formatHeader(\PHPExcel_Worksheet $activeSheet, $headerStartRow)
    {
        $activeSheet->getStyle(self::DATE_COLUMN . $headerStartRow . ':' . self::LAST_COLUMN . $headerStartRow)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $activeSheet->getStyle(self::DATE_COLUMN . $headerStartRow)->getFont()->setBold(true)->setSize(18)->setItalic(true);

        $mainSectionRow      = $headerStartRow + 1;
        $secondarySectionRow = $mainSectionRow + 1;

        $activeSheet->getStyle(self::DATE_COLUMN . $mainSectionRow . ':' . self::LAST_COLUMN . $mainSectionRow)->getFont()->setBold(true);
        $activeSheet->getStyle(self::DATE_COLUMN . $mainSectionRow . ':' . self::LAST_COLUMN . $mainSectionRow)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $activeSheet->getRowDimension($mainSectionRow)->setRowHeight(self::ROW_HEIGHT * 2);
        $activeSheet->getRowDimension($secondarySectionRow)->setRowHeight(self::ROW_HEIGHT * 2);

        $activeSheet->getStyle(self::LENDER_PROVISION_CARD_COLUMN . $mainSectionRow . ':' . self::LAST_COLUMN . $secondarySectionRow)->getAlignment()->setWrapText(true);
    }

    /**
     * @param \PHPExcel_Worksheet $activeSheet
     * @param array               $movements
     * @param int                 $row
     * @param int                 $totalRow
     */
    private function addMovementLines(\PHPExcel_Worksheet $activeSheet, array $movements, $row, $totalRow)
    {
        $calculatedTotals = [
            'financialMovements'    => 0,
            'netInterest'           => 0,
            'repaymentAssignment'   => 0,
            'fiscalDifference'      => 0,
            'realBorrowerProvision' => 0,
            'promotionProvision'    => 0,
            'promotionalOffer'      => 0
        ];

        foreach ($movements as $date => $line) {
            $lenderProvisionCreditCard   = empty($line['lender_provision_credit_card']) ? 0 : $line['lender_provision_credit_card'];
            $lenderProvisionWireTransfer = empty($line['lender_provision_wire_transfer_in']) ? 0 : $line['lender_provision_wire_transfer_in'];
            $promotionProvision          = empty($line[OperationType::UNILEND_PROMOTIONAL_OPERATION_PROVISION]) ? 0 : $line[OperationType::UNILEND_PROMOTIONAL_OPERATION_PROVISION];
            $unilendProvision            = empty($line[OperationType::UNILEND_PROVISION]) ? 0 : $line[OperationType::UNILEND_PROVISION];
            $borrowerProvision           = empty($line[OperationType::BORROWER_PROVISION]) ? 0 : $line[OperationType::BORROWER_PROVISION];
            $borrowerProvisionCancel     = empty($line[OperationType::BORROWER_PROVISION_CANCEL]) ? 0 : $line[OperationType::BORROWER_PROVISION_CANCEL];
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
            $commercialGestures          = empty($line[OperationType::UNILEND_BORROWER_COMMERCIAL_GESTURE]) ? 0 : $line[OperationType::UNILEND_BORROWER_COMMERCIAL_GESTURE];
            $lenderRegularization        = empty($line[OperationType::UNILEND_LENDER_REGULARIZATION]) ? 0 : $line[OperationType::UNILEND_LENDER_REGULARIZATION];
            $borrowerRegularization      = empty($line[OperationType::UNILEND_BORROWER_REGULARIZATION]) ? 0 : $line[OperationType::UNILEND_BORROWER_REGULARIZATION];
            $promotionalOffersCancel     = empty($line[OperationType::UNILEND_PROMOTIONAL_OPERATION_CANCEL]) ? 0 : $line[OperationType::UNILEND_PROMOTIONAL_OPERATION_CANCEL];
            $loans                       = empty($line[OperationType::LENDER_LOAN]) ? 0 : $line[OperationType::LENDER_LOAN];
            $capitalRepayment            = empty($line[OperationType::CAPITAL_REPAYMENT]) ? 0 : $line[OperationType::CAPITAL_REPAYMENT];
            $grossInterest               = empty($line[OperationType::GROSS_INTEREST_REPAYMENT]) ? 0 : $line[OperationType::GROSS_INTEREST_REPAYMENT];

            $realBorrowerProvision       = bcsub($borrowerProvision, $borrowerProvisionCancel, 2);
            $totalPromotionProvision     = bcadd($unilendProvision, $promotionProvision, 2);
            $projectCommission           = bcsub($borrowerCommissionProject, $borrowerRegularization, 2);
            $totalCommission             = bcadd($borrowerCommissionPayment, $projectCommission, 2);
            $totalIncoming               = bcadd($realBorrowerProvision, bcadd($totalPromotionProvision, bcadd($lenderProvisionCreditCard, $lenderProvisionWireTransfer, 2), 2), 2);
            $totalTax                    = bcadd($crds, bcadd($solidarityDeductions, bcadd($additionalContributions, bcadd($socialDeductions, bcadd($csg, bcadd($statutoryContributions, $incomeTax, 2), 2), 2), 2), 2), 2);
            $totalOutgoing               = bcadd($totalTax, bcadd($totalCommission, bcadd($borrowerWithdraw, $lenderWithdraw, 2), 2), 2);
            $totalFinancialMovementsLine = bcsub($totalIncoming, $totalOutgoing, 2);
            $totalPromotionOffer         = bcadd($lenderRegularization, bcadd($commercialGestures, bcsub($promotionalOffers, $promotionalOffersCancel, 2), 2), 2);
            $netInterest                 = bcsub($grossInterest, $totalTax, 2);
            $repaymentAssignment         = bcadd($borrowerCommissionPayment, bcadd($capitalRepayment, $grossInterest, 2), 2);
            $fiscalDifference            = bcsub($repaymentAssignment, bcadd($borrowerCommissionPayment, bcadd($capitalRepayment, bcadd($netInterest, $totalTax, 2), 2), 2), 2);

            $calculatedTotals['financialMovements']    = bcadd($calculatedTotals['financialMovements'], $totalFinancialMovementsLine, 2);
            $calculatedTotals['netInterest']           = bcadd($calculatedTotals['netInterest'], $netInterest, 2);
            $calculatedTotals['repaymentAssignment']   = bcadd($calculatedTotals['repaymentAssignment'], $repaymentAssignment, 2);
            $calculatedTotals['fiscalDifference']      = bcadd($calculatedTotals['fiscalDifference'], $fiscalDifference, 2);
            $calculatedTotals['realBorrowerProvision'] = bcadd($calculatedTotals['realBorrowerProvision'], $realBorrowerProvision, 2);
            $calculatedTotals['promotionProvision']    = bcadd($calculatedTotals['promotionProvision'], $promotionProvision, 2);
            $calculatedTotals['promotionalOffer']      = bcadd($calculatedTotals['promotionalOffer'], $totalPromotionOffer, 2);

            /* Financial Movements */
            $activeSheet->setCellValueExplicit(self::LENDER_PROVISION_CARD_COLUMN . $row, $lenderProvisionCreditCard, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::LENDER_PROVISION_WIRE_TRANSFER_COLUMN . $row, $lenderProvisionWireTransfer, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::LENDER_PROVISION_DIRECT_DEBIT_COLUMN . $row, 0, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::PROMOTION_OFFER_PROVISION_COLUMN . $row, $promotionProvision, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::BORROWER_PROVISION_COLUMN . $row, $realBorrowerProvision , \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::BORROWER_WITHDRAW_COLUMN . $row, $borrowerWithdraw, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::PROJECT_COMMISSION_COLUMN . $row, $projectCommission, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::REPAYMENT_COMMISSION_COLUMN . $row, $borrowerCommissionPayment, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::STATUTORY_CONTRIBUTIONS_COLUMN . $row, $statutoryContributions, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::INCOME_TAX_COLUMN . $row, $incomeTax, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::CSG_COLUMN . $row, $csg, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::SOCIAL_DEDUCTIONS_COLUMN . $row, $socialDeductions, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::ADDITIONAL_CONTRIBUTIONS_COLUMN . $row, $additionalContributions, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::SOLIDARITY_DEDUCTIONS_COLUMN . $row, $solidarityDeductions, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::CRDS_COLUMN . $row, $crds, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::LENDER_WITHDRAW_COLUMN . $row, $lenderWithdraw, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::TOTAL_FINANCIAL_MOVEMENTS_COLUMN . $row, $totalFinancialMovementsLine , \PHPExcel_Cell_DataType::TYPE_NUMERIC);

            /* Internal Movements */
            $activeSheet->setCellValueExplicit(self::PROMOTION_OFFER_DISTRIBUTION_COLUMN . $row, $totalPromotionOffer, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::LENDER_LOAN_COLUMN . $row, $loans, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::CAPITAL_REPAYMENT_COLUMN . $row, $capitalRepayment, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::NET_INTEREST_COLUMN . $row, $netInterest, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::PAYMENT_ASSIGNMENT_COLUMN . $row, $repaymentAssignment, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::FISCAL_DIFFERENCE_COLUMN . $row, $fiscalDifference, \PHPExcel_Cell_DataType::TYPE_NUMERIC);

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
        $projectCommission = bcsub(array_sum(array_column($movements, 'borrower_commission_project')), array_sum(array_column($movements, 'borrower_regularization')), 2);

        $activeSheet->setCellValueExplicit(self::LENDER_PROVISION_CARD_COLUMN . $row, array_sum(array_column($movements, 'lender_provision_credit_card')), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::LENDER_PROVISION_WIRE_TRANSFER_COLUMN . $row, array_sum(array_column($movements,'lender_provision_wire_transfer_in')), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::LENDER_PROVISION_DIRECT_DEBIT_COLUMN . $row, 0, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::PROMOTION_OFFER_PROVISION_COLUMN. $row, $calculatedTotals['promotionProvision'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::BORROWER_PROVISION_COLUMN . $row, $calculatedTotals['realBorrowerProvision'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::BORROWER_WITHDRAW_COLUMN . $row, array_sum(array_column($movements, OperationType::BORROWER_WITHDRAW)), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::PROJECT_COMMISSION_COLUMN . $row, $projectCommission, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::REPAYMENT_COMMISSION_COLUMN . $row, array_sum(array_column($movements, 'borrower_commission_payment')), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::STATUTORY_CONTRIBUTIONS_COLUMN . $row, array_sum(array_column($movements, OperationType::TAX_FR_STATUTORY_CONTRIBUTIONS)), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::INCOME_TAX_COLUMN . $row, array_sum(array_column($movements, OperationType::TAX_FR_INCOME_TAX_DEDUCTED_AT_SOURCE)), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::CSG_COLUMN . $row, array_sum(array_column($movements, OperationType::TAX_FR_CSG)), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::SOCIAL_DEDUCTIONS_COLUMN . $row, array_sum(array_column($movements, OperationType::TAX_FR_SOCIAL_DEDUCTIONS)), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::ADDITIONAL_CONTRIBUTIONS_COLUMN . $row, array_sum(array_column($movements, OperationType::TAX_FR_ADDITIONAL_CONTRIBUTIONS)), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::SOLIDARITY_DEDUCTIONS_COLUMN . $row, array_sum(array_column($movements, OperationType::TAX_FR_SOLIDARITY_DEDUCTIONS)), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::CRDS_COLUMN . $row, array_sum(array_column($movements, OperationType::TAX_FR_CRDS)), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::LENDER_WITHDRAW_COLUMN . $row, array_sum(array_column($movements, OperationType::LENDER_WITHDRAW)), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::TOTAL_FINANCIAL_MOVEMENTS_COLUMN . $row, $calculatedTotals['financialMovements'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::PROMOTION_OFFER_DISTRIBUTION_COLUMN . $row, $calculatedTotals['promotionalOffer'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::LENDER_LOAN_COLUMN . $row, array_sum(array_column($movements, OperationType::LENDER_LOAN)), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::CAPITAL_REPAYMENT_COLUMN . $row, array_sum(array_column($movements, OperationType::CAPITAL_REPAYMENT)), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::NET_INTEREST_COLUMN . $row, $calculatedTotals['netInterest'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::PAYMENT_ASSIGNMENT_COLUMN . $row, $calculatedTotals['repaymentAssignment'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::FISCAL_DIFFERENCE_COLUMN . $row, $calculatedTotals['fiscalDifference'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);
    }

    /**
     * @param \PHPExcel_Worksheet      $activeSheet
     * @param DailyStateBalanceHistory $dailyBalances
     * @param int                      $row
     * @param array                    $specificRows
     */
    private function addBalanceLine(\PHPExcel_Worksheet $activeSheet, DailyStateBalanceHistory $dailyBalances, $row, array $specificRows, $addTotal = false)
    {
        $realBalance = bcadd($dailyBalances->getLenderBorrowerBalance(), $dailyBalances->getUnilendPromotionalBalance(), 2);

        $activeSheet->setCellValueExplicit(self::BALANCE_COLUMN . $row, $realBalance, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::UNILEND_PROMOTIONAL_BALANCE_COLUMN . $row, $dailyBalances->getUnilendPromotionalBalance(), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::UNILEND_BALANCE_COLUMN . $row, $dailyBalances->getUnilendBalance(), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::TAX_BALANCE_COLUMN  . $row, $dailyBalances->getTaxBalance(), \PHPExcel_Cell_DataType::TYPE_NUMERIC);

        if (false === in_array($row, [$specificRows['previousMonth'], $specificRows['previousYear']])) {
            $previousRow        = $row - 1;
            $previousBalance    = $activeSheet->getCell(self::BALANCE_COLUMN . $previousRow)->getValue();
            $totalMovements     = $activeSheet->getCell(self::TOTAL_FINANCIAL_MOVEMENTS_COLUMN . $row)->getValue();
            $theoreticalBalance = bcadd($previousBalance, $totalMovements, 2);
            $globalDifference   = bcsub($theoreticalBalance, $realBalance, 2);

            $activeSheet->setCellValueExplicit(self::THEORETICAL_BALANCE_COLUMN . $row, $theoreticalBalance, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::BALANCE_DIFFERENCE_COLUMN . $row, $globalDifference, \PHPExcel_Cell_DataType::TYPE_NUMERIC);

            if ($addTotal) {
                $totalRow = in_array($row, array_values($specificRows['coordinatesDay'])) ? $specificRows['totalDay'] : $specificRows['totalMonth'];
                $activeSheet->setCellValueExplicit(self::BALANCE_COLUMN . $totalRow, $realBalance, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $activeSheet->setCellValueExplicit(self::UNILEND_PROMOTIONAL_BALANCE_COLUMN . $totalRow, $dailyBalances->getUnilendPromotionalBalance(), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $activeSheet->setCellValueExplicit(self::UNILEND_BALANCE_COLUMN . $totalRow, $dailyBalances->getUnilendBalance(), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $activeSheet->setCellValueExplicit(self::TAX_BALANCE_COLUMN  . $totalRow, $dailyBalances->getTaxBalance(), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $activeSheet->setCellValueExplicit(self::THEORETICAL_BALANCE_COLUMN . $totalRow, $theoreticalBalance, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $activeSheet->setCellValueExplicit(self::BALANCE_DIFFERENCE_COLUMN . $totalRow, $globalDifference, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
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

            $activeSheet->setCellValueExplicit(self::WIRE_TRANSFER_OUT_COLUMN . $row, $wireTransferOut, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::UNILEND_WITHDRAW_COLUMN . $row, $unilendWireTransferOut, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::TAX_WITHDRAW_COLUMN . $row, $taxWireTransferOut, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::DIRECT_DEBIT_COLUMN . $row, $directDebit, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        }

        $activeSheet->setCellValueExplicit(self::WIRE_TRANSFER_OUT_COLUMN . $totalRow, $totalWireTransferOut, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::UNILEND_WITHDRAW_COLUMN . $totalRow, $totalUnilendWireTransferOut, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::TAX_WITHDRAW_COLUMN . $totalRow, $totalTaxWireTransferOut, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::DIRECT_DEBIT_COLUMN . $totalRow, $totalDirectDebit, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
    }
}
