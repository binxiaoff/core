<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
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

    <div style="float: left;padding:0 0 0 300px;">
        <b><?= $this->lng['preteur-operations-pdf']['paris-le'] ?> <?= (date('d/m/Y')) ?></b>
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
    <b><?= $this->lng['preteur-operations-pdf']['objet-releve-doperations-de-votre-compte-unilend-n'] ?><?= $this->clients->id_client ?></b>
    <br/>
    <?= $this->lng['preteur-operations-pdf']['titulaire'] ?> <?= $this->companies->name ?>
    <br/>
    <?= $this->lng['preteur-operations-pdf']['Representant-legal'] ?> <?= $this->clients->civilite . ' ' . $this->clients->prenom . ' ' . $this->clients->nom ?>
    <br/>
</div>
<br/>
<br/>

<table class="table vos_operations" border="0" cellspacing="0" cellpadding="0">
    <thead>
    <tr>
        <th width="200px" id="order_operations" align="center" class="col1" style="padding-left: 0px;">
            <div class="th-wrap" style='top:-3px;width: 300px;'>
                <div class="title-ope" style="color: black"><?= $this->lng['espace-emprunteur']['operation'] ?>&nbsp;
                </div>
            </div>
        </th>
        <th width="200px" id="order_id_projet" align="center" class="col1" style="padding-left: 0px;">
            <div class="th-wrap" style='top:-3px;width: 100px;'>
                <div class="title-ope" style="color: black"><?= $this->lng['espace-emprunteur']['projet'] ?>&nbsp;
                </div>
            </div>
        </th>
        <th width="140px" id="order_date">
            <div class="th-wrap">
                <div class="title-ope" style="color: black"><?= $this->lng['espace-emprunteur']['date-de-loperation'] ?>&nbsp;
                </div>
            </div>
        </th>
        <th width="180px" id="order_montant">
            <div class="th-wrap" style="top:-2px;">
                <div class="title-ope" style="color: black"><?= $this->lng['espace-emprunteur']['montant-de-loperation'] ?>&nbsp;
                </div>
            </div>
        </th>
    </tr>
    </thead>
    <tbody>

    <?php foreach ($this->aBorrowerOperations as $aOperation) : ?>

    <tr>
        <td><?= $this->lng['espace-emprunteur'][ 'operations-type-' . $aOperation['type'] ] ?></td>
        <td style="text-align: center;"><?= $aOperation['id_project'] ?></td>
        <td style="text-align: right;"><?= $this->dates->formatDateMysqltoShortFR($aOperation['date']) ?></td>
        <td style="text-align: right;"><?= $this->ficelle->formatnumber($aOperation['montant']) ?> &euro;</td>
    </tr>

    <?php if ($aOperation['type'] == 'commission-mensuelle') : ?>
    <tr>
        <td style="text-align: right"><?= $this->lng['espace-emprunteur']['operations-type-commission-ht'] ?></td>
        <td></td>
        <td></td>
        <td style="text-align: right;"><?=  $this->ficelle->formatnumber($aOperation['commission']) ?> &euro;</td>
        <td></td>
    </tr>
    <tr>
        <td style="text-align: right"><?=  $this->lng['espace-emprunteur']['operations-type-tva'] ?></td>
        <td></td>
        <td></td>
        <td style="text-align: right;"><?=   $this->ficelle->formatnumber($aOperation['tva']) ?> &euro;</td>
        <td></td
    </tr>
    <?php endif;
    endforeach; ?>
    </tbody>
</table>
<div class="pdfFooter">

    <?= $this->lng['preteur-operations-pdf']['prestataire-de-services-de-paiement'] ?><br/>
    <?= $this->lng['preteur-operations-pdf']['agent-prestataire-de-services-de-paiement'] ?><br/>


</div>

