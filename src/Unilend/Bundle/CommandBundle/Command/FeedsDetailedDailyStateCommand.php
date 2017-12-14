<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\DetailedDailyStateBalanceHistory;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationSubType;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Settings;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;

class FeedsDetailedDailyStateCommand extends ContainerAwareCommand
{
    const ROW_HEIGHT     = 20;
    const COLUMN_WIDTH   = 14;
    const DAY_HEADER_ROW = 2;

    /** Incoming financial feeds columns */
    const LENDER_PROVISION_CARD_COLUMN            = 'B';
    const LENDER_PROVISION_WIRE_TRANSFER_COLUMN   = 'C';
    const BORROWER_PROVISION_DIRECT_DEBIT_COLUMN  = 'D';
    const BORROWER_PROVISION_WIRE_TRANSFER_COLUMN = 'E';
    const BORROWER_PROVISION_OTHER_COLUMN         = 'F';
    const PROMOTIONAL_OFFER_PROVISION_COLUMN      = 'G';
    const TOTAL_INCOMING_COLUMN                   = 'H';

    /** Outgoing financial feeds columns */
    const TAX_WITHDRAW_COLUMN_NEW        = 'I';
    const LENDER_WITHDRAW_COLUMN_NEW     = 'J';
    const UNILEND_WITHDRAW_COLUMN_NEW    = 'K';
    const DEBT_COLLECTOR_WITHDRAW_COLUMN = 'L';
    const BORROWER_WITHDRAW_FUNDS_COLUMN = 'M';
    const BORROWER_WITHDRAW_OTHER_COLUMN = 'N';
    const TOTAL_OUTGOING_COLUMN          = 'O';

    const TOTAL_FINANCIAL_MOVEMENTS_COLUMN = 'P';

    /** Balance columns */
    const THEORETICAL_BALANCE_COLUMN         = 'Q';
    const BALANCE_COLUMN                     = 'R';
    const BALANCE_DIFFERENCE_COLUMN          = 'S';
    const LENDER_BALANCE_COLUMN              = 'T';
    const BORROWER_BALANCE_COLUMN            = 'U';
    const DEBT_COLLECTOR_BALANCE_COLUMN      = 'V';
    const UNILEND_PROMOTIONAL_BALANCE_COLUMN = 'W';
    const UNILEND_BALANCE_COLUMN             = 'X';
    const TAX_BALANCE_COLUMN                 = 'Y';

    /** Internal movements */
    const LENDER_LOAN_COLUMN                  = 'Z';
    const UNILEND_COMMISSION_FUNDS_COLUMN     = 'AA';
    const CAPITAL_REPAYMENT_COLUMN            = 'AB';
    const GROSS_INTEREST_COLUMN               = 'AC';
    const STATUTORY_CONTRIBUTIONS_COLUMN      = 'AD';
    const INCOME_TAX_COLUMN                   = 'AE';
    const CSG_COLUMN                          = 'AF';
    const SOCIAL_DEDUCTIONS_COLUMN            = 'AG';
    const ADDITIONAL_CONTRIBUTIONS_COLUMN     = 'AH';
    const SOLIDARITY_DEDUCTIONS_COLUMN        = 'AI';
    const CRDS_COLUMN                         = 'AJ';
    const TOTAL_TAX_COLUMN                    = 'AK';
    const UNILEND_COMMISSION_REPAYMENT        = 'AL';
    const PAYMENT_ASSIGNMENT_COLUMN           = 'AM';
    const FISCAL_DIFFERENCE_COLUMN            = 'AN';
    const DEBT_COLLECTOR_COMMISSION_COLUMN    = 'AO';
    const BORROWER_CHARGE_REPAYMENT_COLUMN    = 'AP';
    const PROMOTION_OFFER_DISTRIBUTION_COLUMN = 'AQ';

    const DATE_COLUMN = 'A';
    const LAST_COLUMN = self::PROMOTION_OFFER_DISTRIBUTION_COLUMN;

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('feeds:detailed_daily_state')
            ->setDescription('Extract daily fiscal state')
            ->addArgument('day', InputArgument::OPTIONAL, 'Day of the state to export (format: Y-m-d)')
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

