<?php

namespace Unilend\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\{
    InputArgument, InputInterface, InputOption
};
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Entity\{Operation, OperationSubType, OperationType, Settings, TaxType, UnderlyingContract, Wallet, WalletBalanceHistory};

class FeedsFiscalStateCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure(): void
    {
        $this
            ->setName('feeds:fiscal_state')
            ->setDescription('Generate the fiscal state file')
            ->addArgument('month', InputArgument::OPTIONAL, 'The month that you want to generate the fiscal state. Support format : yyyy-mm, yyyy-mm-dd', 'last month')
            ->addOption('withdraw', null, InputOption::VALUE_NONE, 'withdraw the tax wallets at the end of generation');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $entityManager       = $this->getContainer()->get('doctrine.orm.entity_manager');
        $operationRepository = $entityManager->getRepository(Operation::class);

        /** @var TaxType[] $frenchTaxes */
        $frenchTaxes = $entityManager->getRepository(TaxType::class)->findBy(['country' => 'fr']);
        $taxRate     = [];
        foreach ($frenchTaxes as $tax) {
            $taxRate[$tax->getIdTaxType()] = $tax->getRate();
        }

        $month = $input->getArgument('month');
        if ($month = $this->validateDate($month)) {
            $firstDayOfLastMonth = new \DateTime('first day of ' . $month);
            $lastDayOfLastMonth  = new \DateTime('last day of ' . $month);
        } else {
            $output->writeln('<error>Wrong date format. Support format : yyyy-mm, yyyy-mm-dd, or "last month"</error>');
            return;
        }

        /***** TAX *****/
        $statutoryContributionsByContract            = $operationRepository->getTaxForFiscalState(OperationType::TAX_FR_PRELEVEMENTS_OBLIGATOIRES, null, $firstDayOfLastMonth, $lastDayOfLastMonth, true);
        $regularisedStatutoryContributionsByContract = $operationRepository->getTaxForFiscalState(OperationType::TAX_FR_PRELEVEMENTS_OBLIGATOIRES, null, $firstDayOfLastMonth, $lastDayOfLastMonth, true, true);
        $statutoryContributionsByContract            = array_combine(array_column($statutoryContributionsByContract, 'contract_label'), array_values($statutoryContributionsByContract));
        $regularisedStatutoryContributionsByContract = array_combine(array_column($regularisedStatutoryContributionsByContract, 'contract_label'), array_values($regularisedStatutoryContributionsByContract));

        $deductionAtSourceLegalEntity            = $operationRepository->getTaxForFiscalState(OperationType::TAX_FR_RETENUES_A_LA_SOURCE, null, $firstDayOfLastMonth, $lastDayOfLastMonth);
        $regularisedDeductionAtSourceLegalEntity = $operationRepository->getTaxForFiscalState(OperationType::TAX_FR_RETENUES_A_LA_SOURCE, null, $firstDayOfLastMonth, $lastDayOfLastMonth, false, true);

        $deductionAtSourcePerson                 = $operationRepository->getTaxForFiscalState(OperationType::TAX_FR_RETENUES_A_LA_SOURCE, OperationSubType::TAX_FR_RETENUES_A_LA_SOURCE_PERSON, $firstDayOfLastMonth, $lastDayOfLastMonth);
        $regularisedDeductionAtSourcePerson      = $operationRepository->getTaxForFiscalState(OperationType::TAX_FR_RETENUES_A_LA_SOURCE, OperationSubType::TAX_FR_RETENUES_A_LA_SOURCE_PERSON_REGULARIZATION, $firstDayOfLastMonth, $lastDayOfLastMonth, false, true);

        $csg            = $operationRepository->getTaxForFiscalState(OperationType::TAX_FR_CSG, null, $firstDayOfLastMonth, $lastDayOfLastMonth);
        $regularisedCsg = $operationRepository->getTaxForFiscalState(OperationType::TAX_FR_CSG, null, $firstDayOfLastMonth, $lastDayOfLastMonth, false, true);

        $socialDeduction            = $operationRepository->getTaxForFiscalState(OperationType::TAX_FR_PRELEVEMENTS_SOCIAUX, null, $firstDayOfLastMonth, $lastDayOfLastMonth);
        $regularisedSocialDeduction = $operationRepository->getTaxForFiscalState(OperationType::TAX_FR_PRELEVEMENTS_SOCIAUX, null, $firstDayOfLastMonth, $lastDayOfLastMonth, false, true);

        $additionalContribution            = $operationRepository->getTaxForFiscalState(OperationType::TAX_FR_CONTRIBUTIONS_ADDITIONNELLES, null, $firstDayOfLastMonth, $lastDayOfLastMonth);
        $regularisedAdditionalContribution = $operationRepository->getTaxForFiscalState(OperationType::TAX_FR_CONTRIBUTIONS_ADDITIONNELLES, null, $firstDayOfLastMonth, $lastDayOfLastMonth, false, true);

        $solidarityDeduction            = $operationRepository->getTaxForFiscalState(OperationType::TAX_FR_PRELEVEMENTS_DE_SOLIDARITE, null, $firstDayOfLastMonth, $lastDayOfLastMonth);
        $regularisedSolidarityDeduction = $operationRepository->getTaxForFiscalState(OperationType::TAX_FR_PRELEVEMENTS_DE_SOLIDARITE, null, $firstDayOfLastMonth, $lastDayOfLastMonth, false, true);

        $crds            = $operationRepository->getTaxForFiscalState(OperationType::TAX_FR_CRDS, null, $firstDayOfLastMonth, $lastDayOfLastMonth);
        $regularisedCrds = $operationRepository->getTaxForFiscalState(OperationType::TAX_FR_CRDS, null, $firstDayOfLastMonth, $lastDayOfLastMonth, false, true);

        $statutoryContributions            = $operationRepository->getTaxForFiscalState(OperationType::TAX_FR_PRELEVEMENTS_OBLIGATOIRES, null, $firstDayOfLastMonth, $lastDayOfLastMonth);
        $regularisedStatutoryContributions = $operationRepository->getTaxForFiscalState(OperationType::TAX_FR_PRELEVEMENTS_OBLIGATOIRES, null, $firstDayOfLastMonth, $lastDayOfLastMonth, false, true);

        $exemptedIncome            = $operationRepository->getExemptedIncomeTax($firstDayOfLastMonth, $lastDayOfLastMonth);
        $regularisedExemptedIncome = $operationRepository->getExemptedIncomeTax($firstDayOfLastMonth, $lastDayOfLastMonth, true);

        $statutoryContributionsTaxBDC     = $this->getTaxFromGroupedRawData($statutoryContributionsByContract, $regularisedStatutoryContributionsByContract, UnderlyingContract::CONTRACT_BDC);
        $statutoryContributionsTaxIFP     = $this->getTaxFromGroupedRawData($statutoryContributionsByContract, $regularisedStatutoryContributionsByContract, UnderlyingContract::CONTRACT_IFP);
        $statutoryContributionsTaxMiniBon = $this->getTaxFromGroupedRawData($statutoryContributionsByContract, $regularisedStatutoryContributionsByContract, UnderlyingContract::CONTRACT_MINIBON);
        $statutoryContributionsTax        = $this->getTaxFromRawData($statutoryContributions, $regularisedStatutoryContributions);
        $deductionAtSourceTaxLegalEntity  = $this->getTaxFromRawData($deductionAtSourceLegalEntity, $regularisedDeductionAtSourceLegalEntity);
        $deductionAtSourceTaxPerson       = $this->getTaxFromRawData($deductionAtSourcePerson, $regularisedDeductionAtSourcePerson);
        $csgTax                           = $this->getTaxFromRawData($csg, $regularisedCsg);
        $socialDeductionTax               = $this->getTaxFromRawData($socialDeduction, $regularisedSocialDeduction);
        $additionalContributionTax        = $this->getTaxFromRawData($additionalContribution, $regularisedAdditionalContribution);
        $solidarityDeductionTax           = $this->getTaxFromRawData($solidarityDeduction, $regularisedSolidarityDeduction);
        $crdsTax                          = $this->getTaxFromRawData($crds, $regularisedCrds);
        $exemptedIncomeTax                = $this->getTaxFromRawData($exemptedIncome, $regularisedExemptedIncome);

        /***** Interests *****/
        $interestsRawData                       = $operationRepository->getInterestFiscalState($firstDayOfLastMonth, $lastDayOfLastMonth);
        $statutoryContributionsInterestsBDC     = 0;
        $statutoryContributionsInterestsIFP     = 0;
        $statutoryContributionsInterestsMiniBon = 0;
        $exemptedInterests                      = 0;
        $deductionAtSourceInterestsLegalEntity  = 0;
        $deductionAtSourceInterestsPerson       = 0;

        foreach ($interestsRawData as $row) {
            /** @var UnderlyingContract $contract */
            $contract = $entityManager->getRepository(UnderlyingContract::class)->find($row['id_type_contract']);
            if ('person' == $row['client_type'] && 'fr' == $row['fiscal_residence'] && 'taxable' == $row['exemption_status']) {
                switch ($contract->getLabel()) {
                    case UnderlyingContract::CONTRACT_BDC:
                        $statutoryContributionsInterestsBDC = $row['interests'];
                        break;
                    case UnderlyingContract::CONTRACT_IFP:
                        $statutoryContributionsInterestsIFP = $row['interests'];
                        break;
                    case UnderlyingContract::CONTRACT_MINIBON:
                        $statutoryContributionsInterestsMiniBon = $row['interests'];
                        break;
                }
            }

            if ('person' == $row['client_type'] && 'fr' == $row['fiscal_residence'] && 'non_taxable' == $row['exemption_status']) {
                $exemptedInterests = round(bcadd($exemptedInterests, $row['interests'], 4), 2);
            }

            if ('legal_entity' == $row['client_type']) {
                $deductionAtSourceInterestsLegalEntity = round(bcadd($deductionAtSourceInterestsLegalEntity, $row['interests'], 4), 2);
            }

            if ('person' == $row['client_type'] && 'ww' == $row['fiscal_residence']) {
                $deductionAtSourceInterestsPerson = round(bcadd($deductionAtSourceInterestsPerson, $row['interests'], 4), 2);
            }
        }

        $regularisedInterestsRawData = $operationRepository->getInterestFiscalState($firstDayOfLastMonth, $lastDayOfLastMonth, true);
        foreach ($regularisedInterestsRawData as $row) {
            /** @var UnderlyingContract $contract */
            $contract = $entityManager->getRepository(UnderlyingContract::class)->find($row['id_type_contract']);
            if ('person' == $row['client_type'] && 'fr' == $row['fiscal_residence'] && 'taxable' == $row['exemption_status']) {
                switch ($contract->getLabel()) {
                    case UnderlyingContract::CONTRACT_BDC:
                        $statutoryContributionsInterestsBDC = round(bcsub($statutoryContributionsInterestsBDC, $row['interests'], 4), 2);
                        break;
                    case UnderlyingContract::CONTRACT_IFP:
                        $statutoryContributionsInterestsIFP = round(bcsub($statutoryContributionsInterestsIFP, $row['interests'], 4), 2);
                        break;
                    case UnderlyingContract::CONTRACT_MINIBON:
                        $statutoryContributionsInterestsMiniBon = round(bcsub($statutoryContributionsInterestsMiniBon, $row['interests'], 4), 2);
                        break;
                }
            }

            if ('person' == $row['client_type'] && 'fr' == $row['fiscal_residence'] && 'non_taxable' == $row['exemption_status']) {
                $exemptedInterests = round(bcsub($exemptedInterests, $row['interests'], 4), 2);
            }

            if ('legal_entity' == $row['client_type']) {
                $deductionAtSourceInterestsLegalEntity = round(bcsub($deductionAtSourceInterestsLegalEntity, $row['interests'], 4), 2);
            }

            if ('person' == $row['client_type'] && 'ww' == $row['fiscal_residence']) {
                $deductionAtSourceInterestsPerson = round(bcsub($deductionAtSourceInterestsPerson, $row['interests'], 4), 2);
            }
        }

        $totalFrPhysicalPersonInterest = round(bcadd(bcadd(bcadd($statutoryContributionsInterestsBDC, $statutoryContributionsInterestsIFP, 4), $statutoryContributionsInterestsMiniBon, 4), $exemptedInterests, 4), 2);

        $filePath = $this->getContainer()->getParameter('path.sftp') . 'sfpmei/emissions/etat_fiscal/Unilend_etat_fiscal_' . date('Ymd') . '.xlsx';

        try {
            /** @var \PHPExcel $document */
            $document = new \PHPExcel();
            $document->getDefaultStyle()->getFont()->setName('Calibri');
            $document->getDefaultStyle()->getFont()->setSize(12);

            $activeSheet = $document->setActiveSheetIndex(0);
            $activeSheet->getColumnDimension('A')->setWidth(45);
            $activeSheet->getColumnDimension('B')->setWidth(25);
            $activeSheet->getColumnDimension('C')->setWidth(25);
            $activeSheet->getColumnDimension('D')->setWidth(25);

            $activeSheet->setCellValue('A1', 'Période');
            $activeSheet->setCellValue('B1', $firstDayOfLastMonth->format('d/m/Y'));
            $activeSheet->setCellValue('C1', 'au');
            $activeSheet->setCellValue('D1', $firstDayOfLastMonth->format('d/m/Y'));

            $this->addTitleRow($activeSheet, 2, 'Prélèvements obligatoires');
            $this->addSubTitleRow($activeSheet, 3, 'Base (intérêts bruts)', 'Montant prélèvements', 'Taux (%)');
            $this->addValueRow($activeSheet, 4, 'Soumis aux prélèvements (bons de caisse)', $statutoryContributionsInterestsBDC, $statutoryContributionsTaxBDC, $taxRate[TaxType::TYPE_STATUTORY_CONTRIBUTIONS]);
            $this->addValueRow($activeSheet, 5, 'Soumis aux prélèvements (prêt IFP)', $statutoryContributionsInterestsIFP, $statutoryContributionsTaxIFP, $taxRate[TaxType::TYPE_STATUTORY_CONTRIBUTIONS]);
            $this->addValueRow($activeSheet, 6, 'Soumis aux prélèvements (minibons)', $statutoryContributionsInterestsMiniBon, $statutoryContributionsTaxMiniBon, $taxRate[TaxType::TYPE_STATUTORY_CONTRIBUTIONS]);
            $this->addValueRow($activeSheet, 7, 'Dispensé', $exemptedInterests, $exemptedIncomeTax, 0);
            $this->addValueRow($activeSheet, 8, 'Total', $totalFrPhysicalPersonInterest, $statutoryContributionsTax, $taxRate[TaxType::TYPE_STATUTORY_CONTRIBUTIONS]);
            $this->addTitleRow($activeSheet, 9, 'Retenue à la source (bons de caisse et minibons)');
            $this->addSubTitleRow($activeSheet, 10, 'Base (intérêts bruts)', 'Montant retenues à la source', 'Taux (%)');
            $this->addValueRow($activeSheet, 11, 'Soumis à la retenue à la source (personne morale)', $deductionAtSourceInterestsLegalEntity, $deductionAtSourceTaxLegalEntity, $taxRate[TaxType::TYPE_INCOME_TAX_DEDUCTED_AT_SOURCE]);
            $this->addValueRow($activeSheet, 12, 'Soumis à la retenue à la source (personne physique)', $deductionAtSourceInterestsPerson, $deductionAtSourceTaxPerson, $taxRate[TaxType::TYPE_INCOME_TAX_DEDUCTED_AT_SOURCE_PERSON]);
            $this->addTitleRow($activeSheet, 13, 'Prélèvements sociaux');
            $this->addValueRow($activeSheet, 14, 'CSG', $totalFrPhysicalPersonInterest, $csgTax, $taxRate[TaxType::TYPE_CSG]);
            $this->addValueRow($activeSheet, 15, 'Prélèvement social', $totalFrPhysicalPersonInterest, $socialDeductionTax, $taxRate[TaxType::TYPE_SOCIAL_DEDUCTIONS]);
            $this->addValueRow($activeSheet, 16, 'Contribution additionnelle', $totalFrPhysicalPersonInterest, $additionalContributionTax, $taxRate[TaxType::TYPE_ADDITIONAL_CONTRIBUTION_TO_SOCIAL_DEDUCTIONS]);
            $this->addValueRow($activeSheet, 17, 'Prélèvements de solidarité', $totalFrPhysicalPersonInterest, $solidarityDeductionTax, $taxRate[TaxType::TYPE_SOLIDARITY_DEDUCTIONS]);
            $this->addValueRow($activeSheet, 18, 'CRDS', $totalFrPhysicalPersonInterest, $crdsTax, $taxRate[TaxType::TYPE_CRDS]);

            /** @var \PHPExcel_Writer_CSV $writer */
            $writer = \PHPExcel_IOFactory::createWriter($document, 'Excel2007');
            $writer->save(str_replace(__FILE__, $filePath, __FILE__));
        } catch (\PHPExcel_Exception $exception) {
            $this->getContainer()->get('monolog.logger.console')->critical('Monthly fiscal state could not be generated: ' . $exception->getMessage(), [
                'class'    => __CLASS__,
                'function' => __FUNCTION__,
                'file'     => $exception->getFile(),
                'line'     => $exception->getLine()
            ]);

            return;
        }

        $recipientSetting = $entityManager->getRepository(Settings::class)->findOneBy(['type' => 'Adresse notification etat fiscal']);
        $url              = $this->getContainer()->getParameter('router.request_context.scheme') . '://' . $this->getContainer()->getParameter('url.host_default');
        $keywords         = ['$surl' => $url, '$url' => $url];

        $message = $this->getContainer()->get('unilend.swiftmailer.message_provider')->newMessage('notification-etat-fiscal', $keywords, false);

        try {
            $message->setTo(explode(';', trim($recipientSetting->getValue())));
            $message->attach(\Swift_Attachment::fromPath($filePath));
            $mailer = $this->getContainer()->get('mailer');
            $mailer->send($message);
        } catch (\Exception $exception) {
            $this->getContainer()->get('monolog.logger.console')->warning('Could not send email "notification-etat-fiscal": ' . $exception->getMessage(), [
                'id_mail_template' => $message->getTemplateId(),
                'email address'    => explode(';', trim($recipientSetting->getValue())),
                'class'            => __CLASS__,
                'function'         => __FUNCTION__
            ]);
        }

        $withdraw = $input->getOption('withdraw');
        if (true === $withdraw && 'last month' === $month) {
            $this->doTaxWalletsWithdrawals($lastDayOfLastMonth);
        }
    }

    /**
     * @param \PHPExcel_Worksheet $worksheet
     * @param int                 $row
     * @param string              $title
     *
     * @throws \PHPExcel_Exception
     */
    private function addTitleRow(\PHPExcel_Worksheet $worksheet, int $row, string $title): void
    {
        $worksheet->mergeCells('A' . $row . ':D' . $row);
        $worksheet->setCellValue('A' . $row, $title);
        $worksheet->getStyle('A' . $row)->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('ECAEAE');
        $worksheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    }

    /**
     * @param \PHPExcel_Worksheet $worksheet
     * @param int                 $row
     * @param string              $firstTitle
     * @param string              $secondTitle
     * @param string              $thirdTitle
     *
     * @throws \PHPExcel_Exception
     */
    private function addSubTitleRow(\PHPExcel_Worksheet $worksheet, int $row, string $firstTitle, string $secondTitle, string $thirdTitle): void
    {
        $worksheet->setCellValue('B' . $row, $firstTitle);
        $worksheet->setCellValue('C' . $row, $secondTitle);
        $worksheet->setCellValue('D' . $row, $thirdTitle);
        $worksheet->getStyle('A' . $row . ':D' . $row)->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('F4F3DA');
        $worksheet->getStyle('A' . $row . ':D' . $row)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    }

    /**
     * @param \PHPExcel_Worksheet $worksheet
     * @param int                 $row
     * @param string              $title
     * @param float               $firstValue
     * @param float               $secondValue
     * @param float               $thirdValue
     *
     * @throws \PHPExcel_Exception
     */
    private function addValueRow(\PHPExcel_Worksheet $worksheet, int $row, string $title, float $firstValue, float $secondValue, float $thirdValue): void
    {
        $worksheet->setCellValue('A' . $row, $title);
        $worksheet->setCellValueExplicit('B' . $row, $firstValue, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $worksheet->setCellValueExplicit('C' . $row, $secondValue, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $worksheet->setCellValueExplicit('D' . $row, $thirdValue, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $worksheet->getStyle('B' . $row . ':D' . $row)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
        $worksheet->getStyle('B' . $row . ':D' . $row)->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
    }

    /**
     * @param \DateTime $lastDayOfLastMonth
     */
    private function doTaxWalletsWithdrawals(\DateTime $lastDayOfLastMonth): void
    {
        $operationsManager = $this->getContainer()->get('unilend.service.operation_manager');
        $entityManager     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $logger            = $this->getContainer()->get('monolog.logger.console');
        $totalTaxAmount    = 0;

        /** @var Wallet[] $taxWallets */
        $taxWallets = $entityManager->getRepository(Wallet::class)->getTaxWallets();
        foreach ($taxWallets as $wallet) {
            /** @var WalletBalanceHistory $lastMonthWalletHistory */
            $lastMonthWalletHistory = $entityManager->getRepository(WalletBalanceHistory::class)->getBalanceOfTheDay($wallet, $lastDayOfLastMonth);
            if (null === $lastMonthWalletHistory) {
                $logger->error('Could not get the wallet balance for ' . $wallet->getIdType()->getLabel(), ['class' => __CLASS__, 'function' => __FUNCTION__]);
                continue;
            }
            $totalTaxAmount = round(bcadd($lastMonthWalletHistory->getAvailableBalance(), $totalTaxAmount, 4), 2);
            $operationsManager->withdrawTaxWallet($wallet, $lastMonthWalletHistory->getAvailableBalance());
        }
    }

    /**
     * @param array  $rawDataByContract
     * @param array  $regularisedRawDataByContract
     * @param string $contractType
     *
     * @return float|int
     */
    private function getTaxFromGroupedRawData(array $rawDataByContract, array $regularisedRawDataByContract, string $contractType)
    {
        $statutoryContributions = 0;
        if (isset($rawDataByContract[$contractType])) {
            $statutoryContributions = round($rawDataByContract[$contractType]['tax'], 2);
        }

        if (isset($regularisedRawDataByContract[$contractType])) {
            $statutoryContributions = round(bcsub($statutoryContributions, $regularisedRawDataByContract[$contractType]['tax'], 4), 2);
        }

        return $statutoryContributions;
    }

    /**
     * @param array $rawDataByContract
     * @param array $regularisedRawDataByContract
     *
     * @return float
     */
    private function getTaxFromRawData(array $rawDataByContract, array $regularisedRawDataByContract): float
    {
        $tax = 0;
        if (isset($rawDataByContract[0]['tax'])) {
            $tax = round($rawDataByContract[0]['tax'], 2);
        }
        if (isset($regularisedRawDataByContract[0]['tax'])) {
            $tax = round(bcsub($tax, $regularisedRawDataByContract[0]['tax'], 4), 2);
        }

        return $tax;
    }

    /**
     * @param string $date
     *
     * @return bool|string
     */
    private function validateDate($date)
    {
        if ('last month' === $date) {
            return $date;
        }

        if (1 === preg_match('#(\d{4}-\d{2}-\d{2})|(\d{4}-\d{2})#', $date, $match)) {
            $date = $match[0];
            if (false === empty($match[2])) {
                $date .= '-01';
            }
            list($year, $month, $day) = explode('-', $date);
            if (checkdate($month, $day, $year)) {
                return $date;
            }
        }

        return false;
    }
}
