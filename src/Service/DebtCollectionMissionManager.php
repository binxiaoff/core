<?php

namespace Unilend\Service;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Unilend\Entity\{Clients, CloseOutNettingPayment, DebtCollectionFeeDetail, DebtCollectionMission, DebtCollectionMissionPaymentSchedule, Echeanciers, EcheanciersEmprunteur, Loans, Operation,
    ProjectCharge, ProjectRepaymentDetail, ProjectRepaymentTask, Projects, ProjectsStatus, ProjectsStatusHistory, Receptions, TaxType, Users};
use Unilend\Service\Repayment\ProjectRepaymentTaskManager;

class DebtCollectionMissionManager
{
    const DEBT_COLLECTION_CONDITION_CHANGE_DATE = '2016-04-19';
    const FEES_DETAILS_AVAILABILITY_DATE        = '2017-11-01';

    const CLIENT_HASH_MCS      = '2f9f590e-d689-11e6-b3d7-005056a378e2';
    const CLIENT_HASH_PROGERIS = 'f12f0f5b-1867-11e7-a89f-0050569e51ae';

    const DEBT_COLLECTION_MISSION_FOLDER = 'debt_collection_missions';
    const FILE_EXTENSION                 = '.xlsx';

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var ProjectRepaymentTaskManager */
    private $projectRepaymentTaskManager;

    /** @var LoggerInterface */
    private $logger;

    /** @var string */
    private $protectedPath;

    /** @var Filesystem */
    private $fileSystem;

    /** @var ProjectCloseOutNettingManager */
    private $projectCloseOutNettingManager;

