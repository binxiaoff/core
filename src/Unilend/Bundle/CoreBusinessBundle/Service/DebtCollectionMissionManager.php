<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\DebtCollectionFeeDetail;
use Unilend\Bundle\CoreBusinessBundle\Entity\DebtCollectionMission;
use Unilend\Bundle\CoreBusinessBundle\Entity\Loans;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectCharge;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRepaymentTask;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\Receptions;
use Unilend\Bundle\CoreBusinessBundle\Entity\TaxType;
use Unilend\Bundle\CoreBusinessBundle\Service\Repayment\ProjectRepaymentTaskManager;

class DebtCollectionMissionManager
{
    const DEBT_COLLECTION_CONDITION_CHANGE_DATE = '2016-04-19';

    /** @var EntityManager */
    private $entityManager;

    /** @var ProjectRepaymentTaskManager */
    private $projectRepaymentTaskManager;

    public function __construct(EntityManager $entityManager, ProjectRepaymentTaskManager $projectRepaymentTaskManager)
    {
        $this->entityManager               = $entityManager;
        $this->projectRepaymentTaskManager = $projectRepaymentTaskManager;
    }

    public function getCreditorsDetails(DebtCollectionMission $debtCollectionMission)
    {
        return [
            'loans'      => $this->getLoanDetails($debtCollectionMission),
            'commission' => $this->getCommissionDetails($debtCollectionMission),
            'charge'     => $this->getChargeDetails($debtCollectionMission)
        ];
    }

