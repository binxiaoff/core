<?php

header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename=export.csv');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Expires: 0');

PHPExcel_Settings::setCacheStorageMethod(
    PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp,
    array('memoryCacheSize' => '2048MB', 'cacheTime' => 1200)
);

$oDocument    = new PHPExcel();
$oActiveSheet = $oDocument->setActiveSheetIndex(0);
$oActiveSheet->setCellValueByColumnAndRow(0, 1, 'ID');
$oActiveSheet->setCellValueByColumnAndRow(1, 1, 'Motif');
$oActiveSheet->setCellValueByColumnAndRow(2, 1, 'Montant');
$oActiveSheet->setCellValueByColumnAndRow(3, 1, 'Attribution');
$oActiveSheet->setCellValueByColumnAndRow(4, 1, 'ID client');
$oActiveSheet->setCellValueByColumnAndRow(5, 1, 'Date');

foreach ($this->aOperations as $iRowIndex => $aRow) {
    $iColIndex = 0;
    $oActiveSheet->setCellValueByColumnAndRow($iColIndex++, $iRowIndex + 2, $aRow['id_reception']);
    $oActiveSheet->setCellValueByColumnAndRow($iColIndex++, $iRowIndex + 2, $aRow['motif']);
    $oActiveSheet->setCellValueByColumnAndRow($iColIndex++, $iRowIndex + 2, $aRow['montant'] / 100);

    if (1 == $aRow['status_bo'] && isset($this->aUsers[$aRow['id_user']])) {
        $oActiveSheet->setCellValueByColumnAndRow($iColIndex++, $iRowIndex + 2, $this->aUsers[$aRow['id_user']]['firstname'] . ' ' . $this->aUsers[$aRow['id_user']]['name'] . ' - ' . date('d/m/Y H:i:s', strtotime($aRow['assignment_date'])));
    } else {
        $oActiveSheet->setCellValueByColumnAndRow($iColIndex++, $iRowIndex + 2, $this->statusOperations[$aRow['status_bo']]);
    }

    $oActiveSheet->setCellValueByColumnAndRow($iColIndex++, $iRowIndex + 2, $aRow['id_client']);
    $oActiveSheet->setCellValueByColumnAndRow($iColIndex++, $iRowIndex + 2, date('d/m/Y', strtotime($aRow['added'])));
}

/** @var \PHPExcel_Writer_CSV $oWriter */
$oWriter = PHPExcel_IOFactory::createWriter($oDocument, 'CSV');
$oWriter->setDelimiter(';');
$oWriter->save('php://output');
