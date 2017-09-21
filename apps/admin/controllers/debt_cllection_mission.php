<?php

use Unilend\Bundle\CoreBusinessBundle\Entity\Zones;

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

                $titles      = [
                    'ID de prêt',
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
                    'montant de prêt'
                ];
                $titleColumn = 'A';
                $titleRow    = 2;
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
                    $activeSheet->setCellValueExplicitByColumnAndRow($dataColumn, $dataRow, in_array($loanDetails['type'], [1, 3]) ? 'Physique' : 'Moral');

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
                    }

                    $dataColumn++; // frais

                    $dataColumn++;
                    $activeSheet->setCellValueExplicitByColumnAndRow($dataColumn, $dataRow, $loanDetails['fee_tax_excl'], PHPExcel_Cell_DataType::TYPE_NUMERIC);

                    $dataColumn++;
                    $activeSheet->setCellValueExplicitByColumnAndRow($dataColumn, $dataRow, $loanDetails['fee_vat'], PHPExcel_Cell_DataType::TYPE_NUMERIC);

                    $dataColumn++;
                    $activeSheet->setCellValueExplicitByColumnAndRow($dataColumn, $dataRow, $loanDetails['total'], PHPExcel_Cell_DataType::TYPE_NUMERIC);
                }

                $fileName = 'recouvrement_' . $missionId;

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
}
