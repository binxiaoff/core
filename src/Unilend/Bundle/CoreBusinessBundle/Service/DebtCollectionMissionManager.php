<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\DebtCollectionMission;
use Unilend\Bundle\CoreBusinessBundle\Entity\DebtCollectionMissionPaymentSchedule;
use Unilend\Bundle\CoreBusinessBundle\Entity\EcheanciersEmprunteur;
use Unilend\Bundle\CoreBusinessBundle\Entity\Loans;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectCharge;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRepaymentTask;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\TaxType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;
use Unilend\Bundle\CoreBusinessBundle\Service\Repayment\ProjectRepaymentManager;

class DebtCollectionMissionManager
{
    const CLIENT_HASH_MCS      = '2f9f590e-d689-11e6-b3d7-005056a378e2';
    const CLIENT_HASH_PROGERIS = 'f12f0f5b-1867-11e7-a89f-0050569e51ae';

    const DEBT_COLLECTION_MISSION_FOLDER = 'debt_collection_missions';
    const FILE_EXTENSION                 = '.xlsx';

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var ProjectRepaymentManager
     */
    private $projectRepaymentManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $protectedPath;

    /** @var Filesystem */
    private $fileSystem;

    /**
     * DebtCollectionMissionManager constructor.
     *
     * @param EntityManager           $entityManager
     * @param ProjectRepaymentManager $projectRepaymentManager
     * @param LoggerInterface         $logger
     * @param Filesystem              $filesystem
     * @param                         $protectedPath
     */
    public function __construct(EntityManager $entityManager, ProjectRepaymentManager $projectRepaymentManager, LoggerInterface $logger, Filesystem $filesystem, $protectedPath)
    {
        $this->entityManager           = $entityManager;
        $this->projectRepaymentManager = $projectRepaymentManager;
        $this->logger                  = $logger;
        $this->fileSystem              = $filesystem;
        $this->protectedPath           = $protectedPath;
    }