    /**
     * @param EntityManagerInterface        $entityManager
     * @param ProjectRepaymentTaskManager   $projectRepaymentTaskManager
     * @param LoggerInterface               $logger
     * @param Filesystem                    $filesystem
     * @param string                        $protectedPath
     * @param ProjectCloseOutNettingManager $projectCloseOutNettingManager
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ProjectRepaymentTaskManager $projectRepaymentTaskManager,
        LoggerInterface $logger,
        Filesystem $filesystem,
        string $protectedPath,
        ProjectCloseOutNettingManager $projectCloseOutNettingManager
    )
    {
        $this->entityManager                 = $entityManager;
        $this->projectRepaymentTaskManager   = $projectRepaymentTaskManager;
        $this->logger                        = $logger;
        $this->fileSystem                    = $filesystem;
        $this->protectedPath                 = $protectedPath;
        $this->projectCloseOutNettingManager = $projectCloseOutNettingManager;
    }

    /**
     * @param DebtCollectionMission $debtCollectionMission
     */
    public function generateExcelFile(DebtCollectionMission $debtCollectionMission)
    {
        /** @var \Unilend\Entity\DebtCollectionMissionPaymentSchedule[] $missionPaymentSchedules */
        $missionPaymentSchedules          = $debtCollectionMission->getDebtCollectionMissionPaymentSchedules();
        $isDebtCollectionFeeDueToBorrower = $this->isDebtCollectionFeeDueToBorrower($debtCollectionMission->getIdProject());
        $isCloseOutNetting                = null !== $debtCollectionMission->getIdProject()->getCloseOutNettingDate();

        $excel       = new \PHPExcel();
        $activeSheet = $excel->setActiveSheetIndex(0);

        $titles            = [
            'Identifiant du prêt',
            'Nom',
            'Prénom',
            'Email',
            'Type',
            'Raison social',
            'Date de naissance',
            'Téléphone',
            'Mobile',
            'Adresse',
            'Code postal',
            'Ville',
            'Montant du prêt'
        ];
        $titleColumn       = 'A';
        $titleRow          = 2;
        $commissionColumns = [];
        $commissionColumn  = null; // in case of close out netting
        $feeColumn         = [];
        $chargeColumn      = null;
        $totalColumn       = null;
        foreach ($titles as $title) {
            $activeSheet->setCellValue($titleColumn . $titleRow, $title);
            $titleColumn++;
        }

        $paymentScheduleTitleCellLeft = $titleColumn;

        if (false === $isCloseOutNetting) {
            $titleColumn++;
            $titleColumn++;
            $paymentScheduleTitleCellRight = $titleColumn;

            foreach ($missionPaymentSchedules as $missionPaymentSchedule) {
                $activeSheet->setCellValue($paymentScheduleTitleCellLeft . '1', 'Échéance ' . $missionPaymentSchedule->getIdPaymentSchedule()->getOrdre());
                $activeSheet->getStyle($paymentScheduleTitleCellLeft . '1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $activeSheet->mergeCells($paymentScheduleTitleCellLeft . '1:' . $paymentScheduleTitleCellRight . '1');
                $activeSheet->setCellValue($paymentScheduleTitleCellLeft . $titleRow, 'Capital');
                $paymentScheduleTitleCellLeft++;
                $paymentScheduleTitleCellRight++;

                $activeSheet->setCellValue($paymentScheduleTitleCellLeft . $titleRow, 'Intérêts');
                $paymentScheduleTitleCellLeft++;
                $paymentScheduleTitleCellRight++;

                $activeSheet->setCellValue($paymentScheduleTitleCellLeft . $titleRow, 'Commission');
                $paymentScheduleTitleCellLeft++;
                $paymentScheduleTitleCellRight++;
            }
        } else {
            $activeSheet->setCellValue($paymentScheduleTitleCellLeft . $titleRow, 'Capital');
            $paymentScheduleTitleCellLeft++;

            $activeSheet->setCellValue($paymentScheduleTitleCellLeft . $titleRow, 'Intérêts');
            $paymentScheduleTitleCellLeft++;

            $activeSheet->setCellValue($paymentScheduleTitleCellLeft . $titleRow, 'Commission');
            $paymentScheduleTitleCellLeft++;
        }

        $activeSheet->setCellValue($paymentScheduleTitleCellLeft . $titleRow, 'Frais');
        if ($isDebtCollectionFeeDueToBorrower) {
            $paymentScheduleTitleCellLeft++;
            $activeSheet->setCellValue($paymentScheduleTitleCellLeft . $titleRow, 'Honoraires');

            $paymentScheduleTitleCellLeft++;
            $activeSheet->setCellValue($paymentScheduleTitleCellLeft . $titleRow, 'Tva');
        }

        $paymentScheduleTitleCellLeft++;
        $activeSheet->setCellValue($paymentScheduleTitleCellLeft . $titleRow, 'Total');

        $creditorDetails = $this->getCreditorsDetails($debtCollectionMission);

        $dataRow = $titleRow;
        foreach ($creditorDetails['loans'] as $loanId => $loanDetails) {
            $dataRow++;
            $dataColumn = 0;
            $activeSheet->setCellValueExplicitByColumnAndRow($dataColumn, $dataRow, $loanId);

            $dataColumn++;
            $activeSheet->setCellValueExplicitByColumnAndRow($dataColumn, $dataRow, $loanDetails['name']);

            $dataColumn++;
            $activeSheet->setCellValueExplicitByColumnAndRow($dataColumn, $dataRow, $loanDetails['first_name']);

            $dataColumn++;
            $activeSheet->setCellValueExplicitByColumnAndRow($dataColumn, $dataRow, $loanDetails['email']);

            $dataColumn++;
            $activeSheet->setCellValueExplicitByColumnAndRow($dataColumn, $dataRow,
                in_array($loanDetails['type'], [Clients::TYPE_PERSON, Clients::TYPE_PERSON_FOREIGNER]) ? 'Physique' : 'Morale');

            $dataColumn++;
            $activeSheet->setCellValueExplicitByColumnAndRow($dataColumn, $dataRow, $loanDetails['company_name']);

            $dataColumn++;
            $activeSheet->setCellValueExplicitByColumnAndRow($dataColumn, $dataRow, $loanDetails['birthday']->format('d/m/Y'));

            $dataColumn++;
            $activeSheet->setCellValueExplicitByColumnAndRow($dataColumn, $dataRow, $loanDetails['telephone']);

            $dataColumn++;
            $activeSheet->setCellValueExplicitByColumnAndRow($dataColumn, $dataRow, $loanDetails['mobile']);

            $dataColumn++;
            $activeSheet->setCellValueExplicitByColumnAndRow($dataColumn, $dataRow, $loanDetails['address']);

            $dataColumn++;
            $activeSheet->setCellValueExplicitByColumnAndRow($dataColumn, $dataRow, $loanDetails['postal_code']);

            $dataColumn++;
            $activeSheet->setCellValueExplicitByColumnAndRow($dataColumn, $dataRow, $loanDetails['city']);

            $dataColumn++;
            $activeSheet->setCellValueExplicitByColumnAndRow($dataColumn, $dataRow, $loanDetails['amount'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);

            if (false === $isCloseOutNetting) {
                foreach ($missionPaymentSchedules as $missionPaymentSchedule) {
                    $sequence = $missionPaymentSchedule->getIdPaymentSchedule()->getOrdre();

                    $dataColumn++;
                    $activeSheet->setCellValueExplicitByColumnAndRow($dataColumn, $dataRow, $loanDetails['schedule'][$sequence]['remaining_capital'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);

                    $dataColumn++;
                    $activeSheet->setCellValueExplicitByColumnAndRow($dataColumn, $dataRow, $loanDetails['schedule'][$sequence]['remaining_interest'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);

                    $dataColumn++; // commission
                    if (empty($commissionColumns['schedule'][$sequence])) {
                        $commissionColumns['schedule'][$sequence] = $dataColumn;
                    }
                }
            } else {
                $dataColumn++;
                $activeSheet->setCellValueExplicitByColumnAndRow($dataColumn, $dataRow, $loanDetails['remaining_capital'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);

                $dataColumn++;
                $activeSheet->setCellValueExplicitByColumnAndRow($dataColumn, $dataRow, $loanDetails['remaining_interest'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);

                $dataColumn++; // commission
                if (empty($commissionColumn)) {
                    $commissionColumn = $dataColumn;
                }
            }

            $dataColumn++;
            if (empty($chargeColumn)) {
                $chargeColumn = $dataColumn;
            }

            if ($isDebtCollectionFeeDueToBorrower) {
                $dataColumn++;
                if (empty($feeColumn['fee_tax_excl'])) {
                    $feeColumn['fee_tax_excl'] = $dataColumn;
                }
                $activeSheet->setCellValueExplicitByColumnAndRow($dataColumn, $dataRow, $loanDetails['fee_tax_excl'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);

                $dataColumn++;
                if (empty($feeColumn['fee_vat'])) {
                    $feeColumn['fee_vat'] = $dataColumn;
                }
                $activeSheet->setCellValueExplicitByColumnAndRow($dataColumn, $dataRow, $loanDetails['fee_vat'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            }

            $dataColumn++;
            if (empty($totalColumn)) {
                $totalColumn = $dataColumn;
            }
            $activeSheet->setCellValueExplicitByColumnAndRow($dataColumn, $dataRow, $loanDetails['total'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        }

        $commissionDetails = $creditorDetails['commission'];
        $dataRow++;
        $activeSheet->setCellValueByColumnAndRow(0, $dataRow, 'Commission unilend');
        if (false === $isCloseOutNetting) {
            foreach ($commissionColumns['schedule'] as $sequence => $column) {
                $activeSheet->setCellValueExplicitByColumnAndRow($column, $dataRow, $commissionDetails['schedule'][$sequence], \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            }
        } else {
            $activeSheet->setCellValueExplicitByColumnAndRow($commissionColumn, $dataRow, $commissionDetails['remaining_commission'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        }

        if ($isDebtCollectionFeeDueToBorrower) {
            $activeSheet->setCellValueExplicitByColumnAndRow($feeColumn['fee_tax_excl'], $dataRow, $commissionDetails['fee_tax_excl'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicitByColumnAndRow($feeColumn['fee_vat'], $dataRow, $commissionDetails['fee_vat'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        }
        $activeSheet->setCellValueExplicitByColumnAndRow($totalColumn, $dataRow, $commissionDetails['total'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);

        $chargeDetails = $creditorDetails['charge'];
        $dataRow++;
        $activeSheet->setCellValueByColumnAndRow(0, $dataRow, 'Frais');
        $activeSheet->setCellValueExplicitByColumnAndRow($chargeColumn, $dataRow, $chargeDetails['charge'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        if ($isDebtCollectionFeeDueToBorrower) {
            $activeSheet->setCellValueExplicitByColumnAndRow($feeColumn['fee_tax_excl'], $dataRow, $chargeDetails['fee_tax_excl'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicitByColumnAndRow($feeColumn['fee_vat'], $dataRow, $chargeDetails['fee_vat'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        }
        $activeSheet->setCellValueExplicitByColumnAndRow($totalColumn, $dataRow, $chargeDetails['total'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);

        $fileName     = 'recouvrement_' . $debtCollectionMission->getId() . '_' . $debtCollectionMission->getAdded()->format('Y-m-d');
        $absolutePath = implode(DIRECTORY_SEPARATOR, [
            $this->protectedPath,
            self::DEBT_COLLECTION_MISSION_FOLDER,
            trim($debtCollectionMission->getIdClientDebtCollector()->getIdClient()),
            $debtCollectionMission->getIdProject()->getIdProject()
        ]);

        if (false === is_dir($absolutePath)) {
            $this->fileSystem->mkdir($absolutePath);
        }

        if ($this->fileSystem->exists($absolutePath . DIRECTORY_SEPARATOR . $fileName . self::FILE_EXTENSION)) {
            $fileName = 'recouvrement_' . $debtCollectionMission->getId() . '_' . $debtCollectionMission->getAdded()->format('Y-m-d') . '_' . uniqid();
        }
        $absoluteFileName = $absolutePath . DIRECTORY_SEPARATOR . $fileName . self::FILE_EXTENSION;

        /** @var \PHPExcel_Writer_Excel2007 $writer */
        $writer = \PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
        $writer->save($absoluteFileName);

        $debtCollectionMission->setAttachment(str_replace($this->protectedPath, '', $absoluteFileName));
        $this->entityManager->flush($debtCollectionMission);
    }

    private function getCreditorsDetails(DebtCollectionMission $debtCollectionMission)
    {
        return [
            'loans'      => $this->getLoanDetails($debtCollectionMission),
            'commission' => $this->getCommissionDetails($debtCollectionMission),
            'charge'     => $this->getChargeDetails($debtCollectionMission)
        ];
    }

    private function getChargeDetails(DebtCollectionMission $debtCollectionMission)
    {
        $charges = $this->entityManager->getRepository(ProjectCharge::class)->findBy([
            'idProject' => $debtCollectionMission->getIdProject(),
            'status'    => ProjectCharge::STATUS_PAID_BY_UNILEND
        ]);

        $totalCharges = 0;

        foreach ($charges as $charge) {
            $totalCharges = round(bcadd($totalCharges, $charge->getAmountInclVat(), 4), 2);
        }
        $totalFeeTaxIncl = 0;
        $totalFeeVat     = 0;
        if ($this->isDebtCollectionFeeDueToBorrower($debtCollectionMission->getIdProject())) {
            $vatTax = $this->entityManager->getRepository(TaxType::class)->find(TaxType::TYPE_VAT);
            if (null === $vatTax) {
                throw new \Exception('The VAT rate is not defined.');
            }
            $vatTaxRate = round(bcdiv($vatTax->getRate(), 100, 5), 4);

            $totalFeeTaxIncl = round(bcmul($totalCharges, $debtCollectionMission->getFeesRate(), 4), 2);
            $totalFeeVat     = round(bcmul($totalFeeTaxIncl, $vatTaxRate, 4), 2);
        }

        $total = round(bcadd($totalCharges, bcadd($totalFeeTaxIncl, $totalFeeVat, 4), 4), 2);

        return [
            'charge'       => $totalCharges,
            'fee_tax_excl' => $totalFeeTaxIncl,
            'fee_vat'      => $totalFeeVat,
            'total'        => $total
        ];
    }

    /**
     * @param DebtCollectionMission $debtCollectionMission
     *
     * @return array
     * @throws \Exception
     */
    private function getCommissionDetails(DebtCollectionMission $debtCollectionMission)
    {
        $commissionDetails        = [];
        $totalRemainingCommission = 0;
        $isCloseOutNetting        = null !== $debtCollectionMission->getIdProject()->getCloseOutNettingDate();

        if (false === $isCloseOutNetting) {
            $missionPaymentSchedules = $debtCollectionMission->getDebtCollectionMissionPaymentSchedules();

            foreach ($missionPaymentSchedules as $missionPaymentSchedule) {
                $paymentSchedule               = $missionPaymentSchedule->getIdPaymentSchedule();
                $remainingCommissionBySchedule = round(bcdiv($paymentSchedule->getCommission() + $paymentSchedule->getTva() - $paymentSchedule->getPaidCommissionVatIncl(), 100, 4), 2);

                $commissionDetails['schedule'][$missionPaymentSchedule->getIdPaymentSchedule()->getOrdre()] = $remainingCommissionBySchedule;

                $totalRemainingCommission = round(bcadd($totalRemainingCommission, $remainingCommissionBySchedule, 4), 2);
            }
        } else {
            $closeOutNettingPayment   = $this->entityManager->getRepository(CloseOutNettingPayment::class)->findOneBy(['idProject' => $debtCollectionMission->getIdProject()]);
            $totalRemainingCommission = round(bcsub($closeOutNettingPayment->getCommissionTaxIncl(), $closeOutNettingPayment->getPaidCommissionTaxIncl(), 4), 2);

            $commissionDetails['remaining_commission'] = $totalRemainingCommission;
        }

        if ($this->isDebtCollectionFeeDueToBorrower($debtCollectionMission->getIdProject())) {
            $vatTax = $this->entityManager->getRepository(TaxType::class)->find(TaxType::TYPE_VAT);
            if (null === $vatTax) {
                throw new \Exception('The VAT rate is not defined.');
            }

            $vatTaxRate = round(bcdiv($vatTax->getRate(), 100, 5), 4);

            $totalFeeTaxIncl = round(bcmul($totalRemainingCommission, $debtCollectionMission->getFeesRate(), 4), 2);
            $totalFeeVat     = round(bcmul($totalFeeTaxIncl, $vatTaxRate, 4), 2);

            $commissionDetails['fee_tax_excl'] = $totalFeeTaxIncl;
            $commissionDetails['fee_vat']      = $totalFeeVat;
            $commissionDetails['total']        = round(bcadd($totalRemainingCommission, bcadd($totalFeeTaxIncl, $totalFeeVat, 4), 4), 2);
        } else {
            $commissionDetails['fee_tax_excl'] = 0;
            $commissionDetails['fee_vat']      = 0;
            $commissionDetails['total']        = $totalRemainingCommission;
        }

        return $commissionDetails;
    }

    /**
     * @param DebtCollectionMission $debtCollectionMission
     *
     * @return array
     * @throws \Exception
     */
    private function getLoanDetails(DebtCollectionMission $debtCollectionMission)
    {
        $missionPaymentSchedules = $debtCollectionMission->getDebtCollectionMissionPaymentSchedules();

        $isCloseOutNetting = null !== $debtCollectionMission->getIdProject()->getCloseOutNettingDate();

        $this->projectRepaymentTaskManager->prepareNonFinishedTask($debtCollectionMission->getIdProject());

        $repaymentScheduleRepository        = $this->entityManager->getRepository(Echeanciers::class);
        $projectRepaymentDetailRepository   = $this->entityManager->getRepository(ProjectRepaymentDetail::class);
        $loanRepository                     = $this->entityManager->getRepository(Loans::class);

        $project                 = $debtCollectionMission->getIdProject();
        $vatTax                  = $this->entityManager->getRepository(TaxType::class)->find(TaxType::TYPE_VAT);
        $remainingAmountsByLoans = $repaymentScheduleRepository->getRemainingAmountsByLoanAndSequence($project); // for resolve the memory issue. 200 MB reduced.
        $loanDetails             = $loanRepository->getBasicInformation($project); // for resolve the memory issue. 30 MB reduced.

        if (null === $vatTax) {
            throw new \Exception('The VAT rate is not defined.');
        }

        $vatTaxRate = round(bcdiv($vatTax->getRate(), 100, 6), 4);

        foreach ($loanDetails as $loanId => $loanDetail) {
            $totalRemainingAmount = 0;

            if (false === $isCloseOutNetting) {
                foreach ($missionPaymentSchedules as $missionPaymentSchedule) {
                    $sequence = $missionPaymentSchedule->getIdPaymentSchedule()->getOrdre();

                    $remainingCapital  = $remainingAmountsByLoans[$loanId][$sequence]['capital'];
                    $remainingInterest = $remainingAmountsByLoans[$loanId][$sequence]['interest'];

                    $pendingCapital  = 0;
                    $pendingInterest = 0;

                    $pendingAmount = $projectRepaymentDetailRepository->getPendingAmountToRepay($loanId, $sequence);
                    if ($pendingAmount) {
                        $pendingCapital  = $pendingAmount['capital'];
                        $pendingInterest = $pendingAmount['interest'];
                    }

                    $remainingCapital  = round(bcsub($remainingCapital, $pendingCapital, 4), 2);
                    $remainingInterest = round(bcsub($remainingInterest, $pendingInterest, 4), 2);

                    $loanDetails[$loanId]['schedule'][$sequence]['remaining_capital']  = $remainingCapital;
                    $loanDetails[$loanId]['schedule'][$sequence]['remaining_interest'] = $remainingInterest;

                    $totalRemainingAmount = round(bcadd($totalRemainingAmount, bcadd($remainingCapital, $remainingInterest, 4), 4), 2);
                }
            } else {
                $remainingAmountsByLoan = $this->projectCloseOutNettingManager->getRemainingAmountByLoan($loanId);

                $loanDetails[$loanId]['remaining_capital']  = $remainingAmountsByLoan['capital'];
                $loanDetails[$loanId]['remaining_interest'] = $remainingAmountsByLoan['interest'];

                $totalRemainingAmount = round(bcadd($remainingAmountsByLoan['capital'], $remainingAmountsByLoan['interest'], 4), 2);
            }

            if ($this->isDebtCollectionFeeDueToBorrower($debtCollectionMission->getIdProject())) {
                $feeVatExcl                           = round(bcmul($totalRemainingAmount, $debtCollectionMission->getFeesRate(), 4), 2);
                $feeVat                               = round(bcmul($feeVatExcl, $vatTaxRate, 4), 2);
                $feeOnRemainingAmountTaxIncl          = round(bcadd($feeVatExcl, $feeVat, 4), 2);
                $loanDetails[$loanId]['fee_tax_excl'] = $feeVatExcl;
                $loanDetails[$loanId]['fee_vat']      = $feeVat;
                $loanDetails[$loanId]['total']        = round(bcadd($totalRemainingAmount, $feeOnRemainingAmountTaxIncl, 4), 2);
            } else {
                $loanDetails[$loanId]['fee_tax_excl'] = 0;
                $loanDetails[$loanId]['fee_vat']      = 0;
                $loanDetails[$loanId]['total']        = $totalRemainingAmount;
            }
        }

        return $loanDetails;
    }

    /**
     * @param Projects $project
     *
     * @return bool
     */
    public function isDebtCollectionFeeDueToBorrower(Projects $project)
    {
        $statusHistory = $this->entityManager
            ->getRepository(ProjectsStatusHistory::class)
            ->findStatusFirstOccurrence($project, ProjectsStatus::STATUS_PUBLISHED);
        $putOnlineDate = $statusHistory->getAdded();
        $putOnlineDate->setTime(0, 0, 0);
        $dateOfChange = new \DateTime(self::DEBT_COLLECTION_CONDITION_CHANGE_DATE);
        $dateOfChange->setTime(0, 0, 0);

        if ($putOnlineDate >= $dateOfChange) {
            return true;
        }

        return false;
    }

    /**
     * @param Receptions $wireTransferIn
     *
     * @return array
     */
    private function getFeeDetails(Receptions $wireTransferIn)
    {
        return [
            'loans'      => $this->getLoanFeeDetails($wireTransferIn),
            'commission' => $this->getCommissionFeeDetails($wireTransferIn),
            'charge'     => $this->getChargeFeeDetails($wireTransferIn)
        ];
    }

    /**
     * @param Receptions $wireTransferIn
     *
     * @return array
     */
    private function getLoanFeeDetails(Receptions $wireTransferIn)
    {
        $operationRepository               = $this->entityManager->getRepository(Operation::class);
        $debtCollectionFeeDetailRepository = $this->entityManager->getRepository(DebtCollectionFeeDetail::class);
        $loanDetails                       = $this->entityManager->getRepository(Loans::class)->getBasicInformation($wireTransferIn->getIdProject());

        foreach ($loanDetails as $loanId => $loanDetail) {
            $repaidAmounts                           = $operationRepository->getTotalRepaidAmountsByLoanAndWireTransferIn($loanId, $wireTransferIn);
            $loanDetails[$loanId]['repaid_capital']  = $repaidAmounts['capital'];
            $loanDetails[$loanId]['repaid_interest'] = $repaidAmounts['interest'];
            $feeAmounts                              = $debtCollectionFeeDetailRepository->getAmountsByLoanAndWireTransferIn($loanId, $wireTransferIn);
            $loanDetails[$loanId]['fee_tax_excl']    = round(bcsub($feeAmounts['amountTaxIncl'], $feeAmounts['vat'], 4), 2);
            $loanDetails[$loanId]['fee_vat']         = $feeAmounts['vat'];
        }

        return $loanDetails;
    }

    /**
     * @param Receptions $wireTransferIn
     *
     * @return array
     */
    private function getCommissionFeeDetails(Receptions $wireTransferIn)
    {
        $debtCollectionFeeDetailRepository = $this->entityManager->getRepository(DebtCollectionFeeDetail::class);
        $projectRepaymentTaskRepository    = $this->entityManager->getRepository(ProjectRepaymentTask::class);

        $feeAmounts                        = $debtCollectionFeeDetailRepository->getAmountsByTypeAndWireTransferIn(DebtCollectionFeeDetail::TYPE_REPAYMENT_COMMISSION, $wireTransferIn);
        $commissionDetails['commission']   = $projectRepaymentTaskRepository->getTotalCommissionByWireTransferIn($wireTransferIn);
        $commissionDetails['fee_tax_excl'] = round(bcsub($feeAmounts['amountTaxIncl'], $feeAmounts['vat'], 4), 2);
        $commissionDetails['fee_vat']      = $feeAmounts['vat'];

        return $commissionDetails;
    }

    /**
     * @param Receptions $wireTransferIn
     *
     * @return array
     */
    private function getChargeFeeDetails(Receptions $wireTransferIn)
    {
        $debtCollectionFeeDetailRepository = $this->entityManager->getRepository(DebtCollectionFeeDetail::class);
        $projectChargeRepository           = $this->entityManager->getRepository(ProjectCharge::class);

        $feeAmounts                    = $debtCollectionFeeDetailRepository->getAmountsByTypeAndWireTransferIn(DebtCollectionFeeDetail::TYPE_PROJECT_CHARGE, $wireTransferIn);
        $chargeDetails['charge']       = $projectChargeRepository->getTotalChargeByWireTransferIn($wireTransferIn);
        $chargeDetails['fee_tax_excl'] = round(bcsub($feeAmounts['amountTaxIncl'], $feeAmounts['vat'], 4), 2);
        $chargeDetails['fee_vat']      = $feeAmounts['vat'];

        return $chargeDetails;
    }

    /**
     * @param Receptions $wireTransferIn
     *
     * @return \PHPExcel
     */
    public function generateFeeDetailsFile(Receptions $wireTransferIn)
    {
        $excel       = new \PHPExcel();
        $activeSheet = $excel->setActiveSheetIndex(0);

        $titles           = [
            'Identifiant du prêt',
            'Nom',
            'Prénom',
            'Email',
            'Type',
            'Raison social',
            'Date de naissance',
            'Téléphone',
            'Mobile',
            'Adresse',
            'Code postal',
            'Ville',
            'Montant du prêt',
            'Capital remboursé',
            'Intétêt remboursé',
            'Commission',
            'Frais',
            'Honoraires',
            'TVA'
        ];
        $titleColumn      = 0;
        $titleRow         = 1;
        $commissionColumn = 14;
        $chargeColumn     = 15;
        $feeColumn        = 16;
        $vatColumn        = 17;

        foreach ($titles as $title) {
            $activeSheet->setCellValueByColumnAndRow($titleColumn, $titleRow, $title);
            $titleColumn++;
        }

        $feeDetails = $this->getFeeDetails($wireTransferIn);

        $dataRow = 2;
        foreach ($feeDetails['loans'] as $loanId => $loanDetails) {
            $dataColumn = 0;
            $activeSheet->setCellValueExplicitByColumnAndRow($dataColumn, $dataRow, $loanId);

            $dataColumn++;
            $activeSheet->setCellValueExplicitByColumnAndRow($dataColumn, $dataRow, $loanDetails['name']);

            $dataColumn++;
            $activeSheet->setCellValueExplicitByColumnAndRow($dataColumn, $dataRow, $loanDetails['first_name']);

            $dataColumn++;
            $activeSheet->setCellValueExplicitByColumnAndRow($dataColumn, $dataRow, $loanDetails['email']);

            $dataColumn++;
            $activeSheet->setCellValueExplicitByColumnAndRow($dataColumn, $dataRow, in_array($loanDetails['type'], [Clients::TYPE_PERSON, Clients::TYPE_PERSON_FOREIGNER]) ? 'Physique' : 'Morale');

            $dataColumn++;
            $activeSheet->setCellValueExplicitByColumnAndRow($dataColumn, $dataRow, $loanDetails['company_name']);

            $dataColumn++;
            $activeSheet->setCellValueExplicitByColumnAndRow($dataColumn, $dataRow, $loanDetails['birthday']->format('d/m/Y'));

            $dataColumn++;
            $activeSheet->setCellValueExplicitByColumnAndRow($dataColumn, $dataRow, $loanDetails['telephone']);

            $dataColumn++;
            $activeSheet->setCellValueExplicitByColumnAndRow($dataColumn, $dataRow, $loanDetails['mobile']);

            $dataColumn++;
            $activeSheet->setCellValueExplicitByColumnAndRow($dataColumn, $dataRow, $loanDetails['address']);

            $dataColumn++;
            $activeSheet->setCellValueExplicitByColumnAndRow($dataColumn, $dataRow, $loanDetails['postal_code']);

            $dataColumn++;
            $activeSheet->setCellValueExplicitByColumnAndRow($dataColumn, $dataRow, $loanDetails['city']);

            $dataColumn++;
            $activeSheet->setCellValueExplicitByColumnAndRow($dataColumn, $dataRow, $loanDetails['amount'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);

            $dataColumn++;
            $activeSheet->setCellValueExplicitByColumnAndRow($dataColumn, $dataRow, $loanDetails['repaid_capital'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);

            $dataColumn++;
            $activeSheet->setCellValueExplicitByColumnAndRow($dataColumn, $dataRow, $loanDetails['repaid_interest'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);

            $activeSheet->setCellValueExplicitByColumnAndRow($feeColumn, $dataRow, $loanDetails['fee_tax_excl'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);

            $activeSheet->setCellValueExplicitByColumnAndRow($vatColumn, $dataRow, $loanDetails['fee_vat'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);

            $dataRow++;
        }

        $commissionDetails = $feeDetails['commission'];
        $activeSheet->setCellValueByColumnAndRow(0, $dataRow, 'Commission unilend');
        $activeSheet->setCellValueExplicitByColumnAndRow($commissionColumn, $dataRow, $commissionDetails['commission'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicitByColumnAndRow($feeColumn, $dataRow, $commissionDetails['fee_tax_excl'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicitByColumnAndRow($vatColumn, $dataRow, $commissionDetails['fee_vat'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);

        $chargeDetails = $feeDetails['charge'];
        $dataRow++;
        $activeSheet->setCellValueByColumnAndRow(0, $dataRow, 'Frais');
        $activeSheet->setCellValueExplicitByColumnAndRow($chargeColumn, $dataRow, $chargeDetails['charge'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicitByColumnAndRow($feeColumn, $dataRow, $chargeDetails['fee_tax_excl'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicitByColumnAndRow($vatColumn, $dataRow, $chargeDetails['fee_vat'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);

        return $excel;
    }

    /**
     * Archives current project's debt collection missions if any and creates a new one with whole late payments schedule
     *
     * @param Projects $project
     * @param Clients  $debtCollector
     * @param int      $type
     * @param float    $feesRate
     * @param Users    $user
     *
     * @return bool|DebtCollectionMission The created debt collection mission in case of success, FALSE otherwise
     */
    public function newMission(Projects $project, Clients $debtCollector, $type, $feesRate, Users $user)
    {
        $debtCollectionMissionRepository = $this->entityManager->getRepository(DebtCollectionMission::class);
        /** @var DebtCollectionMission $currentMission */
        $currentMission  = $debtCollectionMissionRepository->findOneBy(['idProject' => $project, 'idClientDebtCollector' => $debtCollector, 'archived' => null]);
        $totalCapital    = 0;
        $totalInterest   = 0;
        $totalCommission = 0;

        try {
            $this->entityManager->getConnection()->beginTransaction();

            if ($currentMission) {
                $this->endMission($currentMission, $user);
            }

            $newMission = new DebtCollectionMission();
            $newMission->setIdProject($project)
                ->setIdClientDebtCollector($debtCollector)
                ->setType($type)
                ->setFeesRate($feesRate)
                ->setIdUserCreation($user)
                ->setCapital(0)
                ->setInterest(0)
                ->setCommissionVatIncl(0);
            $this->entityManager->persist($newMission);
            $this->entityManager->flush($newMission);

            $closeOutNettingDate = $project->getCloseOutNettingDate();

            if (null === $closeOutNettingDate) {
                /** @var EcheanciersEmprunteur[] $pendingPayments */
                $pendingPayments                  = $this->entityManager->getRepository(EcheanciersEmprunteur::class)->findBy(
                    [
                        'idProject'        => $project,
                        'statusEmprunteur' => [EcheanciersEmprunteur::STATUS_PENDING, EcheanciersEmprunteur::STATUS_PARTIALLY_PAID]
                    ],
                    ['dateEcheanceEmprunteur' => 'ASC']
                );
                $paymentScheduleMissionCollection = new ArrayCollection();
                $today                            = (new \DateTime())->setTime(0, 0, 0);

                foreach ($pendingPayments as $key => $payment) {
                    if ($payment->getDateEcheanceEmprunteur() < $today) {
                        $paymentScheduleMission = new DebtCollectionMissionPaymentSchedule();
                        $paymentScheduleMission->setIdMission($newMission)
                            ->setIdPaymentSchedule($payment)
                            ->setCapital(round(bcdiv($payment->getCapital() - $payment->getPaidCapital(), 100, 4), 2))
                            ->setInterest(round(bcdiv($payment->getInterets() - $payment->getPaidInterest(), 100, 4), 2))
                            ->setCommissionVatIncl(round(bcdiv($payment->getCommission() + $payment->getTva() - $payment->getPaidCommissionVatIncl(), 100, 4), 2));

                        $this->entityManager->persist($paymentScheduleMission);
                        $this->entityManager->flush($paymentScheduleMission);

                        $totalCapital    = round(bcadd($totalCapital, $paymentScheduleMission->getCapital(), 4), 2);
                        $totalInterest   = round(bcadd($totalInterest, $paymentScheduleMission->getInterest(), 4), 2);
                        $totalCommission = round(bcadd($totalCommission, $paymentScheduleMission->getCommissionVatIncl(), 4), 2);

                        $paymentScheduleMissionCollection->add($paymentScheduleMission);
                    }
                }
                $newMission->setDebtCollectionMissionPaymentSchedules($paymentScheduleMissionCollection);
            } else {
                $closeOutNettingPayment = $this->entityManager->getRepository(CloseOutNettingPayment::class)->findOneBy(['idProject' => $project]);
                $totalCapital           = round(bcsub($closeOutNettingPayment->getCapital(), $closeOutNettingPayment->getPaidCapital(), 4), 2);
                $totalInterest          = round(bcsub($closeOutNettingPayment->getInterest(), $closeOutNettingPayment->getPaidInterest(), 4), 2);
                $totalCommission        = round(bcsub($closeOutNettingPayment->getCommissionTaxIncl(), $closeOutNettingPayment->getPaidCommissionTaxIncl(), 4), 2);

            }
            $newMission->setCapital($totalCapital)
                ->setInterest($totalInterest)
                ->setCommissionVatIncl($totalCommission);
            $this->entityManager->flush($newMission);

            $this->entityManager->getConnection()->commit();
            $this->entityManager->refresh($newMission);

            return $newMission;
        } catch (\Exception $exception) {
            $this->entityManager->getConnection()->rollBack();
            $this->logger->error('Error when creating new debt collection mission on project: ' . $project->getTitle() . ' Error: ' . $exception->getMessage() . ' In file: ' . $exception->getFile() . ' At line: ' . $exception->getLine(),
                ['method' => __METHOD__, 'id_project' => $project->getIdProject(), 'debt_collector' => $debtCollector->getIdClient()]);

            return false;
        }
    }

    /**
     * @param DebtCollectionMission $debtCollectionMission
     * @param Users                 $user
     *
     * @return bool
     */
    public function endMission(DebtCollectionMission $debtCollectionMission, Users $user): bool
    {
        $debtCollectionMission->setArchived(new \DateTime())->setIdUserArchiving($user);

        try {
            $this->entityManager->flush($debtCollectionMission);
        } catch (\Exception $exception) {
            $this->logger->error('Error occured when archiving the debt collection mission (ID: ' . $debtCollectionMission->getId() . '). Error: ' . $exception->getMessage(), [
                'class'    => __CLASS__,
                'function' => __FUNCTION__,
                'file'     => $exception->getFile(),
                'line'     => $exception->getLine(),
            ]);

            return false;
        }

        return true;
    }
}
