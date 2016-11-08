<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en-US" xmlns="http://www.w3.org/1999/xhtml" dir="ltr">
<head>
    <title>Vos operations</title>
    <meta http-equiv="Content-type" content="text/html; charset=utf-8"/>
    </head>
<body>
<div class="pdfHeader">
    <div class="logo"></div>
    <br/><br/>

    <div style="float: left;">
        <b>Unilend – Société Française pour le financement des PME</b><br/>
        6 Rue du général Clergerie<br/>
        75116 Paris
    </div>

    <div style="float: right;">
        <b><?= $this->translator->trans('preteur-operations-pdf_paris-le') ?> <?= (date('d/m/Y')) ?></b>
        <br/><br/><br/>
        <b><?= $this->companies->name ?></b>
        <br/>
        <?= $this->companies->adresse1 ?>
        <br/>
        <?= $this->companies->zip . ' ' . $this->companies->city ?>
        <br/>
    </div>

    <div style="clear:both;"></div>
    <br/>
    <b><?= $this->translator->trans('preteur-operations-pdf_objet-releve-doperations-de-votre-compte-unilend-n') ?><?= $this->clients->id_client ?></b>
    <br/>
    <?= $this->translator->trans('preteur-operations-pdf_titulaire') ?> <?= $this->companies->name ?>
    <br/>
    <?= $this->translator->trans('preteur-operations-pdf_representant-legal') ?> <?= $this->clients->civilite . ' ' . $this->clients->prenom . ' ' . $this->clients->nom ?>
    <br/>
</div>
<br/>
<br/>
<table class="table vos_operations" border="0" cellspacing="0" cellpadding="0">
    <thead>
    <tr>
        <th><?= $this->translator->trans('espace-emprunteur_operation') ?></th>
        <th><?= $this->translator->trans('espace-emprunteur_projet') ?></th>
        <th><?= $this->translator->trans('espace-emprunteur_date-de-loperation') ?></th>
        <th><?= $this->translator->trans('espace-emprunteur_montant-de-loperation') ?></th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($this->aBorrowerOperations as $aOperation) : ?>
        <tr>
            <td><?= $this->translator->trans('espace-emprunteur_operations-type-' . $aOperation['type'] ) ?></td>
            <td style="text-align: center;"><?= $aOperation['id_project'] ?></td>
            <td style="text-align: right;"><?= $this->dates->formatDateMysqltoShortFR($aOperation['date']) ?></td>
            <td style="text-align: right;"><?= $this->ficelle->formatnumber($aOperation['montant']) ?> &euro;</td>
        </tr>
        <?php if ($aOperation['type'] == 'commission-mensuelle') : ?>
        <tr>
            <td style="text-align: right"><?= $this->translator->trans('espace-emprunteur_operations-type-commission-ht') ?></td>
            <td></td>
            <td></td>
            <td style="text-align: right;"><?=  $this->ficelle->formatnumber($aOperation['commission']) ?> &euro;</td>
            <td></td>
        </tr>
        <tr>
            <td style="text-align: right"><?=  $this->translator->trans('espace-emprunteur_operations-type-tva') ?></td>
            <td></td>
            <td></td>
            <td style="text-align: right;"><?=   $this->ficelle->formatnumber($aOperation['tva']) ?> &euro;</td>
            <td></td
        </tr>
        <?php endif; ?>
    <?php endforeach; ?>
    </tbody>
</table>
<div class="pdfFooter">
    <?= $this->translator->trans('preteur-operations-pdf_prestataire-de-services-de-paiement') ?><br/>
    <?= $this->translator->trans('preteur-operations-pdf_agent-prestataire-de-services-de-paiement') ?><br/>
</div>
