<?php
header("Pragma: no-cache");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0, public");
header("Expires: 0");

$handle = fopen('php://output', 'w+');
fputs($handle, "\xEF\xBB\xBF"); // add UTF-8 BOM in order to be compatible to Excel
fputcsv($handle, ['Id_translation', 'locale', 'Section', 'Nom', 'Traduction', 'Date d\'ajout', 'Date de mise à jour'], ';');

while ($record = $this->bdd->fetch_assoc($this->requete_result)) {
    fputcsv($handle, $record, ';');
}

fclose($handle);

$response->setStatusCode(200);
$response->headers->set('Content-Type', 'text/csv; charset=utf-8');
$response->headers->set('Content-Disposition', 'attachment; filename="export-translations.csv"');

?>