        try {
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

            $separationRow = $specificRows['firstMonth'] + 12;
            $activeSheet->mergeCells(self::DATE_COLUMN . $separationRow . ':' . $maxCoordinates['column'] . $separationRow);
            $specificRows['totalMonth'] = $specificRows['firstMonth'] + 13;
            $this->applyStyleToWorksheet($activeSheet, $specificRows);

            $this->addMovementData($activeSheet, $firstDay, $requestedDate, $specificRows);
            $this->addBalanceData($activeSheet, $firstDay, $requestedDate, $specificRows);

            $filePath = $this->getContainer()->getParameter('path.sftp') . 'sfpmei/emissions/etat_quotidien/Unilend_etat_detaille_' . $requestedDate->format('Ymd') . '.xlsx';
            /** @var \PHPExcel_Writer_CSV $writer */
            $writer = \PHPExcel_IOFactory::createWriter($document, 'Excel2007');
            $writer->save(str_replace(__FILE__, $filePath, __FILE__));

//            if (false === $input->getOption('no-email')) {
//                $this->sendFileToInternalRecipients($filePath);
//            }

        } catch (\Exception $exception) {
            $this->getContainer()->get('monolog.logger.console')->warning('Detailed daily state could not be generated', [$exception->getMessage()]);
        }
    }

    /**
     * @param \PHPExcel_Worksheet $activeSheet
     * @param \DateTime           $firstDay
     * @param \DateTime           $requestedDate
     * @param array               $specificRows
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    private function addMovementData(\PHPExcel_Worksheet $activeSheet, \DateTime $firstDay, \DateTime $requestedDate, array $specificRows)
    {
        $entityManager       = $this->getContainer()->get('doctrine.orm.entity_manager');
        $operationRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Operation');

        $incomingMovements = [
            OperationType::LENDER_PROVISION,
            OperationType::LENDER_PROVISION_CANCEL,
            OperationType::BORROWER_PROVISION,
            OperationType::BORROWER_PROVISION_CANCEL,
            OperationType::UNILEND_PROMOTIONAL_OPERATION_PROVISION,
            OperationType::UNILEND_PROVISION,
        ];

        $outgoingMovements = array_merge([
            OperationType::LENDER_WITHDRAW,
            OperationType::BORROWER_WITHDRAW,
            OperationType::UNILEND_WITHDRAW,
            OperationType::DEBT_COLLECTOR_WITHDRAW
        ], OperationType::TAX_WITHDRAW_TYPES);

        $internalMovements = array_merge([
            OperationType::LENDER_LOAN,
            OperationType::CAPITAL_REPAYMENT,
            OperationType::CAPITAL_REPAYMENT_REGULARIZATION,
            OperationType::GROSS_INTEREST_REPAYMENT,
            OperationType::GROSS_INTEREST_REPAYMENT_REGULARIZATION,
            OperationType::BORROWER_COMMISSION,
            OperationType::BORROWER_COMMISSION_REGULARIZATION,
            OperationType::BORROWER_PROJECT_CHARGE_REPAYMENT,
            OperationType::UNILEND_PROMOTIONAL_OPERATION,
            OperationType::UNILEND_PROMOTIONAL_OPERATION_CANCEL,
            OperationType::UNILEND_LENDER_REGULARIZATION,
            OperationType::UNILEND_BORROWER_COMMERCIAL_GESTURE,
            OperationType::COLLECTION_COMMISSION_PROVISION,
            OperationType::COLLECTION_COMMISSION_LENDER,
            OperationType::COLLECTION_COMMISSION_LENDER_REGULARIZATION,
            OperationType::COLLECTION_COMMISSION_BORROWER,
            OperationType::COLLECTION_COMMISSION_BORROWER_REGULARIZATION,
            OperationType::COLLECTION_COMMISSION_UNILEND
        ], OperationType::TAX_TYPES_FR, OperationType::TAX_TYPES_FR_REGULARIZATION);

        $movements = array_merge($incomingMovements, $outgoingMovements, $internalMovements);

        $dailyMovements   = $operationRepository->sumMovementsForDailyStateByDay($firstDay, $requestedDate, $movements);
        $monthlyMovements = $operationRepository->sumMovementsForDailyStateByMonth($requestedDate, $movements);
        $totalMonth[]     = $operationRepository->sumMovementsForDailyState($firstDay, $requestedDate, $movements);
        $totalYear[]      = $operationRepository->sumMovementsForDailyState(new \DateTime('first day of January ' . $requestedDate->format('Y')), $requestedDate, $movements);

        $this->addMovementLines($activeSheet, $dailyMovements, $specificRows['firstDay']);
        $this->addMovementLines($activeSheet, $monthlyMovements, $specificRows['firstMonth']);
        $this->addMovementLines($activeSheet, $totalMonth, $specificRows['totalDay']);
        $this->addMovementLines($activeSheet, $totalYear, $specificRows['totalMonth']);
    }

    /**
     * @param \PHPExcel_Worksheet $activeSheet
     * @param \DateTime           $firstDay
     * @param \DateTime           $requestedDate
     * @param array               $specificRows
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \PHPExcel_Exception
     */
    private function addBalanceData(\PHPExcel_Worksheet $activeSheet, \DateTime $firstDay, \DateTime $requestedDate, array $specificRows)
    {
        $detailedDailyStateBalanceRepository = $this->getContainer()->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:DetailedDailyStateBalanceHistory');
        $previousDay                         = $firstDay->sub(\DateInterval::createFromDateString('1 day'));
        $previousDayBalanceHistory           = $detailedDailyStateBalanceRepository->findOneBy(['date' => $previousDay]);

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

            $balanceHistory = $detailedDailyStateBalanceRepository->findOneBy(['date' => $dateTime]);
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
        $previousMonthBalanceHistory = $detailedDailyStateBalanceRepository->findOneBy(['date' => $previousYear]);

        if (null === $previousMonthBalanceHistory) {
            $previousMonthBalanceHistory = $this->newDailyStateBalanceHistory($previousYear);
        }
        $this->addBalanceLine($activeSheet, $previousMonthBalanceHistory, $specificRows['previousYear'], $specificRows);

        foreach ($specificRows['coordinatesMonth'] as $month => $row) {
            $monthObject    = \DateTime::createFromFormat('Y-n-d', $requestedDate->format('Y') . '-' . $month . '-01');
            $lastDayOfMonth = new \DateTime('Last day of ' . $monthObject->format('F Y'));

            if ($month <= $requestedDate->format('n')) {
                if ($month == $requestedDate->format('n')) {
                    $balanceHistory = $detailedDailyStateBalanceRepository->findOneBy(['date' => $requestedDate]);
                    $this->addBalanceLine($activeSheet, $balanceHistory, $row, $specificRows);
                    $this->addBalanceLine($activeSheet, $balanceHistory, $specificRows['totalMonth'], $specificRows);
                    continue;
                }
                $balanceHistory = $detailedDailyStateBalanceRepository->findOneBy(['date' => $lastDayOfMonth]);
                if (null === $balanceHistory) {
                    $balanceHistory = $this->newDailyStateBalanceHistory($lastDayOfMonth);
                }
                $this->addBalanceLine($activeSheet, $balanceHistory, $row, $specificRows);
            }
        }
    }

    /**
     * @param \DateTime $date
     *
     * @return DetailedDailyStateBalanceHistory
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function newDailyStateBalanceHistory(\DateTime $date)
    {
        $entityManager                  = $this->getContainer()->get('doctrine.orm.entity_manager');
        $walletBalanceHistoryRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:WalletBalanceHistory');

        $balanceHistory = new DetailedDailyStateBalanceHistory();
        $balanceHistory->setLenderBalance($walletBalanceHistoryRepository->sumBalanceForDailyState($date, [WalletType::LENDER]));
        $balanceHistory->setBorrowerBalance($walletBalanceHistoryRepository->sumBalanceForDailyState($date, [WalletType::BORROWER]));
        $balanceHistory->setDebtCollectorBalance($walletBalanceHistoryRepository->sumBalanceForDailyState($date, [WalletType::DEBT_COLLECTOR]));
        $balanceHistory->setUnilendBalance($walletBalanceHistoryRepository->sumBalanceForDailyState($date, [WalletType::UNILEND]));
        $balanceHistory->setUnilendPromotionalBalance($walletBalanceHistoryRepository->sumBalanceForDailyState($date, [WalletType::UNILEND_PROMOTIONAL_OPERATION]));
        $balanceHistory->setTaxBalance($walletBalanceHistoryRepository->sumBalanceForDailyState($date, WalletType::TAX_FR_WALLETS));
        $balanceHistory->setDate($date);
        $balanceHistory->setAdded(new \DateTime('NOW'));

        $entityManager->persist($balanceHistory);
        $entityManager->flush($balanceHistory);

        return $balanceHistory;
    }

    /**
     * @param \PHPExcel_Worksheet $activeSheet
     * @param \DateTime           $date
     * @param int                 $startRow
     *
     * @throws \PHPExcel_Exception
     */
    private function addHeaders(\PHPExcel_Worksheet $activeSheet, \DateTime $date, $startRow)
    {
        $bigSectionsRow     = $startRow + 1;
        $descriptionRow     = $bigSectionsRow + 1;
        $additionalInfoRow  = $descriptionRow + 1;
        $previousBalanceRow = $additionalInfoRow + 1;

        $activeSheet->mergeCells(self::DATE_COLUMN . $startRow . ':' . self::LAST_COLUMN . $startRow)->setCellValue(self::DATE_COLUMN . $startRow, 'UNILEND');

        if (3 == $descriptionRow) {
            $activeSheet->setCellValue(self::DATE_COLUMN . $descriptionRow, $date->format('d/m/Y'));
            $activeSheet->setCellValue(self::DATE_COLUMN . $additionalInfoRow, 'Début du mois');
        } else {
            $activeSheet->setCellValue(self::DATE_COLUMN . $descriptionRow, $date->format('Y'));
            $activeSheet->setCellValue(self::DATE_COLUMN . $additionalInfoRow, 'Début d\'année');
        }

        /** incoming feeds section */
        $activeSheet->mergeCells(self::LENDER_PROVISION_CARD_COLUMN . $bigSectionsRow . ':' . self::TOTAL_INCOMING_COLUMN . $bigSectionsRow)
            ->setCellValue(self::LENDER_PROVISION_CARD_COLUMN . $bigSectionsRow, 'FLUX ENTRANTS');
        $activeSheet->mergeCells(self::LENDER_PROVISION_CARD_COLUMN . $descriptionRow . ':' . self::LENDER_PROVISION_WIRE_TRANSFER_COLUMN . $descriptionRow)
            ->setCellValue(self::LENDER_PROVISION_CARD_COLUMN . $descriptionRow, 'Chargements comptes prêteurs');
        $activeSheet->setCellValue(self::LENDER_PROVISION_CARD_COLUMN . $additionalInfoRow, 'Carte bancaire');
        $activeSheet->setCellValue(self::LENDER_PROVISION_WIRE_TRANSFER_COLUMN . $additionalInfoRow, 'Virement');
        $activeSheet->mergeCells(self::BORROWER_PROVISION_DIRECT_DEBIT_COLUMN . $descriptionRow . ':' . self::BORROWER_PROVISION_OTHER_COLUMN . $descriptionRow)
            ->setCellValue(self::BORROWER_PROVISION_DIRECT_DEBIT_COLUMN . $descriptionRow, 'Chargement comptes emprunteurs');
        $activeSheet->setCellValue(self::BORROWER_PROVISION_DIRECT_DEBIT_COLUMN . $additionalInfoRow, 'Prélèvement');
        $activeSheet->setCellValue(self::BORROWER_PROVISION_WIRE_TRANSFER_COLUMN . $additionalInfoRow, 'Virement');
        $activeSheet->setCellValue(self::BORROWER_PROVISION_OTHER_COLUMN . $additionalInfoRow, 'Autres');
        $activeSheet->setCellValue(self::PROMOTIONAL_OFFER_PROVISION_COLUMN . $descriptionRow, 'Chargement promotion Unilend');
        $activeSheet->setCellValue(self::PROMOTIONAL_OFFER_PROVISION_COLUMN . $additionalInfoRow, 'Virement');
        $activeSheet->setCellValue(self::TOTAL_INCOMING_COLUMN . $descriptionRow, 'Total flux entrants');

        /** outgoing feeds section */
        $activeSheet->mergeCells(self::TAX_WITHDRAW_COLUMN_NEW . $bigSectionsRow . ':' . self::TOTAL_OUTGOING_COLUMN . $bigSectionsRow)
            ->setCellValue(self::TAX_WITHDRAW_COLUMN_NEW . $bigSectionsRow, 'FLUX SORTANTS');
        $activeSheet->setCellValue(self::TAX_WITHDRAW_COLUMN_NEW . $descriptionRow, 'Retrait administration fiscale');
        $activeSheet->setCellValue(self::TAX_WITHDRAW_COLUMN_NEW . $additionalInfoRow, 'Virement');
        $activeSheet->setCellValue(self::LENDER_WITHDRAW_COLUMN_NEW . $descriptionRow, 'Retrait prêteurs');
        $activeSheet->setCellValue(self::LENDER_WITHDRAW_COLUMN_NEW . $additionalInfoRow, 'Virement');
        $activeSheet->setCellValue(self::UNILEND_WITHDRAW_COLUMN_NEW . $descriptionRow, 'Retrait Unilend');
        $activeSheet->setCellValue(self::UNILEND_WITHDRAW_COLUMN_NEW . $additionalInfoRow, 'Virement');
        $activeSheet->setCellValue(self::DEBT_COLLECTOR_WITHDRAW_COLUMN . $descriptionRow, 'Retrait Recouvreurs');
        $activeSheet->setCellValue(self::DEBT_COLLECTOR_WITHDRAW_COLUMN . $additionalInfoRow, 'Virement');
        $activeSheet->setCellValue(self::BORROWER_WITHDRAW_FUNDS_COLUMN . $descriptionRow, 'Octroi prêt');
        $activeSheet->setCellValue(self::BORROWER_WITHDRAW_FUNDS_COLUMN . $additionalInfoRow, 'Virement');
        $activeSheet->setCellValue(self::BORROWER_WITHDRAW_OTHER_COLUMN . $descriptionRow, 'Retrait Emprunteur');
        $activeSheet->setCellValue(self::BORROWER_WITHDRAW_OTHER_COLUMN . $additionalInfoRow, 'Virement');
        $activeSheet->setCellValue(self::TOTAL_OUTGOING_COLUMN . $descriptionRow, 'Total flux sortants');

        $activeSheet->setCellValue(self::TOTAL_FINANCIAL_MOVEMENTS_COLUMN . $descriptionRow, 'Total mouvements');

        /** balance section */
        $activeSheet->mergeCells(self::THEORETICAL_BALANCE_COLUMN . $bigSectionsRow . ':' . self::TAX_BALANCE_COLUMN . $bigSectionsRow)
            ->setCellValue(self::THEORETICAL_BALANCE_COLUMN . $bigSectionsRow, 'SOLDES');
        $activeSheet->setCellValue(self::THEORETICAL_BALANCE_COLUMN . $descriptionRow, 'Solde théorique');
        $activeSheet->setCellValue(self::BALANCE_COLUMN . $descriptionRow, 'Solde réel');
        $activeSheet->setCellValue(self::BALANCE_DIFFERENCE_COLUMN . $descriptionRow, 'Ecart global');
        $activeSheet->setCellValue(self::LENDER_BALANCE_COLUMN . $descriptionRow, 'Solde Preteurs');
        $activeSheet->setCellValue(self::BORROWER_BALANCE_COLUMN . $descriptionRow, 'Solde Emprunteurs');
        $activeSheet->setCellValue(self::DEBT_COLLECTOR_BALANCE_COLUMN . $descriptionRow, 'Solde Recouvreurs');
        $activeSheet->setCellValue(self::UNILEND_PROMOTIONAL_BALANCE_COLUMN . $descriptionRow, 'Solde Promotions');
        $activeSheet->setCellValue(self::UNILEND_BALANCE_COLUMN . $descriptionRow, 'Solde Unilend');
        $activeSheet->setCellValue(self::TAX_BALANCE_COLUMN . $descriptionRow, 'Solde Admin. Fiscale');

        /** internal movements section */
        $activeSheet->mergeCells(self::LENDER_LOAN_COLUMN . $bigSectionsRow . ':' . self::LAST_COLUMN . $bigSectionsRow)
            ->setCellValue(self::LENDER_LOAN_COLUMN . $bigSectionsRow, 'MOUVEMENTS INTERNES');
        $activeSheet->setCellValue(self::LENDER_LOAN_COLUMN . $descriptionRow, 'Octroi prêt');
        $activeSheet->setCellValue(self::UNILEND_COMMISSION_FUNDS_COLUMN . $descriptionRow, 'Commission octroi prêt');
        $activeSheet->setCellValue(self::CAPITAL_REPAYMENT_COLUMN . $descriptionRow, 'Retour prêteur (Capital)');
        $activeSheet->setCellValue(self::GROSS_INTEREST_COLUMN . $descriptionRow, 'Retour prêteur (Intêréts bruts)');
        $activeSheet->setCellValue(self::STATUTORY_CONTRIBUTIONS_COLUMN . $descriptionRow, 'Prélèvements obligatoires');
        $activeSheet->setCellValue(self::INCOME_TAX_COLUMN . $descriptionRow, 'Retenues à la source');
        $activeSheet->setCellValue(self::CSG_COLUMN . $descriptionRow, 'CSG');
        $activeSheet->setCellValue(self::SOCIAL_DEDUCTIONS_COLUMN . $descriptionRow, 'Prélèvements sociaux');
        $activeSheet->setCellValue(self::ADDITIONAL_CONTRIBUTIONS_COLUMN . $descriptionRow, 'Contributions additionnelles');
        $activeSheet->setCellValue(self::SOLIDARITY_DEDUCTIONS_COLUMN . $descriptionRow, 'Prélèvements solidarité');
        $activeSheet->setCellValue(self::CRDS_COLUMN . $descriptionRow, 'CRDS');
        $activeSheet->setCellValue(self::TOTAL_TAX_COLUMN . $descriptionRow, 'Total retenues fiscales');
        $activeSheet->setCellValue(self::UNILEND_COMMISSION_REPAYMENT . $descriptionRow, 'Commission sur echéance');
        $activeSheet->setCellValue(self::PAYMENT_ASSIGNMENT_COLUMN . $descriptionRow, 'Affectation Ech. Empr.');
        $activeSheet->setCellValue(self::FISCAL_DIFFERENCE_COLUMN . $descriptionRow, 'Ecart fiscal');
        $activeSheet->setCellValue(self::DEBT_COLLECTOR_COMMISSION_COLUMN . $descriptionRow, 'Commission Recouvreur');
        $activeSheet->setCellValue(self::BORROWER_CHARGE_REPAYMENT_COLUMN . $descriptionRow, 'Frais remboursés à Unilend');
        $activeSheet->setCellValue(self::PROMOTION_OFFER_DISTRIBUTION_COLUMN . $descriptionRow, 'Offre promo');
    }

    /**
     * @param \PHPExcel_Worksheet $activeSheet
     * @param \DateTime           $firstDay
     * @param \DateTime           $requestedDate
     * @param                     $row
     *
     * @return array
     * @throws \PHPExcel_Exception
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
     * @throws \PHPExcel_Exception
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
     *
     * @throws \PHPExcel_Exception
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
            ->getNumberFormat()
            ->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
        $activeSheet->getStyle(self::DATE_COLUMN . 1 . ':' . self::LAST_COLUMN . $specificRows['totalMonth'])
            ->getAlignment()
            ->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);

        $this->formatHeader($activeSheet, $specificRows['headerStartDay']);
        $this->formatHeader($activeSheet, $specificRows['headerStartMonth']);

        $activeSheet->getStyle(self::DATE_COLUMN . $specificRows['totalMonth'] . ':' . self::LAST_COLUMN . $specificRows['totalMonth'])
            ->getFont()
            ->setBold(true);
        $activeSheet->getStyle(self::DATE_COLUMN . $specificRows['totalDay'] . ':' . self::LAST_COLUMN . $specificRows['totalDay'])
            ->getFont()
            ->setBold(true);

        $activeSheet->setCellValue(self::DATE_COLUMN . $specificRows['totalDay'], 'Total mois');
        $activeSheet->setCellValue(self::DATE_COLUMN . $specificRows['totalMonth'], 'Total année');
    }

    /**
     * @param \PHPExcel_Worksheet $activeSheet
     * @param int                 $headerStartRow
     *
     * @throws \PHPExcel_Exception
     */
    private function formatHeader(\PHPExcel_Worksheet $activeSheet, $headerStartRow)
    {
        $activeSheet->getStyle(self::DATE_COLUMN . $headerStartRow . ':' . self::LAST_COLUMN . $headerStartRow)
            ->getAlignment()
            ->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        $activeSheet->getStyle(self::DATE_COLUMN . $headerStartRow)
            ->getFont()
            ->setBold(true)
            ->setSize(18)
            ->setItalic(true);

        $bigSectionsRow    = $headerStartRow + 1;
        $descriptionRow    = $bigSectionsRow + 1;
        $additionalInfoRow = $descriptionRow + 1;

        $activeSheet->getStyle(self::DATE_COLUMN . $bigSectionsRow . ':' . self::LAST_COLUMN . $bigSectionsRow)
            ->getFont()
            ->setBold(true);

        $activeSheet->getStyle(self::DATE_COLUMN . $bigSectionsRow . ':' . self::LAST_COLUMN . $bigSectionsRow)
            ->getAlignment()
            ->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        $activeSheet->getStyle(self::DATE_COLUMN . $descriptionRow . ':' . self::LAST_COLUMN . $descriptionRow)
            ->getFont()
            ->setBold(true);

        $activeSheet->getStyle(self::DATE_COLUMN . $descriptionRow . ':' . self::LAST_COLUMN . $descriptionRow)
            ->getAlignment()
            ->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        $activeSheet->getRowDimension($bigSectionsRow)
            ->setRowHeight(self::ROW_HEIGHT * 1.25);

        $activeSheet->getRowDimension($descriptionRow)
            ->setRowHeight(self::ROW_HEIGHT * 2.5);

        $activeSheet->getStyle(self::LENDER_PROVISION_CARD_COLUMN . $descriptionRow . ':' . self::LAST_COLUMN . $additionalInfoRow)
            ->getAlignment()
            ->setWrapText(true);

        $activeSheet->getStyle(self::DATE_COLUMN . $additionalInfoRow. ':' . self::TOTAL_FINANCIAL_MOVEMENTS_COLUMN . $additionalInfoRow)
            ->getAlignment()
            ->setHorizontal(\PHPExcel_Style_Alignment::VERTICAL_CENTER);

        $this->groupColumns($activeSheet, self::LENDER_PROVISION_CARD_COLUMN, self::PROMOTIONAL_OFFER_PROVISION_COLUMN);
        $this->groupColumns($activeSheet, self::TAX_WITHDRAW_COLUMN_NEW, self::BORROWER_WITHDRAW_OTHER_COLUMN);
        $this->groupColumns($activeSheet, self::STATUTORY_CONTRIBUTIONS_COLUMN, self::CRDS_COLUMN);
    }


    /**
     * @param \PHPExcel_Worksheet $activeSheet
     * @param string              $firstColumn
     * @param string              $lastColumn
     *
     * @throws \PHPExcel_Exception
     */
    private function groupColumns(\PHPExcel_Worksheet $activeSheet, $firstColumn, $lastColumn)
    {
        for ($column = $firstColumn; $column <= $lastColumn; ++$column) {
            $activeSheet->getColumnDimension($column)
                ->setOutlineLevel(1)
                ->setVisible(false)
                ->setCollapsed(true)
                ->setWidth(self::COLUMN_WIDTH);
        }
    }

    /**
     * @param \PHPExcel_Worksheet $activeSheet
     * @param array               $movements
     * @param int                 $row
     */
    private function addMovementLines(\PHPExcel_Worksheet $activeSheet, array $movements, $row)
    {
        foreach ($movements as $line) {
            $lenderProvisionCreditCard         = empty($line['lender_provision_credit_card']) ? 0 : $line['lender_provision_credit_card'];
            $lenderProvisionWireTransfer       = empty($line['lender_provision_wire_transfer_in']) ? 0 : $line['lender_provision_wire_transfer_in'];
            $lenderProvisionCancelCreditCard   = empty($line['lender_provision_cancel_credit_card']) ? 0 : $line['lender_provision_cancel_credit_card'];
            $lenderProvisionCancelWireTransfer = empty($line['lender_provision_cancel_wire_transfer_in']) ? 0 : $line['lender_provision_cancel_wire_transfer_in'];
            $totalLenderProvisionCreditCard    = round(bcsub($lenderProvisionCreditCard, $lenderProvisionCancelCreditCard, 4), 2);
            $totalLenderProvisionWireTransfer  = round(bcsub($lenderProvisionWireTransfer, $lenderProvisionCancelWireTransfer, 4), 2);
            $totalLenderProvision              = bcadd($totalLenderProvisionWireTransfer, $totalLenderProvisionCreditCard, 4);
            $activeSheet->setCellValueExplicit(self::LENDER_PROVISION_CARD_COLUMN . $row, $totalLenderProvisionCreditCard, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::LENDER_PROVISION_WIRE_TRANSFER_COLUMN . $row, $totalLenderProvisionWireTransfer, \PHPExcel_Cell_DataType::TYPE_NUMERIC);

            $borrowerProvisionDirectDebit        = empty($line['borrower_provision_direct_debit']) ? 0 : $line['borrower_provision_direct_debit'];
            $borrowerProvisionCancelDirectDebit  = empty($line['borrower_provision_cancel_direct_debit']) ? 0 : $line['borrower_provision_cancel_direct_debit'];
            $borrowerProvisionWireTransfer       = empty($line['borrower_provision_wire_transfer_in']) ? 0 : $line['borrower_provision_wire_transfer_in'];
            $borrowerProvisionCancelWireTransfer = empty($line['borrower_provision_cancel_wire_transfer_in']) ? 0 : $line['borrower_provision_cancel_wire_transfer_in'];
            $borrowerProvisionOther              = empty($line['borrower_provision_other']) ? 0 : $line['borrower_provision_other'];
            $borrowerProvisionCancelOther        = empty($line['borrower_provision_cancel_other']) ? 0 : $line['borrower_provision_cancel_other'];
            $totalBorrowerProvisionDirectDebit   = round(bcsub($borrowerProvisionDirectDebit, $borrowerProvisionCancelDirectDebit, 4), 2);
            $totalBorrowerProvisionWireTransfer  = round(bcsub($borrowerProvisionWireTransfer, $borrowerProvisionCancelWireTransfer, 4), 2);
            $totalBorrowerProvisionOther         = round(bcsub($borrowerProvisionOther, $borrowerProvisionCancelOther, 4), 2);
            $totalBorrowerProvision              = bcadd(bcadd($totalBorrowerProvisionDirectDebit, $totalBorrowerProvisionWireTransfer, 4), $totalBorrowerProvisionOther, 4);
            $activeSheet->setCellValueExplicit(self::BORROWER_PROVISION_DIRECT_DEBIT_COLUMN . $row, $totalBorrowerProvisionDirectDebit, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::BORROWER_PROVISION_WIRE_TRANSFER_COLUMN . $row, $totalBorrowerProvisionWireTransfer, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::BORROWER_PROVISION_OTHER_COLUMN . $row, $totalBorrowerProvisionOther, \PHPExcel_Cell_DataType::TYPE_NUMERIC);

            $promotionProvision      = empty($line[OperationType::UNILEND_PROMOTIONAL_OPERATION_PROVISION]) ? 0 : $line[OperationType::UNILEND_PROMOTIONAL_OPERATION_PROVISION];
            $unilendProvision        = empty($line[OperationType::UNILEND_PROVISION]) ? 0 : $line[OperationType::UNILEND_PROVISION];
            $totalPromotionProvision = round(bcadd($unilendProvision, $promotionProvision, 4), 2);
            $activeSheet->setCellValueExplicit(self::PROMOTIONAL_OFFER_PROVISION_COLUMN . $row, $totalPromotionProvision, \PHPExcel_Cell_DataType::TYPE_NUMERIC);

            $totalProvision          = round(bcadd(bcadd($totalLenderProvision, $totalBorrowerProvision, 4), $totalPromotionProvision, 4), 2);
            $activeSheet->setCellValueExplicit(self::TOTAL_INCOMING_COLUMN . $row, $totalProvision, \PHPExcel_Cell_DataType::TYPE_NUMERIC);

            $statutoryContributionsWithdraw  = empty($line[OperationType::TAX_FR_PRELEVEMENTS_OBLIGATOIRES_WITHDRAW]) ? 0 : $line[OperationType::TAX_FR_PRELEVEMENTS_OBLIGATOIRES_WITHDRAW];
            $incomeTaxWithdraw               = empty($line[OperationType::TAX_FR_RETENUES_A_LA_SOURCE_WITHDRAW]) ? 0 : $line[OperationType::TAX_FR_RETENUES_A_LA_SOURCE_WITHDRAW];
            $csgWithdraw                     = empty($line[OperationType::TAX_FR_CSG_WITHDRAW]) ? 0 : $line[OperationType::TAX_FR_CSG_WITHDRAW];
            $socialDeductionsWithdraw        = empty($line[OperationType::TAX_FR_PRELEVEMENTS_SOCIAUX_WITHDRAW]) ? 0 : $line[OperationType::TAX_FR_PRELEVEMENTS_SOCIAUX_WITHDRAW];
            $additionalContributionsWithdraw = empty($line[OperationType::TAX_FR_CONTRIBUTIONS_ADDITIONNELLES_WITHDRAW]) ? 0 : $line[OperationType::TAX_FR_CONTRIBUTIONS_ADDITIONNELLES_WITHDRAW];
            $solidarityDeductionsWithdraw    = empty($line[OperationType::TAX_FR_PRELEVEMENTS_DE_SOLIDARITE_WITHDRAW]) ? 0 : $line[OperationType::TAX_FR_PRELEVEMENTS_DE_SOLIDARITE_WITHDRAW];
            $crdsWithdraw                    = empty($line[OperationType::TAX_FR_CRDS_WITHDRAW]) ? 0 : $line[OperationType::TAX_FR_CRDS_WITHDRAW];
            $totalTaxWithdraw                = round(bcadd(bcadd(bcadd(bcadd(bcadd(bcadd($statutoryContributionsWithdraw, $incomeTaxWithdraw, 4), $csgWithdraw, 4), $socialDeductionsWithdraw, 4), $additionalContributionsWithdraw, 4), $solidarityDeductionsWithdraw, 4), $crdsWithdraw, 4), 2);
            $lenderWithdraw                  = empty($line[OperationType::LENDER_WITHDRAW]) ? 0 : $line[OperationType::LENDER_WITHDRAW];
            $unilendWithdraw                 = empty($line[OperationType::UNILEND_WITHDRAW]) ? 0 : $line[OperationType::UNILEND_WITHDRAW];
            $debtCollectorWithdraw           = empty($line[OperationType::DEBT_COLLECTOR_WITHDRAW]) ? 0 : $line[OperationType::DEBT_COLLECTOR_WITHDRAW];
            $borrowerWithdraw                = empty($line[OperationType::BORROWER_WITHDRAW]) ? 0 : $line[OperationType::BORROWER_WITHDRAW];
            $totalWithdraw                   = round(bcadd(bcadd(bcadd(bcadd($totalTaxWithdraw, $lenderWithdraw, 4), $unilendWithdraw, 4), $debtCollectorWithdraw, 4), $borrowerWithdraw, 4), 2);
            $activeSheet->setCellValueExplicit(self::TAX_WITHDRAW_COLUMN_NEW . $row, $totalTaxWithdraw, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::LENDER_WITHDRAW_COLUMN_NEW . $row, $lenderWithdraw, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::UNILEND_WITHDRAW_COLUMN_NEW . $row, $unilendWithdraw, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::DEBT_COLLECTOR_WITHDRAW_COLUMN . $row, $debtCollectorWithdraw, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::BORROWER_WITHDRAW_FUNDS_COLUMN . $row, $borrowerWithdraw, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::BORROWER_WITHDRAW_OTHER_COLUMN . $row, 0, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::TOTAL_OUTGOING_COLUMN . $row, $totalWithdraw, \PHPExcel_Cell_DataType::TYPE_NUMERIC);

            $totalMovements = round(bcsub($totalProvision, $totalWithdraw, 4), 2);
            $activeSheet->setCellValueExplicit(self::TOTAL_FINANCIAL_MOVEMENTS_COLUMN . $row, $totalMovements, \PHPExcel_Cell_DataType::TYPE_NUMERIC);

            $loans                                   = empty($line[OperationType::LENDER_LOAN]) ? 0 : $line[OperationType::LENDER_LOAN];
            $borrowerCommissionProject               = empty($line[OperationSubType::BORROWER_COMMISSION_FUNDS]) ? 0 : $line[OperationSubType::BORROWER_COMMISSION_FUNDS];
            $borrowerCommissionProjectRegularization = empty($line[OperationSubType::BORROWER_COMMISSION_FUNDS_REGULARIZATION]) ? 0 : $line[OperationSubType::BORROWER_COMMISSION_FUNDS_REGULARIZATION];
            $totalProjectCommission                  = round(bcsub($borrowerCommissionProject, $borrowerCommissionProjectRegularization, 4), 2);
            $activeSheet->setCellValueExplicit(self::LENDER_LOAN_COLUMN . $row, $loans, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::UNILEND_COMMISSION_FUNDS_COLUMN . $row, $totalProjectCommission, \PHPExcel_Cell_DataType::TYPE_NUMERIC);

            $capitalRepayment               = empty($line[OperationType::CAPITAL_REPAYMENT]) ? 0 : $line[OperationType::CAPITAL_REPAYMENT];
            $capitalRepaymentRegularization = empty($line[OperationType::CAPITAL_REPAYMENT_REGULARIZATION]) ? 0 : $line[OperationType::CAPITAL_REPAYMENT_REGULARIZATION];
            $grossInterest                  = empty($line[OperationType::GROSS_INTEREST_REPAYMENT]) ? 0 : $line[OperationType::GROSS_INTEREST_REPAYMENT];
            $grossInterestRegularization    = empty($line[OperationType::GROSS_INTEREST_REPAYMENT_REGULARIZATION]) ? 0 : $line[OperationType::GROSS_INTEREST_REPAYMENT_REGULARIZATION];
            $totalCapitalRepayment          = round(bcsub($capitalRepayment, $capitalRepaymentRegularization, 4), 2);
            $totalGrossInterest             = round(bcsub($grossInterest, $grossInterestRegularization, 4), 2);
            $activeSheet->setCellValueExplicit(self::CAPITAL_REPAYMENT_COLUMN . $row, $totalCapitalRepayment, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::GROSS_INTEREST_COLUMN . $row, $totalGrossInterest, \PHPExcel_Cell_DataType::TYPE_NUMERIC);

            $statutoryContributions                = empty($line[OperationType::TAX_FR_PRELEVEMENTS_OBLIGATOIRES]) ? 0 : $line[OperationType::TAX_FR_PRELEVEMENTS_OBLIGATOIRES];
            $statutoryContributionsRegularization  = empty($line[OperationType::TAX_FR_PRELEVEMENTS_OBLIGATOIRES_REGULARIZATION]) ? 0 : $line[OperationType::TAX_FR_PRELEVEMENTS_OBLIGATOIRES_REGULARIZATION];
            $incomeTax                             = empty($line[OperationType::TAX_FR_RETENUES_A_LA_SOURCE]) ? 0 : $line[OperationType::TAX_FR_RETENUES_A_LA_SOURCE];
            $incomeTaxRegularization               = empty($line[OperationType::TAX_FR_RETENUES_A_LA_SOURCE_REGULARIZATION]) ? 0 : $line[OperationType::TAX_FR_RETENUES_A_LA_SOURCE_REGULARIZATION];
            $csg                                   = empty($line[OperationType::TAX_FR_CSG]) ? 0 : $line[OperationType::TAX_FR_CSG];
            $csgRegularization                     = empty($line[OperationType::TAX_FR_CSG_REGULARIZATION]) ? 0 : $line[OperationType::TAX_FR_CSG_REGULARIZATION];
            $socialDeductions                      = empty($line[OperationType::TAX_FR_PRELEVEMENTS_SOCIAUX]) ? 0 : $line[OperationType::TAX_FR_PRELEVEMENTS_SOCIAUX];
            $socialDeductionsRegularization        = empty($line[OperationType::TAX_FR_PRELEVEMENTS_SOCIAUX_REGULARIZATION]) ? 0 : $line[OperationType::TAX_FR_PRELEVEMENTS_SOCIAUX_REGULARIZATION];
            $additionalContributions               = empty($line[OperationType::TAX_FR_CONTRIBUTIONS_ADDITIONNELLES]) ? 0 : $line[OperationType::TAX_FR_CONTRIBUTIONS_ADDITIONNELLES];
            $additionalContributionsRegularization = empty($line[OperationType::TAX_FR_CONTRIBUTIONS_ADDITIONNELLES_REGULARIZATION]) ? 0 : $line[OperationType::TAX_FR_CONTRIBUTIONS_ADDITIONNELLES_REGULARIZATION];
            $solidarityDeductions                  = empty($line[OperationType::TAX_FR_PRELEVEMENTS_DE_SOLIDARITE]) ? 0 : $line[OperationType::TAX_FR_PRELEVEMENTS_DE_SOLIDARITE];
            $solidarityDeductionsRegularization    = empty($line[OperationType::TAX_FR_PRELEVEMENTS_DE_SOLIDARITE_REGULARIZATION]) ? 0 : $line[OperationType::TAX_FR_PRELEVEMENTS_DE_SOLIDARITE_REGULARIZATION];
            $crds                                  = empty($line[OperationType::TAX_FR_CRDS]) ? 0 : $line[OperationType::TAX_FR_CRDS];
            $crdsRegularization                    = empty($line[OperationType::TAX_FR_CRDS_REGULARIZATION]) ? 0 : $line[OperationType::TAX_FR_CRDS_REGULARIZATION];
            $totalStatutoryContributions           = round(bcsub($statutoryContributions, $statutoryContributionsRegularization, 4), 2);
            $totalIncomeTax                        = round(bcsub($incomeTax, $incomeTaxRegularization, 4), 2);
            $totalCsg                              = round(bcsub($csg, $csgRegularization, 4), 2);
            $totalSocialDeductions                 = round(bcsub($socialDeductions, $socialDeductionsRegularization, 4), 2);
            $totalAdditionalContributions          = round(bcsub($additionalContributions, $additionalContributionsRegularization, 4), 2);
            $totalSolidarityDeductions             = round(bcsub($solidarityDeductions, $solidarityDeductionsRegularization, 4), 2);
            $totalCrds                             = round(bcsub($crds, $crdsRegularization, 4), 2);
            $totalTax                              = round(bcadd($totalCrds, bcadd($totalSolidarityDeductions, bcadd($totalAdditionalContributions, bcadd($totalSocialDeductions, bcadd($totalCsg, bcadd($totalStatutoryContributions, $totalIncomeTax, 4), 4), 4), 4), 4), 4), 2);
            $activeSheet->setCellValueExplicit(self::STATUTORY_CONTRIBUTIONS_COLUMN . $row, $totalStatutoryContributions, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::INCOME_TAX_COLUMN . $row, $totalIncomeTax, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::CSG_COLUMN . $row, $totalCsg, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::SOCIAL_DEDUCTIONS_COLUMN . $row, $totalSocialDeductions, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::ADDITIONAL_CONTRIBUTIONS_COLUMN . $row, $totalAdditionalContributions, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::SOLIDARITY_DEDUCTIONS_COLUMN . $row, $totalSolidarityDeductions, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::CRDS_COLUMN . $row, $totalCrds, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::TOTAL_TAX_COLUMN . $row, $totalTax, \PHPExcel_Cell_DataType::TYPE_NUMERIC);

            $borrowerCommissionPayment               = empty($line[OperationSubType::BORROWER_COMMISSION_REPAYMENT]) ? 0 : $line[OperationSubType::BORROWER_COMMISSION_REPAYMENT];
            $borrowerCommissionPaymentRegularization = empty($line[OperationSubType::BORROWER_COMMISSION_REPAYMENT_REGULARIZATION]) ? 0 : $line[OperationSubType::BORROWER_COMMISSION_REPAYMENT_REGULARIZATION];
            $totalPaymentCommission                  = round(bcsub($borrowerCommissionPayment, $borrowerCommissionPaymentRegularization, 4), 2);
            $activeSheet->setCellValueExplicit(self::UNILEND_COMMISSION_REPAYMENT . $row, $totalPaymentCommission, \PHPExcel_Cell_DataType::TYPE_NUMERIC);

            $repaymentAssignment   = round(bcadd(bcadd(bcadd($totalCapitalRepayment, $totalGrossInterest, 4), $totalPaymentCommission, 4), $totalTax, 4), 2);
            $fiscalDifference      = round(bcsub($repaymentAssignment, bcadd($totalPaymentCommission, bcadd($totalCapitalRepayment, 4), 4), 4), 2);
            $activeSheet->setCellValueExplicit(self::PAYMENT_ASSIGNMENT_COLUMN . $row, $repaymentAssignment, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::FISCAL_DIFFERENCE_COLUMN . $row, $fiscalDifference, \PHPExcel_Cell_DataType::TYPE_NUMERIC);

            $collectionCommissionProvision              = empty($line[OperationType::COLLECTION_COMMISSION_PROVISION]) ? 0 : $line[OperationType::COLLECTION_COMMISSION_PROVISION];
            $collectionCommissionLender                 = empty($line[OperationType::COLLECTION_COMMISSION_LENDER]) ? 0 : $line[OperationType::COLLECTION_COMMISSION_LENDER];
            $collectionCommissionLenderRegularization   = empty($line[OperationType::COLLECTION_COMMISSION_LENDER_REGULARIZATION]) ? 0 : $line[OperationType::COLLECTION_COMMISSION_LENDER_REGULARIZATION];
            $collectionCommissionBorrower               = empty($line[OperationType::COLLECTION_COMMISSION_BORROWER]) ? 0 : $line[OperationType::COLLECTION_COMMISSION_BORROWER];
            $collectionCommissionBorrowerRegularization = empty($line[OperationType::COLLECTION_COMMISSION_BORROWER_REGULARIZATION]) ? 0 : $line[OperationType::COLLECTION_COMMISSION_BORROWER_REGULARIZATION];
            $borrowerChargeRepayment                    = empty($line[OperationType::BORROWER_PROJECT_CHARGE_REPAYMENT]) ? 0 : $line[OperationType::BORROWER_PROJECT_CHARGE_REPAYMENT];
            $totalCollectionCommission                  = round(bcsub(bcadd(bcsub($collectionCommissionLender, $collectionCommissionLenderRegularization, 4), bcsub($collectionCommissionBorrower, $collectionCommissionBorrowerRegularization, 4), 4), $collectionCommissionProvision, 4), 2);
            $activeSheet->setCellValueExplicit(self::DEBT_COLLECTOR_COMMISSION_COLUMN . $row, $totalCollectionCommission, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::BORROWER_CHARGE_REPAYMENT_COLUMN . $row, $borrowerChargeRepayment, \PHPExcel_Cell_DataType::TYPE_NUMERIC);

            $promotionalOffers       = empty($line[OperationType::UNILEND_PROMOTIONAL_OPERATION]) ? 0 : $line[OperationType::UNILEND_PROMOTIONAL_OPERATION];
            $promotionalOffersCancel = empty($line[OperationType::UNILEND_PROMOTIONAL_OPERATION_CANCEL]) ? 0 : $line[OperationType::UNILEND_PROMOTIONAL_OPERATION_CANCEL];
            $commercialGestures      = empty($line[OperationType::UNILEND_BORROWER_COMMERCIAL_GESTURE]) ? 0 : $line[OperationType::UNILEND_BORROWER_COMMERCIAL_GESTURE];
            $lenderRegularization    = empty($line[OperationType::UNILEND_LENDER_REGULARIZATION]) ? 0 : $line[OperationType::UNILEND_LENDER_REGULARIZATION];
            $totalPromotionOffer     = round(bcadd($lenderRegularization, bcadd($commercialGestures, bcsub($promotionalOffers, $promotionalOffersCancel, 4), 4), 4), 2);
            $activeSheet->setCellValueExplicit(self::PROMOTION_OFFER_DISTRIBUTION_COLUMN . $row, $totalPromotionOffer, \PHPExcel_Cell_DataType::TYPE_NUMERIC);

            $row++;
        }
    }

    /**
     * @param \PHPExcel_Worksheet              $activeSheet
     * @param DetailedDailyStateBalanceHistory $dailyBalances
     * @param                                  $row
     * @param array                            $specificRows
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \PHPExcel_Exception
     */
    private function addBalanceLine(\PHPExcel_Worksheet $activeSheet, DetailedDailyStateBalanceHistory $dailyBalances, $row, array $specificRows)
    {
        $isPreviousLine        = in_array($row, [$specificRows['previousMonth'], $specificRows['previousYear']]);
        $isTotal               = in_array($row, [$specificRows['totalDay'], $specificRows['totalMonth']]);

        $lenderBorrowerBalance = bcadd($dailyBalances->getLenderBalance(), $dailyBalances->getBorrowerBalance(), 4);
        $unilendBalance        = bcadd($dailyBalances->getUnilendPromotionalBalance(), $dailyBalances->getUnilendBalance(), 4);
        $realBalance           = round(bcadd($dailyBalances->getTaxBalance(), bcadd($unilendBalance, bcadd($lenderBorrowerBalance, $dailyBalances->getDebtCollectorBalance(), 4), 4), 4), 2);

        $activeSheet->setCellValueExplicit(self::BALANCE_COLUMN . $row, $realBalance, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::UNILEND_PROMOTIONAL_BALANCE_COLUMN . $row, $dailyBalances->getUnilendPromotionalBalance(), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::LENDER_BALANCE_COLUMN . $row, $dailyBalances->getLenderBalance(), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::BORROWER_BALANCE_COLUMN . $row, $dailyBalances->getBorrowerBalance(), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::DEBT_COLLECTOR_BALANCE_COLUMN . $row, $dailyBalances->getDebtCollectorBalance(), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::UNILEND_BALANCE_COLUMN . $row, $dailyBalances->getUnilendBalance(), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit(self::TAX_BALANCE_COLUMN . $row, $dailyBalances->getTaxBalance(), \PHPExcel_Cell_DataType::TYPE_NUMERIC);

        if (null !== $dailyBalances->getTheoreticalBalance() && $dailyBalances->getTheoreticalBalance() !== $realBalance) {
            $difference = round(bcsub($realBalance, $dailyBalances->getTheoreticalBalance(), 4), 2);
            if ($difference == $dailyBalances->getTaxBalance()) {
                $this->addTheoreticalBalanceToHistory($realBalance, $dailyBalances);
            }
        }

        if ($isPreviousLine) {
            $globalDifference = round(bcsub($dailyBalances->getTheoreticalBalance(), $realBalance, 4), 2);
            $activeSheet->setCellValueExplicit(self::THEORETICAL_BALANCE_COLUMN . $row, $dailyBalances->getTheoreticalBalance(), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::BALANCE_DIFFERENCE_COLUMN . $row, $globalDifference, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        }

        if (false === $isTotal && false === $isPreviousLine) {
            if (null === $dailyBalances->getTheoreticalBalance()) {
                $previousRow        = $row - 1;
                $previousBalance    = $activeSheet->getCell(self::THEORETICAL_BALANCE_COLUMN . $previousRow)->getValue();
                $totalMovements     = $activeSheet->getCell(self::TOTAL_FINANCIAL_MOVEMENTS_COLUMN . $row)->getValue();
                $theoreticalBalance = round(bcadd($previousBalance, $totalMovements, 4), 2);
                $this->addTheoreticalBalanceToHistory($theoreticalBalance, $dailyBalances);
            }

            $activeSheet->setCellValueExplicit(self::THEORETICAL_BALANCE_COLUMN . $row, $dailyBalances->getTheoreticalBalance(), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::BALANCE_DIFFERENCE_COLUMN . $row, round(bcsub($dailyBalances->getTheoreticalBalance(), $realBalance, 4), 2), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        }

        if ($isTotal) {
            if ($row == $specificRows['totalDay']) {
                $lastNotEmptyRow    = $specificRows['coordinatesDay'][$dailyBalances->getDate()->format('Y-m-d')];
                $theoreticalBalance = $activeSheet->getCell(self::THEORETICAL_BALANCE_COLUMN . $lastNotEmptyRow)->getValue();
            }

            if ($row == $specificRows['totalMonth']) {
                $month              = $dailyBalances->getDate()->format('n');
                $lastNotEmptyRow    = $specificRows['coordinatesMonth'][$month];
                $theoreticalBalance = $activeSheet->getCell(self::THEORETICAL_BALANCE_COLUMN . $lastNotEmptyRow)->getValue();
            }
            $globalDifference = $activeSheet->getCell(self::BALANCE_DIFFERENCE_COLUMN . $lastNotEmptyRow)->getValue();
            $activeSheet->setCellValueExplicit(self::THEORETICAL_BALANCE_COLUMN . $row, $theoreticalBalance, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit(self::BALANCE_DIFFERENCE_COLUMN . $row, $globalDifference, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        }
    }

    /**
     * @param string                           $theoreticalBalance
     * @param DetailedDailyStateBalanceHistory $dailyBalances
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function addTheoreticalBalanceToHistory($theoreticalBalance, DetailedDailyStateBalanceHistory $dailyBalances)
    {
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        $dailyBalances->setTheoreticalBalance($theoreticalBalance);

        $entityManager->flush($dailyBalances);
        $entityManager->refresh($dailyBalances);
    }

    /**
     * @param $filePath
     *
     * @throws \Swift_RfcComplianceException
     */
    private function sendFileToInternalRecipients($filePath)
    {
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        /** @var Settings $recipientSetting */
        $recipientSetting = $entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Adresse notification etat quotidien']);

        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->getContainer()->get('unilend.swiftmailer.message_provider')->newMessage('notification-etat-quotidien', [], false);
        $message->setSubject('Etat Quotidien avec soldes détaillés')
            ->setTo(explode(';', trim($recipientSetting->getValue())))
            ->attach(\Swift_Attachment::fromPath($filePath));

        /** @var \Swift_Mailer $mailer */
        $mailer = $this->getContainer()->get('mailer');
        $mailer->send($message);
    }
}
