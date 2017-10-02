<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\DebtCollectionMission;
use Unilend\Bundle\CoreBusinessBundle\Entity\Loans;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectCharge;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRepaymentTask;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
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
            'status'    => ProjectCharge::STATUS_PAID_BY_UNILEND
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

        $companyRepository                = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Companies');
        $clientAddressRepository          = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ClientsAdresses');
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
            $client        = $loan->getIdLender()->getIdClient();
            $company       = $companyRepository->findOneBy(['idClientOwner' => $client]);
            $postalAddress = $clientAddressRepository->findOneBy(['idClient' => $client]);

            $companyName = '';
            if ($company) {
                $companyName = $company->getName();
            }

            $loanDetails[$loan->getIdLoan()] = [
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
}
