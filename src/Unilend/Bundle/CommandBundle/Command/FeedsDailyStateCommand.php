<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\DailyStateBalanceHistory;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationSubType;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Settings;
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
            )
            ->addOption('no-email', null, InputOption::VALUE_OPTIONAL, 'Do not send email with daily state', false);
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

        if (false === $input->getOption('no-email')) {
            $this->sendFileToInternalRecipients($filePath);
        }
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
            OperationType::UNILEND_PROVISION,
            OperationType::UNILEND_BORROWER_COMMERCIAL_GESTURE,
            OperationType::BORROWER_COMMISSION_REGULARIZATION,
            OperationType::CAPITAL_REPAYMENT_REGULARIZATION,
            OperationType::GROSS_INTEREST_REPAYMENT_REGULARIZATION,
            OperationType::TAX_FR_ADDITIONAL_CONTRIBUTIONS_REGULARIZATION,
            OperationType::TAX_FR_CRDS_REGULARIZATION,
            OperationType::TAX_FR_CSG_REGULARIZATION,
            OperationType::TAX_FR_SOLIDARITY_DEDUCTIONS_REGULARIZATION,
            OperationType::TAX_FR_STATUTORY_CONTRIBUTIONS_REGULARIZATION,
            OperationType::TAX_FR_SOCIAL_DEDUCTIONS_REGULARIZATION,
            OperationType::TAX_FR_INCOME_TAX_DEDUCTED_AT_SOURCE_REGULARIZATION
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
        $previousDay                    = $firstDay->sub(\DateInterval::createFromDateString('1 day'));
        $previousDayBalanceHistory      = $entityManager->getRepository('UnilendCoreBusinessBundle:DailyStateBalanceHistory')->findOneBy(['date' => $previousDay->format('Y-m-d')]);

        if (null === $previousDayBalanceHistory) {
            $previousDayBalanceHistory = $this->newDailyStateBalanceHistory($previousDay);
        }
        $this->addBalanceLine($activeSheet, $previousDayBalanceHistory, $specificRows['previousMonth'], $specificRows);

        foreach ($specificRows['coordinatesDay'] as $date => $row) {
            $dateTime = \DateTime::createFromFormat('Y-m-d', $date);
            $dateTime->setTime(0, 0, 0);
            $requestedDate->setTime(0, 0, 0);
            if ($dateTime > $requestedDate) {
                break;
            }

            $balanceHistory = $entityManager->getRepository('UnilendCoreBusinessBundle:DailyStateBalanceHistory')->findOneBy(['date' => $date]);
            if (null === $balanceHistory) {
                $balanceDate    = ($date == $requestedDate->format('Y-m-d')) ? $requestedDate : $dateTime;
                $balanceHistory = $this->newDailyStateBalanceHistory($balanceDate);
            }
            $this->addBalanceLine($activeSheet, $balanceHistory, $row, $specificRows);

            if ($date === $requestedDate->format('Y-m-d')) {
                $this->addBalanceLine($activeSheet, $balanceHistory, $specificRows['totalDay'], $specificRows);
            }
        }

        $previousYear = new \DateTime('Last day of december ' . $requestedDate->format('Y'));
        $previousYear->sub(\DateInterval::createFromDateString('1 year'));
        $previousMonthBalanceHistory = $entityManager->getRepository('UnilendCoreBusinessBundle:DailyStateBalanceHistory')->findOneBy(['date' => $previousYear->format('Y-m-d')]);

        if (null === $previousMonthBalanceHistory) {
            $previousMonthBalanceHistory = $this->newDailyStateBalanceHistory($previousYear);
        }
        $this->addBalanceLine($activeSheet, $previousMonthBalanceHistory, $specificRows['previousYear'], $specificRows);

        foreach ($specificRows['coordinatesMonth'] as $month => $row) {
            $lastDayOfMonth = \DateTime::createFromFormat('n-Y', $month . '-' . $requestedDate->format('Y'));

            if ($month <= $requestedDate->format('n')) {
                if ($month == $requestedDate->format('n')) {
                    $balanceHistory = $entityManager->getRepository('UnilendCoreBusinessBundle:DailyStateBalanceHistory')->findOneBy(['date' => $requestedDate->format('Y-m-d')]);
                    $this->addBalanceLine($activeSheet, $balanceHistory, $row, $specificRows);
                    $this->addBalanceLine($activeSheet, $balanceHistory, $specificRows['totalMonth'], $specificRows);
                    continue;
                }
                $balanceHistory = $entityManager->getRepository('UnilendCoreBusinessBundle:DailyStateBalanceHistory')->findOneBy(['date' => $lastDayOfMonth->format('Y-m-t')]);
                if (null === $balanceHistory) {
                    $balanceHistory = $this->newDailyStateBalanceHistory($lastDayOfMonth);
                }
                $this->addBalanceLine($activeSheet, $balanceHistory, $row, $specificRows);
            }
        }
    }

    /**
     * @param \DateTime $balanceDate
     *
     * @return DailyStateBalanceHistory
     */
    private function newDailyStateBalanceHistory(\DateTime $balanceDate)
    {
        $entityManager                  = $this->getContainer()->get('doctrine.orm.entity_manager');
        $walletBalanceHistoryRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:WalletBalanceHistory');

        $balanceHistory = new DailyStateBalanceHistory();
        $balanceHistory->setLenderBorrowerBalance($walletBalanceHistoryRepository->sumBalanceForDailyState($balanceDate, [WalletType::LENDER, WalletType::BORROWER]));
        $balanceHistory->setUnilendBalance($walletBalanceHistoryRepository->sumBalanceForDailyState($balanceDate, [WalletType::UNILEND]));
        $balanceHistory->setUnilendPromotionalBalance($walletBalanceHistoryRepository->sumBalanceForDailyState($balanceDate, [WalletType::UNILEND_PROMOTIONAL_OPERATION]));
        $balanceHistory->setTaxBalance($walletBalanceHistoryRepository->sumBalanceForDailyState($balanceDate, WalletType::TAX_FR_WALLETS));
        $balanceHistory->setDate($balanceDate->format('Y-m-d'));

        $entityManager->persist($balanceHistory);
        $entityManager->flush($balanceHistory);

        return $balanceHistory;
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
            $lenderProvisionCreditCard               = empty($line['lender_provision_credit_card']) ? 0 : $line['lender_provision_credit_card'];
            $lenderProvisionWireTransfer             = empty($line['lender_provision_wire_transfer_in']) ? 0 : $line['lender_provision_wire_transfer_in'];
            $promotionProvision                      = empty($line[OperationType::UNILEND_PROMOTIONAL_OPERATION_PROVISION]) ? 0 : $line[OperationType::UNILEND_PROMOTIONAL_OPERATION_PROVISION];
            $unilendProvision                        = empty($line[OperationType::UNILEND_PROVISION]) ? 0 : $line[OperationType::UNILEND_PROVISION];
            $borrowerProvision                       = empty($line[OperationType::BORROWER_PROVISION]) ? 0 : $line[OperationType::BORROWER_PROVISION];
            $borrowerProvisionCancel                 = empty($line[OperationType::BORROWER_PROVISION_CANCEL]) ? 0 : $line[OperationType::BORROWER_PROVISION_CANCEL];
            $borrowerWithdraw                        = empty($line[OperationType::BORROWER_WITHDRAW]) ? 0 : $line[OperationType::BORROWER_WITHDRAW];
            $borrowerCommissionProject               = empty($line[OperationSubType::BORROWER_COMMISSION_FUNDS]) ? 0 : $line[OperationSubType::BORROWER_COMMISSION_FUNDS];
            $borrowerCommissionProjectRegularization = empty($line[OperationType::BORROWER_COMMISSION_REGULARIZATION]) ? 0 : $line[OperationType::BORROWER_COMMISSION_REGULARIZATION];
            $borrowerCommissionPayment               = empty($line[OperationSubType::BORROWER_COMMISSION_REPAYMENT]) ? 0 : $line[OperationSubType::BORROWER_COMMISSION_REPAYMENT];
            $borrowerCommissionPaymentRegularization = empty($line[OperationSubType::BORROWER_COMMISSION_REPAYMENT_REGULARIZATION]) ? 0 : $line[OperationSubType::BORROWER_COMMISSION_REPAYMENT_REGULARIZATION];
            $statutoryContributions                  = empty($line[OperationType::TAX_FR_STATUTORY_CONTRIBUTIONS]) ? 0 : $line[OperationType::TAX_FR_STATUTORY_CONTRIBUTIONS];
            $statutoryContributionsRegularization    = empty($line[OperationType::TAX_FR_STATUTORY_CONTRIBUTIONS_REGULARIZATION]) ? 0 : $line[OperationType::TAX_FR_STATUTORY_CONTRIBUTIONS_REGULARIZATION];
            $incomeTax                               = empty($line[OperationType::TAX_FR_INCOME_TAX_DEDUCTED_AT_SOURCE]) ? 0 : $line[OperationType::TAX_FR_INCOME_TAX_DEDUCTED_AT_SOURCE];
            $incomeTaxRegularization                 = empty($line[OperationType::TAX_FR_INCOME_TAX_DEDUCTED_AT_SOURCE_REGULARIZATION]) ? 0 : $line[OperationType::TAX_FR_INCOME_TAX_DEDUCTED_AT_SOURCE_REGULARIZATION];
            $csg                                     = empty($line[OperationType::TAX_FR_CSG]) ? 0 : $line[OperationType::TAX_FR_CSG];
            $csgRegularization                       = empty($line[OperationType::TAX_FR_CSG_REGULARIZATION]) ? 0 : $line[OperationType::TAX_FR_CSG_REGULARIZATION];
            $socialDeductions                        = empty($line[OperationType::TAX_FR_SOCIAL_DEDUCTIONS]) ? 0 : $line[OperationType::TAX_FR_SOCIAL_DEDUCTIONS];
            $socialDeductionsRegularization          = empty($line[OperationType::TAX_FR_SOCIAL_DEDUCTIONS_REGULARIZATION]) ? 0 : $line[OperationType::TAX_FR_SOCIAL_DEDUCTIONS_REGULARIZATION];
            $additionalContributions                 = empty($line[OperationType::TAX_FR_ADDITIONAL_CONTRIBUTIONS]) ? 0 : $line[OperationType::TAX_FR_ADDITIONAL_CONTRIBUTIONS];
            $additionalContributionsRegularization   = empty($line[OperationType::TAX_FR_ADDITIONAL_CONTRIBUTIONS_REGULARIZATION]) ? 0 : $line[OperationType::TAX_FR_ADDITIONAL_CONTRIBUTIONS_REGULARIZATION];
            $solidarityDeductions                    = empty($line[OperationType::TAX_FR_SOLIDARITY_DEDUCTIONS]) ? 0 : $line[OperationType::TAX_FR_SOLIDARITY_DEDUCTIONS];
            $solidarityDeductionsRegularization      = empty($line[OperationType::TAX_FR_SOLIDARITY_DEDUCTIONS_REGULARIZATION]) ? 0 : $line[OperationType::TAX_FR_SOLIDARITY_DEDUCTIONS_REGULARIZATION];
            $crds                                    = empty($line[OperationType::TAX_FR_CRDS]) ? 0 : $line[OperationType::TAX_FR_CRDS];
            $crdsRegularization                      = empty($line[OperationType::TAX_FR_CRDS_REGULARIZATION]) ? 0 : $line[OperationType::TAX_FR_CRDS_REGULARIZATION];
            $lenderWithdraw                          = empty($line[OperationType::LENDER_WITHDRAW]) ? 0 : $line[OperationType::LENDER_WITHDRAW];
            $promotionalOffers                       = empty($line[OperationType::UNILEND_PROMOTIONAL_OPERATION]) ? 0 : $line[OperationType::UNILEND_PROMOTIONAL_OPERATION];
            $commercialGestures                      = empty($line[OperationType::UNILEND_BORROWER_COMMERCIAL_GESTURE]) ? 0 : $line[OperationType::UNILEND_BORROWER_COMMERCIAL_GESTURE];
            $lenderRegularization                    = empty($line[OperationType::UNILEND_LENDER_REGULARIZATION]) ? 0 : $line[OperationType::UNILEND_LENDER_REGULARIZATION];
            $promotionalOffersCancel                 = empty($line[OperationType::UNILEND_PROMOTIONAL_OPERATION_CANCEL]) ? 0 : $line[OperationType::UNILEND_PROMOTIONAL_OPERATION_CANCEL];
            $loans                                   = empty($line[OperationType::LENDER_LOAN]) ? 0 : $line[OperationType::LENDER_LOAN];
            $capitalRepayment                        = empty($line[OperationType::CAPITAL_REPAYMENT]) ? 0 : $line[OperationType::CAPITAL_REPAYMENT];
            $capitalRepaymentRegularization          = empty($line[OperationType::CAPITAL_REPAYMENT_REGULARIZATION]) ? 0 : $line[OperationType::CAPITAL_REPAYMENT_REGULARIZATION];
            $grossInterest                           = empty($line[OperationType::GROSS_INTEREST_REPAYMENT]) ? 0 : $line[OperationType::GROSS_INTEREST_REPAYMENT];
            $grossInterestRegularization             = empty($line[OperationType::GROSS_INTEREST_REPAYMENT_REGULARIZATION]) ? 0 : $line[OperationType::GROSS_INTEREST_REPAYMENT_REGULARIZATION];

            $totalStatutoryContributions  = bcsub($statutoryContributions, $statutoryContributionsRegularization, 2);
            $totalIncomeTax               = bcsub($incomeTax, $incomeTaxRegularization, 2);
            $totalCsg                     = bcsub($csg, $csgRegularization, 2);
            $totalSocialDeductions        = bcsub($socialDeductions, $socialDeductionsRegularization, 2);
            $totalAdditionalContributions = bcsub($additionalContributions, $additionalContributionsRegularization, 2);
            $totalSolidarityDeductions    = bcsub($solidarityDeductions, $solidarityDeductionsRegularization, 2);
            $totalCrds                    = bcsub($crds, $crdsRegularization, 2);
            $realBorrowerProvision        = bcsub($borrowerProvision, $borrowerProvisionCancel, 2);
            $totalPromotionProvision      = bcadd($unilendProvision, $promotionProvision, 2);
            $totalProjectCommission       = bcsub($borrowerCommissionProject, $borrowerCommissionProjectRegularization, 2);
            $totalPaymentCommission       = bcsub($borrowerCommissionPayment, $borrowerCommissionPaymentRegularization, 2);
            $totalCommission              = bcadd($totalPaymentCommission, $totalProjectCommission, 2);
            $totalIncoming                = bcadd($realBorrowerProvision, bcadd($totalPromotionProvision, bcadd($lenderProvisionCreditCard, $lenderProvisionWireTransfer, 2), 2), 2);
            $totalTax                     = bcadd($totalCrds, bcadd($totalSolidarityDeductions, bcadd($totalAdditionalContributions, bcadd($totalSocialDeductions, bcadd($totalCsg, bcadd($totalStatutoryContributions, $totalIncomeTax, 2), 2), 2), 2), 2), 2);
            $totalOutgoing                = bcadd($totalTax, bcadd($totalCommission, bcadd($borrowerWithdraw, $lenderWithdraw, 2), 2), 2);
            $totalFinancialMovementsLine  = bcsub($totalIncoming, $totalOutgoing, 2);
            $totalPromotionOffer          = bcadd($lenderRegularization, bcadd($commercialGestures, bcsub($promotionalOffers, $promotionalOffersCancel, 2), 2), 2);
            $totalCapitalRepayment        = bcsub($capitalRepayment, $capitalRepaymentRegularization, 2);
            $netInterest                  = bcsub(bcsub($grossInterest, $grossInterestRegularization, 2), $totalTax, 2);
            $repaymentAssignment          = bcadd($totalPaymentCommission, bcadd($totalCapitalRepayment, bcsub($grossInterest, $grossInterestRegularization, 2), 2), 2);
            $fiscalDifference             = bcsub($repaymentAssignment, bcadd($totalPaymentCommission, bcadd($totalCapitalRepayment, bcadd($netInterest, $totalTax, 2), 2), 2), 2);

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
            $activeSheet->setCellValueExplicit(self::PROJECT_COMMISSION_COLUMN . $row, $totalProjectCommission, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::REPAYMENT_COMMISSION_COLUMN . $row, $borrowerCommissionPayment, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::STATUTORY_CONTRIBUTIONS_COLUMN . $row, $totalStatutoryContributions, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::INCOME_TAX_COLUMN . $row, $totalIncomeTax, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::CSG_COLUMN . $row, $totalCsg, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::SOCIAL_DEDUCTIONS_COLUMN . $row, $totalSocialDeductions, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::ADDITIONAL_CONTRIBUTIONS_COLUMN . $row, $totalAdditionalContributions, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::SOLIDARITY_DEDUCTIONS_COLUMN . $row, $totalSolidarityDeductions, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::CRDS_COLUMN . $row, $totalCrds, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::LENDER_WITHDRAW_COLUMN . $row, $lenderWithdraw, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::TOTAL_FINANCIAL_MOVEMENTS_COLUMN . $row, $totalFinancialMovementsLine , \PHPExcel_Cell_DataType::TYPE_NUMERIC);

            /* Internal Movements */
            $activeSheet->setCellValueExplicit(self::PROMOTION_OFFER_DISTRIBUTION_COLUMN . $row, $totalPromotionOffer, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::LENDER_LOAN_COLUMN . $row, $loans, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::CAPITAL_REPAYMENT_COLUMN . $row, $totalCapitalRepayment, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
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
        $borrowerCommissionProject               = array_sum(array_column($movements, OperationSubType::BORROWER_COMMISSION_FUNDS));
        $borrowerCommissionProjectRegularization = array_sum(array_column($movements, OperationType::BORROWER_COMMISSION_REGULARIZATION));
        $borrowerCommissionPayment               = array_sum(array_column($movements, OperationSubType::BORROWER_COMMISSION_REPAYMENT));
        $borrowerCommissionPaymentRegularization = array_sum(array_column($movements, OperationSubType::BORROWER_COMMISSION_REPAYMENT_REGULARIZATION));
        $statutoryContributions                  = array_sum(array_column($movements, OperationType::TAX_FR_STATUTORY_CONTRIBUTIONS));;
        $statutoryContributionsRegularization    = array_sum(array_column($movements, OperationType::TAX_FR_STATUTORY_CONTRIBUTIONS_REGULARIZATION));
        $incomeTax                               = array_sum(array_column($movements, OperationType::TAX_FR_INCOME_TAX_DEDUCTED_AT_SOURCE));
        $incomeTaxRegularization                 = array_sum(array_column($movements, OperationType::TAX_FR_INCOME_TAX_DEDUCTED_AT_SOURCE_REGULARIZATION));
        $csg                                     = array_sum(array_column($movements, OperationType::TAX_FR_CSG));
        $csgRegularization                       = array_sum(array_column($movements, OperationType::TAX_FR_CSG_REGULARIZATION));
        $socialDeductions                        = array_sum(array_column($movements, OperationType::TAX_FR_SOCIAL_DEDUCTIONS));
        $socialDeductionsRegularization          = array_sum(array_column($movements, OperationType::TAX_FR_SOCIAL_DEDUCTIONS_REGULARIZATION));
        $additionalContributions                 = array_sum(array_column($movements, OperationType::TAX_FR_ADDITIONAL_CONTRIBUTIONS));
        $additionalContributionsRegularization   = array_sum(array_column($movements, OperationType::TAX_FR_ADDITIONAL_CONTRIBUTIONS_REGULARIZATION));
        $solidarityDeductions                    = array_sum(array_column($movements, OperationType::TAX_FR_SOLIDARITY_DEDUCTIONS));
        $solidarityDeductionsRegularization      = array_sum(array_column($movements, OperationType::TAX_FR_SOLIDARITY_DEDUCTIONS_REGULARIZATION));
        $crds                                    = array_sum(array_column($movements, OperationType::TAX_FR_CRDS));
        $crdsRegularization                      = array_sum(array_column($movements, OperationType::TAX_FR_CRDS_REGULARIZATION));
        $capitalRepayment                        = array_sum(array_column($movements, OperationType::CAPITAL_REPAYMENT));
        $capitalRepaymentRegularization          = array_sum(array_column($movements, OperationType::CAPITAL_REPAYMENT_REGULARIZATION));

        $totalStatutoryContributions  = bcsub($statutoryContributions, $statutoryContributionsRegularization, 2);
        $totalIncomeTax               = bcsub($incomeTax, $incomeTaxRegularization, 2);
        $totalCsg                     = bcsub($csg, $csgRegularization, 2);
        $totalSocialDeductions        = bcsub($socialDeductions, $socialDeductionsRegularization, 2);
        $totalAdditionalContributions = bcsub($additionalContributions, $additionalContributionsRegularization, 2);
        $totalSolidarityDeductions    = bcsub($solidarityDeductions, $solidarityDeductionsRegularization, 2);
        $totalCrds                    = bcsub($crds, $crdsRegularization, 2);
        $totalProjectCommission       = bcsub($borrowerCommissionProject, $borrowerCommissionProjectRegularization, 2);
        $totalPaymentCommission       = bcsub($borrowerCommissionPayment, $borrowerCommissionPaymentRegularization, 2);
        $totalCapitalRepayment        = bcsub($capitalRepayment, $capitalRepaymentRegularization, 2);

        $activeSheet->setCellValueExplicit(self::LENDER_PROVISION_CARD_COLUMN . $row, array_sum(array_column($movements, 'lender_provision_credit_card')), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::LENDER_PROVISION_WIRE_TRANSFER_COLUMN . $row, array_sum(array_column($movements,'lender_provision_wire_transfer_in')), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::LENDER_PROVISION_DIRECT_DEBIT_COLUMN . $row, 0, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::PROMOTION_OFFER_PROVISION_COLUMN. $row, $calculatedTotals['promotionProvision'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::BORROWER_PROVISION_COLUMN . $row, $calculatedTotals['realBorrowerProvision'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::BORROWER_WITHDRAW_COLUMN . $row, array_sum(array_column($movements, OperationType::BORROWER_WITHDRAW)), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::PROJECT_COMMISSION_COLUMN . $row, $totalProjectCommission, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::REPAYMENT_COMMISSION_COLUMN . $row, $totalPaymentCommission, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::STATUTORY_CONTRIBUTIONS_COLUMN . $row, $totalStatutoryContributions, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::INCOME_TAX_COLUMN . $row, $totalIncomeTax, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::CSG_COLUMN . $row, $totalCsg, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::SOCIAL_DEDUCTIONS_COLUMN . $row, $totalSocialDeductions, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::ADDITIONAL_CONTRIBUTIONS_COLUMN . $row, $totalAdditionalContributions, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::SOLIDARITY_DEDUCTIONS_COLUMN . $row, $totalSolidarityDeductions, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::CRDS_COLUMN . $row, $totalCrds, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::LENDER_WITHDRAW_COLUMN . $row, array_sum(array_column($movements, OperationType::LENDER_WITHDRAW)), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::TOTAL_FINANCIAL_MOVEMENTS_COLUMN . $row, $calculatedTotals['financialMovements'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::PROMOTION_OFFER_DISTRIBUTION_COLUMN . $row, $calculatedTotals['promotionalOffer'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::LENDER_LOAN_COLUMN . $row, array_sum(array_column($movements, OperationType::LENDER_LOAN)), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::CAPITAL_REPAYMENT_COLUMN . $row, $totalCapitalRepayment, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
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
    private function addBalanceLine(\PHPExcel_Worksheet $activeSheet, DailyStateBalanceHistory $dailyBalances, $row, array $specificRows)
    {
        $isPreviousLine = in_array($row, [$specificRows['previousMonth'], $specificRows['previousYear']]);
        $isTotal        = in_array($row, [$specificRows['totalDay'], $specificRows['totalMonth']]);
        $realBalance    = bcadd($dailyBalances->getLenderBorrowerBalance(), $dailyBalances->getUnilendPromotionalBalance(), 2);

        $activeSheet->setCellValueExplicit(self::BALANCE_COLUMN . $row, $realBalance, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::UNILEND_PROMOTIONAL_BALANCE_COLUMN . $row, $dailyBalances->getUnilendPromotionalBalance(), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::UNILEND_BALANCE_COLUMN . $row, $dailyBalances->getUnilendBalance(), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::TAX_BALANCE_COLUMN  . $row, $dailyBalances->getTaxBalance(), \PHPExcel_Cell_DataType::TYPE_NUMERIC);

        if ($isPreviousLine) {
            $globalDifference = bcsub($dailyBalances->getTheoreticalBalance(), $realBalance, 2);
            $activeSheet->setCellValueExplicit(self::THEORETICAL_BALANCE_COLUMN . $row, $dailyBalances->getTheoreticalBalance(), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::BALANCE_DIFFERENCE_COLUMN . $row, $globalDifference, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        }

        if (false === $isTotal && false === $isPreviousLine) {
            $previousRow        = $row - 1;
            $previousBalance    = $activeSheet->getCell(self::THEORETICAL_BALANCE_COLUMN . $previousRow)->getValue();
            $totalMovements     = $activeSheet->getCell(self::TOTAL_FINANCIAL_MOVEMENTS_COLUMN . $row)->getValue();
            $theoreticalBalance = bcadd($previousBalance, $totalMovements, 2);
            $globalDifference   = bcsub($theoreticalBalance, $realBalance, 2);
            $this->addTheoreticalBalanceToHistory($theoreticalBalance, $dailyBalances);

            $activeSheet->setCellValueExplicit(self::THEORETICAL_BALANCE_COLUMN . $row, $theoreticalBalance, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::BALANCE_DIFFERENCE_COLUMN . $row, $globalDifference, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        }

        if ($isTotal) {
            if ($row == $specificRows['totalDay']) {
                $lastNotEmptyRow    = $specificRows['coordinatesDay'][$dailyBalances->getDate()];
                $theoreticalBalance = $activeSheet->getCell(self::THEORETICAL_BALANCE_COLUMN . $lastNotEmptyRow)->getValue();
            }

            if ($row == $specificRows['totalMonth']) {
                $month              = (int) substr($dailyBalances->getDate(), 5, 2);
                $lastNotEmptyRow    = $specificRows['coordinatesMonth'][$month];
                $theoreticalBalance = $activeSheet->getCell(self::THEORETICAL_BALANCE_COLUMN . $lastNotEmptyRow)->getValue();
            }
            $globalDifference   = $activeSheet->getCell(self::BALANCE_DIFFERENCE_COLUMN . $lastNotEmptyRow)->getValue();
            $activeSheet->setCellValueExplicit(self::THEORETICAL_BALANCE_COLUMN . $row, $theoreticalBalance, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::BALANCE_DIFFERENCE_COLUMN . $row, $globalDifference, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        }

    }

    /**
     * @param string                   $theoreticalBalance
     * @param DailyStateBalanceHistory $dailyBalances
     */
    private function addTheoreticalBalanceToHistory($theoreticalBalance, DailyStateBalanceHistory $dailyBalances)
    {
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        $dailyBalances->setTheoreticalBalance($theoreticalBalance);

        $entityManager->flush($dailyBalances);
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

    /**
     * @param string $filePath
     */
    private function sendFileToInternalRecipients($filePath)
    {
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        /** @var Settings $recipientSetting */
        $recipientSetting = $entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Adresse notification etat quotidien']);

        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->getContainer()->get('unilend.swiftmailer.message_provider')->newMessage('notification-etat-quotidien', [], false);
        $message
            ->setTo(explode(';', trim($recipientSetting->getValue())))
            ->attach(\Swift_Attachment::fromPath($filePath));

        /** @var \Swift_Mailer $mailer */
        $mailer = $this->getContainer()->get('mailer');
        $mailer->send($message);
    }
}
