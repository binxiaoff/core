<?php

header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename=export.csv');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Expires: 0');

PHPExcel_Settings::setCacheStorageMethod(
    PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp,
    array('memoryCacheSize' => '2048MB', 'cacheTime' => 1200)
);

$document    = new PHPExcel();
$activeSheet = $document->setActiveSheetIndex(0);
$activeSheet->setCellValueByColumnAndRow(0, 1, 'ID');
$activeSheet->setCellValueByColumnAndRow(1, 1, 'Motif');
$activeSheet->setCellValueByColumnAndRow(2, 1, 'Montant');
$activeSheet->setCellValueByColumnAndRow(3, 1, 'Attribution');
$activeSheet->setCellValueByColumnAndRow(4, 1, 'ID client');
$activeSheet->setCellValueByColumnAndRow(5, 1, 'Date');

foreach ($this->receptions as $index => $reception) {
    $colIndex = 0;
    $activeSheet->setCellValueByColumnAndRow($colIndex++, $index + 2, $reception->getIdReception());
    $activeSheet->setCellValueByColumnAndRow($colIndex++, $index + 2, $reception->getMotif());
    $activeSheet->setCellValueByColumnAndRow($colIndex++, $index + 2, str_replace('.', ',', bcdiv($reception->getMontant(), 100, 2)));

    if (1 == $reception->getStatusBo() && $reception->getIdUser()) {
        $activeSheet->setCellValueByColumnAndRow($colIndex++, $index + 2, $reception->getIdUser()->getFirstname() . ' ' . $reception->getIdUser()->getName() . ' - ' . $reception->getAssignmentDate()->format('d/m/Y à H:i:s'));
    } else {
        $activeSheet->setCellValueByColumnAndRow($colIndex++, $index + 2, $this->statusOperations[$reception->getStatusBo()]);
    }

    if (null === $reception->getIdClient()) {
        $activeSheet->setCellValueByColumnAndRow($colIndex++, $index + 2, $reception->getIdProject()->getIdCompany()->getIdClientOwner());
    } else {
        $activeSheet->setCellValueByColumnAndRow($colIndex++, $index + 2, $reception->getIdClient()->getIdClient());
    }

    $activeSheet->setCellValueByColumnAndRow($colIndex++, $index + 2, $reception->getAdded()->format('d/m/Y'));
}

/** @var \PHPExcel_Writer_CSV $writer */
$writer = PHPExcel_IOFactory::createWriter($document, 'CSV');
$writer->setDelimiter(';');
$writer->save('php://output');