    /**
     * @param DebtCollectionMission $debtCollectionMission
     */
    public function generateExcelFile(DebtCollectionMission $debtCollectionMission)
    {
        if ($debtCollectionMission) {
            /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\DebtCollectionMissionPaymentSchedule[] $missionPaymentSchedules */
            $missionPaymentSchedules = $debtCollectionMission->getDebtCollectionMissionPaymentSchedules();

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
            $feeColumn         = [];
            $chargeColumn      = null;
            $totalColumn       = null;
            foreach ($titles as $title) {
                $activeSheet->setCellValue($titleColumn . $titleRow, $title);
                $titleColumn++;
            }

            $paymentScheduleTitleCellLeft = $titleColumn;
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

            $activeSheet->setCellValue($paymentScheduleTitleCellLeft . $titleRow, 'Frais');

            $paymentScheduleTitleCellLeft++;
            $activeSheet->setCellValue($paymentScheduleTitleCellLeft . $titleRow, 'Honoraires');

            $paymentScheduleTitleCellLeft++;
            $activeSheet->setCellValue($paymentScheduleTitleCellLeft . $titleRow, 'Tva');

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

                $dataColumn++;
                if (empty($chargeColumn)) {
                    $chargeColumn = $dataColumn;
                }

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

                $dataColumn++;
                if (empty($totalColumn)) {
                    $totalColumn = $dataColumn;
                }
                $activeSheet->setCellValueExplicitByColumnAndRow($dataColumn, $dataRow, $loanDetails['total'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            }

            $commissionDetails = $creditorDetails['commission'];
            $dataRow++;
            $activeSheet->setCellValueByColumnAndRow(0, $dataRow, 'Commission unilend');
            foreach ($commissionColumns['schedule'] as $sequence => $column) {
                $activeSheet->setCellValueExplicitByColumnAndRow($column, $dataRow, $commissionDetails['schedule'][$sequence], \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            }
            $activeSheet->setCellValueExplicitByColumnAndRow($feeColumn['fee_tax_excl'], $dataRow, $commissionDetails['fee_tax_excl'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicitByColumnAndRow($feeColumn['fee_vat'], $dataRow, $commissionDetails['fee_vat'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicitByColumnAndRow($totalColumn, $dataRow, $commissionDetails['total'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);

            $chargeDetails = $creditorDetails['charge'];
            $dataRow++;
            $activeSheet->setCellValueByColumnAndRow(0, $dataRow, 'Frais');
            $activeSheet->setCellValueExplicitByColumnAndRow($chargeColumn, $dataRow, $chargeDetails['charge'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicitByColumnAndRow($feeColumn['fee_tax_excl'], $dataRow, $chargeDetails['fee_tax_excl'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicitByColumnAndRow($feeColumn['fee_vat'], $dataRow, $chargeDetails['fee_vat'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicitByColumnAndRow($totalColumn, $dataRow, $chargeDetails['total'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);

            $fileName     = 'recouvrement_' . $debtCollectionMission->getId() . '_' . $debtCollectionMission->getAdded()->format('Y-m-d');
            $absolutePath = implode(DIRECTORY_SEPARATOR, [$this->protectedPath, self::DEBT_COLLECTION_MISSION_FOLDER, trim($debtCollectionMission->getIdClientDebtCollector()->getIdClient()), $debtCollectionMission->getIdProject()->getIdProject()]);

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
                $this->projectRepaymentManager->prepare($projectRepaymentTask);
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
        $debtCollectionMissionRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:DebtCollectionMission');
        /** @var DebtCollectionMission $currentMission */
        $currentMission  = $debtCollectionMissionRepository->findOneBy(['idProject' => $project, 'idClientDebtCollector' => $debtCollector, 'archived' => null]);
        $totalCapital    = 0;
        $totalInterest   = 0;
        $totalCommission = 0;

        try {
            $this->entityManager->getConnection()->beginTransaction();

            if ($currentMission) {
                $currentMission->setArchived(new \DateTime())
                    ->setIdUserArchiving($user);
                $this->entityManager->flush($currentMission);
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

            $paymentRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur');

            /** @var EcheanciersEmprunteur[] $pendingPayments */
            $pendingPayments     = $paymentRepository->findBy(
                [
                    'idProject'        => $project,
                    'statusEmprunteur' => [EcheanciersEmprunteur::STATUS_PENDING, EcheanciersEmprunteur::STATUS_PARTIALLY_PAID]
                ],
                ['dateEcheanceEmprunteur' => 'ASC']
            );
            $now                 = (new \DateTime())->setTime(23, 59, 59);
            $closeOutNettingDate = $project->getCloseOutNettingDate();

            if (null === $closeOutNettingDate) {
                foreach ($pendingPayments as $key => $payment) {
                    if ($now > $payment->getDateEcheanceEmprunteur()) {
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
                    }
                }
            } else {
                /** @todo Use information from new table that holds close out netting information */
                $closeOutNettingDate->setTime(0, 0, 0);

                foreach ($pendingPayments as $key => $payment) {
                    if ($project->getCloseOutNettingDate() > $payment->getDateEcheanceEmprunteur()) {
                        $totalCapital    = bcadd($totalCapital, bcsub($payment->getCapital(), $payment->getPaidCapital(), 4), 4);
                        $totalInterest   = bcadd($totalCapital, bcsub($payment->getInterets(), $payment->getPaidInterest(), 4), 4);
                        $totalCommission = bcadd($totalCapital, bcsub(bcadd($payment->getCommission(), $payment->getTva(), 4), $payment->getPaidCommissionVatIncl(), 4), 4);
                    } else {
                        $totalCapital = bcadd($totalCapital, bcsub($payment->getCapital(), $payment->getPaidCapital(), 4), 4);
                    }
                }

            }
            $newMission->setCapital($totalCapital)
                ->setInterest($totalInterest)
                ->setCommissionVatIncl($totalCommission);
            $this->entityManager->flush($newMission);

            $this->entityManager->getConnection()->commit();

            return $newMission;
        } catch (\Exception $exception) {
            $this->entityManager->getConnection()->rollBack();
            $this->logger->error('Error when creating new debt collection mission on project: ' . $project->getTitle() . ' Error: ' . $exception->getMessage() . ' In file: ' . $exception->getFile() . ' At line: ' . $exception->getLine(),
                ['method' => __METHOD__, 'id_project' => $project->getIdProject(), 'debt_collector' => $debtCollector->getIdClient()]);

            return false;
        }
    }
}
