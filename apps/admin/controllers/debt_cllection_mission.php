<?php

use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\Zones;
use \Unilend\Bundle\CoreBusinessBundle\Entity\DebtCollectionMission;

class debt_cllection_missionController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->users->checkAccess(Zones::ZONE_LABEL_BORROWERS);

        $this->menu_admin = 'emprunteurs';
    }

    public function _creditor_details()
    {
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\DebtCollectionMissionManager $debtCollectionMissionManager */
        $debtCollectionMissionManager = $this->get('unilend.service.debt_collection_mission_manager');
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        if (false === empty($this->params[0])) {
            $missionId             = filter_var($this->params[0], FILTER_VALIDATE_INT);
            $debtCollectionMission = $entityManager->getRepository('UnilendCoreBusinessBundle:DebtCollectionMission')->find($missionId);
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

                $creditorDetails = $debtCollectionMissionManager->getCreditorsDetails($debtCollectionMission);

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
                    $activeSheet->setCellValueExplicitByColumnAndRow($dataColumn, $dataRow, $loanDetails['amount'], PHPExcel_Cell_DataType::TYPE_NUMERIC);

                    foreach ($missionPaymentSchedules as $missionPaymentSchedule) {
                        $sequence = $missionPaymentSchedule->getIdPaymentSchedule()->getOrdre();

                        $dataColumn++;
                        $activeSheet->setCellValueExplicitByColumnAndRow($dataColumn, $dataRow, $loanDetails['schedule'][$sequence]['remaining_capital'], PHPExcel_Cell_DataType::TYPE_NUMERIC);

                        $dataColumn++;
                        $activeSheet->setCellValueExplicitByColumnAndRow($dataColumn, $dataRow, $loanDetails['schedule'][$sequence]['remaining_interest'], PHPExcel_Cell_DataType::TYPE_NUMERIC);

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
                    $activeSheet->setCellValueExplicitByColumnAndRow($dataColumn, $dataRow, $loanDetails['fee_tax_excl'], PHPExcel_Cell_DataType::TYPE_NUMERIC);

                    $dataColumn++;
                    if (empty($feeColumn['fee_vat'])) {
                        $feeColumn['fee_vat'] = $dataColumn;
                    }
                    $activeSheet->setCellValueExplicitByColumnAndRow($dataColumn, $dataRow, $loanDetails['fee_vat'], PHPExcel_Cell_DataType::TYPE_NUMERIC);

                    $dataColumn++;
                    if (empty($totalColumn)) {
                        $totalColumn = $dataColumn;
                    }
                    $activeSheet->setCellValueExplicitByColumnAndRow($dataColumn, $dataRow, $loanDetails['total'], PHPExcel_Cell_DataType::TYPE_NUMERIC);
                }

                $commissionDetails = $creditorDetails['commission'];
                $dataRow++;
                $activeSheet->setCellValueByColumnAndRow(0, $dataRow, 'Commission unilend');
                foreach ($commissionColumns['schedule'] as $sequence => $column) {
                    $activeSheet->setCellValueExplicitByColumnAndRow($column, $dataRow, $commissionDetails['schedule'][$sequence], PHPExcel_Cell_DataType::TYPE_NUMERIC);
                }
                $activeSheet->setCellValueExplicitByColumnAndRow($feeColumn['fee_tax_excl'], $dataRow, $commissionDetails['fee_tax_excl'], PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $activeSheet->setCellValueExplicitByColumnAndRow($feeColumn['fee_vat'], $dataRow, $commissionDetails['fee_vat'], PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $activeSheet->setCellValueExplicitByColumnAndRow($totalColumn, $dataRow, $commissionDetails['total'], PHPExcel_Cell_DataType::TYPE_NUMERIC);

                $chargeDetails = $creditorDetails['charge'];
                $dataRow++;
                $activeSheet->setCellValueByColumnAndRow(0, $dataRow, 'Frais');
                $activeSheet->setCellValueExplicitByColumnAndRow($chargeColumn, $dataRow, $chargeDetails['charge'], PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $activeSheet->setCellValueExplicitByColumnAndRow($feeColumn['fee_tax_excl'], $dataRow, $chargeDetails['fee_tax_excl'], PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $activeSheet->setCellValueExplicitByColumnAndRow($feeColumn['fee_vat'], $dataRow, $chargeDetails['fee_vat'], PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $activeSheet->setCellValueExplicitByColumnAndRow($totalColumn, $dataRow, $chargeDetails['total'], PHPExcel_Cell_DataType::TYPE_NUMERIC);

                $fileName = 'recouvrement_' . $missionId . '_' . (new DateTime())->format('Y-m-d');

                /** @var \PHPExcel_Writer_Excel2007 $writer */
                $writer = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');

                header('Content-Type: application/force-download; charset=utf-8');
                header('Content-Disposition: attachment;filename=' . $fileName . '.xlsx');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Expires: 0');

                $writer->save('php://output');

                die;
            }
        }

        header('Location: ' . $this->url);
        die;
    }

    public function _add()
    {
        if (false === empty($this->params[0])) {
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager = $this->get('doctrine.orm.entity_manager');

            $projectId = filter_var($this->params[0], FILTER_VALIDATE_INT);

            if (null !== ($project = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($projectId))) {
                if (false === empty($_POST['debt-collector-hash'])){
                    $debtCollector = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($_POST['debt-collector-hash']);
                }
                if (empty($debtCollector)) {
                    $error = 'Le recouvreur n\'existe pas.';
                    return;
                }
                if (empty($_POST['debt-collection-type'])
                    || false === ($debtCollectionType = filter_var($_POST['debt-collection-type'], FILTER_VALIDATE_INT))
                    || false === in_array($debtCollectionType, [DebtCollectionMission::TYPE_AMICABLE, DebtCollectionMission::TYPE_AMICABLE, ])
                ) {
                    $error = 'Le type de mission est incorrect';
                    return;
                }
                if (empty($_POST['debt-collection-rate']) || false ===($debtCollectionRate = filter_var($_POST['debt-collection-rate'], FILTER_VALIDATE_FLOAT))) {
                    $error = 'Le taux d\'honoraires est obligatoire';
                    return;
                }
                $debtCollectionMissionRepository      = $entityManager->getRepository('UnilendCoreBusinessBundle:DebtCollectionMission');
                $debtCollectionMissionPaymentSchedule = $entityManager->getRepository('UnilendCoreBusinessBundle:DebtCollectionMissionPaymentSchedule');
                $user                                 = $entityManager->getRepository('UnilendCoreBusinessBundle:Users')->find($_SESSION['user']['id_user']);

                $currentMissions = $debtCollectionMissionRepository->findBy(['idProject' => $project, 'archived' => null]);
                try {
                    $entityManager->beginTransaction();
                    foreach ($currentMissions as $mission) {
                        $mission->setArchived(new \DateTime())
                            ->setIdUserArchiving($user);
                    }

                    $newMission = new DebtCollectionMission();
                    $newMission->setIdProject($project)
                        ->setIdClientDebtCollector($debtCollector)
                        ->setType($debtCollectionType)
                        ->setFeesRate($debtCollectionRate)
                        ->setIdUserCreation($user);

                    $entityManager->persist($newMission);
                    $entityManager->flush();
                    $entityManager->commit();

                    $success = 'Mission créée acec succé';
                } catch (Exception $exception) {
                    $entityManager->getConnection()->rollBack();
                    $this->get('logger')->error('Error when creating new debt collection mission on project: ' . $project->getTitle(), ['method' => __METHOD__, 'id_project' => $project->getIdProject()]);
                    $error = 'La mission n\'a pas été créée';
                }
            }
        }
    }
}