    private function getChargeDetails(DebtCollectionMission $debtCollectionMission)
    {
        $charges = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectCharge')->findBy([
            'idProject' => $debtCollectionMission->getIdProject(),
            'status'    => ProjectCharge::STATUS_PENDING
        ]);

        $totalCharges = 0;

        foreach ($charges as $charge) {
            $totalCharges = round(bcadd($totalCharges, $charge->getAmountInclVat(), 4), 2);
        }

        $vatTax = $this->entityManager->getRepository('UnilendCoreBusinessBundle:TaxType')->find(TaxType::TYPE_VAT);
        if (null === $vatTax) {
            throw new \Exception('The VAT rate is not defined.');
        }

        $vatTaxRate = round(bcdiv($vatTax->getRate(), 100, 5), 4);

        $totalFeeTaxIncl = round(bcmul($totalCharges, $debtCollectionMission->getFeesRate(), 4), 2);
        $totalFeeVat     = round(bcmul($totalFeeTaxIncl, $vatTaxRate, 4), 2);

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

        $missionPaymentSchedules = $debtCollectionMission->getDebtCollectionMissionPaymentSchedules();

        foreach ($missionPaymentSchedules as $missionPaymentSchedule) {
            $paymentSchedule               = $missionPaymentSchedule->getIdPaymentSchedule();
            $remainingCommissionBySchedule = round(bcdiv($paymentSchedule->getCommission() + $paymentSchedule->getTva() - $paymentSchedule->getPaidCommissionVatIncl(), 100, 4), 2);

            $commissionDetails['schedule'][$missionPaymentSchedule->getIdPaymentSchedule()->getOrdre()] = $remainingCommissionBySchedule;

            $totalRemainingCommission = round(bcadd($totalRemainingCommission, $remainingCommissionBySchedule, 4), 2);
        }

        $vatTax = $this->entityManager->getRepository('UnilendCoreBusinessBundle:TaxType')->find(TaxType::TYPE_VAT);
        if (null === $vatTax) {
            throw new \Exception('The VAT rate is not defined.');
        }

        $vatTaxRate = round(bcdiv($vatTax->getRate(), 100, 5), 4);

        $totalFeeTaxIncl = round(bcmul($totalRemainingCommission, $debtCollectionMission->getFeesRate(), 4), 2);
        $totalFeeVat     = round(bcmul($totalFeeTaxIncl, $vatTaxRate, 4), 2);

        $commissionDetails['fee_tax_excl'] = $totalFeeTaxIncl;
        $commissionDetails['fee_vat']      = $totalFeeVat;
        $commissionDetails['total']        = round(bcadd($totalRemainingCommission, bcadd($totalFeeTaxIncl, $totalFeeVat, 4), 4), 2);

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
        $projectRepaymentTaskRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectRepaymentTask');
        $missionPaymentSchedules        = $debtCollectionMission->getDebtCollectionMissionPaymentSchedules();

        foreach ($missionPaymentSchedules as $missionPaymentSchedule) {
            $repaymentTasks = $projectRepaymentTaskRepository->findBy([
                'idProject' => $debtCollectionMission->getIdProject(),
                'sequence'  => $missionPaymentSchedule->getIdPaymentSchedule()->getOrdre(),
                'status'    => [
                    ProjectRepaymentTask::STATUS_ERROR,
                    ProjectRepaymentTask::STATUS_PENDING,
                    ProjectRepaymentTask::STATUS_READY,
                    ProjectRepaymentTask::STATUS_IN_PROGRESS,
                ]
            ]);

            foreach ($repaymentTasks as $projectRepaymentTask) {
                $this->projectRepaymentTaskManager->prepare($projectRepaymentTask);
            }
        }

        $repaymentScheduleRepository      = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Echeanciers');
        $projectRepaymentDetailRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectRepaymentDetail');

        $loanDetails = [];
        $project     = $debtCollectionMission->getIdProject();
        $loans       = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Loans')->findBy(['idProject' => $project, 'status' => Loans::STATUS_ACCEPTED]);
        $vatTax      = $this->entityManager->getRepository('UnilendCoreBusinessBundle:TaxType')->find(TaxType::TYPE_VAT);

        if (null === $vatTax) {
            throw new \Exception('The VAT rate is not defined.');
        }

        $vatTaxRate = round(bcdiv($vatTax->getRate(), 100, 6), 4);

        foreach ($loans as $loan) {
            $loanDetails[$loan->getIdLoan()] = $this->getLoanBasicInformation($loan);

            $totalRemainingAmount = 0;

            foreach ($missionPaymentSchedules as $missionPaymentSchedule) {
                $sequence = $missionPaymentSchedule->getIdPaymentSchedule()->getOrdre();

                $repaymentSchedule = $repaymentScheduleRepository->findOneBy(['idLoan' => $loan, 'ordre' => $sequence]);
                $remainingCapital  = round(bcdiv($repaymentSchedule->getCapital() - $repaymentSchedule->getCapitalRembourse(), 100, 4), 2);
                $remainingInterest = round(bcdiv($repaymentSchedule->getInterets() - $repaymentSchedule->getInteretsRembourses(), 100, 4), 2);

                $pendingCapital  = 0;
                $pendingInterest = 0;

                $pendingAmount = $projectRepaymentDetailRepository->getPendingAmountToRepay($loan, $sequence);
                if ($pendingAmount) {
                    $pendingCapital  = $pendingAmount['capital'];
                    $pendingInterest = $pendingAmount['interest'];
                }

                $remainingCapital  = round(bcsub($remainingCapital, $pendingCapital, 4), 2);
                $remainingInterest = round(bcsub($remainingInterest, $pendingInterest, 4), 2);

                $loanDetails[$loan->getIdLoan()]['schedule'][$sequence]['remaining_capital']  = $remainingCapital;
                $loanDetails[$loan->getIdLoan()]['schedule'][$sequence]['remaining_interest'] = $remainingInterest;

                $totalRemainingAmount = round(bcadd($totalRemainingAmount, bcadd($remainingCapital, $remainingInterest, 4), 4), 2);
            }
            $feeVatExcl                                      = round(bcmul($totalRemainingAmount, $debtCollectionMission->getFeesRate(), 4), 2);
            $feeVat                                          = round(bcmul($feeVatExcl, $vatTaxRate, 4), 2);
            $feeOnRemainingAmountTaxIncl                     = round(bcadd($feeVatExcl, $feeVat, 4), 2);
            $loanDetails[$loan->getIdLoan()]['fee_tax_excl'] = $feeVatExcl;
            $loanDetails[$loan->getIdLoan()]['fee_vat']      = $feeVat;
            $loanDetails[$loan->getIdLoan()]['total']        = round(bcadd($totalRemainingAmount, $feeOnRemainingAmountTaxIncl, 4), 2);
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
            ->getRepository('UnilendCoreBusinessBundle:ProjectsStatusHistory')
            ->findStatusFirstOccurrence($project, ProjectsStatus::EN_FUNDING);
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
        $loanDetails = [];

        $operationRepository               = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Operation');
        $debtCollectionFeeDetailRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:DebtCollectionFeeDetail');
        $project                           = $wireTransferIn->getIdProject();
        $loans                             = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Loans')->findBy(['idProject' => $project, 'status' => Loans::STATUS_ACCEPTED]);

        foreach ($loans as $loan) {
            $loanDetails[$loan->getIdLoan()]                    = $this->getLoanBasicInformation($loan);
            $repaidAmounts                                      = $operationRepository->getTotalRepaidAmountsByLoanAndWireTransferIn($loan, $wireTransferIn);
            $loanDetails[$loan->getIdLoan()]['repaid_capital']  = $repaidAmounts['capital'];
            $loanDetails[$loan->getIdLoan()]['repaid_interest'] = $repaidAmounts['interest'];
            $feeAmounts                                         = $debtCollectionFeeDetailRepository->getAmountsByLoanAndWireTransferIn($loan, $wireTransferIn);
            $loanDetails[$loan->getIdLoan()]['fee_tax_excl']    = round(bcsub($feeAmounts['amountTaxIncl'], $feeAmounts['vat'], 4), 2);
            $loanDetails[$loan->getIdLoan()]['fee_vat']         = $feeAmounts['vat'];
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
        $debtCollectionFeeDetailRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:DebtCollectionFeeDetail');
        $projectRepaymentTaskRepository    = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectRepaymentTask');

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
        $debtCollectionFeeDetailRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:DebtCollectionFeeDetail');
        $projectChargeRepository           = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectCharge');

        $feeAmounts                    = $debtCollectionFeeDetailRepository->getAmountsByTypeAndWireTransferIn(DebtCollectionFeeDetail::TYPE_PROJECT_CHARGE, $wireTransferIn);
        $chargeDetails['charge']       = $projectChargeRepository->getTotalChargeByWireTransferIn($wireTransferIn);
        $chargeDetails['fee_tax_excl'] = round(bcsub($feeAmounts['amountTaxIncl'], $feeAmounts['vat'], 4), 2);
        $chargeDetails['fee_vat']      = $feeAmounts['vat'];

        return $chargeDetails;
    }

    /**
     * @param Loans $loan
     *
     * @return array
     */
    private function getLoanBasicInformation(Loans $loan)
    {
        $companyRepository       = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Companies');
        $clientAddressRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ClientsAdresses');

        $client        = $loan->getIdLender()->getIdClient();
        $company       = $companyRepository->findOneBy(['idClientOwner' => $client]);
        $postalAddress = $clientAddressRepository->findOneBy(['idClient' => $client]);

        $companyName = '';
        if ($company) {
            $companyName = $company->getName();
        }

        return [
            'name'         => $client->getNom(),
            'first_name'   => $client->getPrenom(),
            'email'        => $client->getEmail(),
            'type'         => $client->getType(),
            'company_name' => $companyName,
            'birthday'     => $client->getNaissance(),
            'telephone'    => $client->getTelephone(),
            'mobile'       => $client->getMobile(),
            'address'      => $postalAddress->getAdresse1() . ' ' . $postalAddress->getAdresse2() . ' ' . $postalAddress->getAdresse3(),
            'postal_code'  => $postalAddress->getCp(),
            'city'         => $postalAddress->getVille(),
            'amount'       => round(bcdiv($loan->getAmount(), 100, 4), 2),
        ];
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
            'Montant remboursé',
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
}
