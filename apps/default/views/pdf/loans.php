<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="fr-FR" xmlns="http://www.w3.org/1999/xhtml" dir="ltr">
<head>
    <title>Vos prêts</title>
    <meta http-equiv="Content-type" content="text/html; charset=utf-8"/>
    <link rel="stylesheet" href="<?= $this->surl ?>/styles/default/style.css" type="text/css" media="all"/>
    <link rel="stylesheet" href="<?= $this->surl ?>/styles/default/style-edit.css" type="text/css" media="all"/>
    <link rel="stylesheet" href="<?= $this->surl ?>/styles/default/pdf/styleOperations.css" type="text/css" media="all"/>
</head>
<body>
    <div class="pdfHeader">
        <div class="logo"></div>
        <br/><br/>
        <div style="float: left;">
            <strong>Unilend – Société Française pour le financement des PME</strong><br/>
            6 Rue du général Clergerie<br/>
            75116 Paris
        </div>
        <div style="float: left;padding:0 0 0 300px;">
            <b><?= $this->lng['preteur-operations-pdf']['paris-le'] ?> <?= (date('d/m/Y')) ?></b>
            <br/><br/><br/>
            <?
            if (isset($this->oLendersAccounts->id_company) && $this->oLendersAccounts->id_company != 0) {

                $this->companies->get($this->oLendersAccounts->id_company, 'id_company');
                ?>
                <b><?= $this->companies->name ?></b><br/>
                <?= $this->companies->adresse1 ?><br/>
                <?= $this->companies->zip . ' ' . $this->companies->city ?>
                <?
            } else {
                ?>
                <b><?= $this->clients->prenom . ' ' . $this->clients->nom ?></b><br/>
                <?= $this->clients_adresses->adresse1 ?><br/>
                <?= $this->clients_adresses->cp . ' ' . $this->clients_adresses->ville ?>
                <?
            }
            ?>
        </div>

        <div style="clear:both;"></div>
        <br/>
        <strong>Historique des projets financés par votre compte Unilend n°<?= $this->clients->id_client ?></strong><br/>
        <?= $this->lng['preteur-operations-pdf']['titulaire'] ?> <?= (isset($this->oLendersAccounts->id_company) && $this->oLendersAccounts->id_company != 0 ? $this->companies->name : $this->clients->prenom . ' ' . $this->clients->nom) ?>
        <br/>
        <?php if (isset($this->oLendersAccounts->id_company) && $this->oLendersAccounts->id_company != 0): ?>
            <?= $this->lng['preteur-operations-pdf']['Representant-legal'] ?> <?= $this->clients->civilite . ' ' . $this->clients->prenom . ' ' . $this->clients->nom ?><br/>
        <?php endif; ?>
    </div>

    <br/><br/>

    <table class="table vos_operations detail-ope finances">
        <tr>
            <th>Statut</th>
            <th>Projet</th>
            <th>Montant prêté</th>
            <th>Taux d'intérêts</th>
            <th>Date de première échéance</th>
            <th>Prochaine échéance</th>
            <th>Date de dernière échéance</th>
            <th>Mensualité</th>
        </tr>
        <?php foreach ($this->lSumLoans as $aProjectLoans): ?>
            <?php if ($aProjectLoans['project_status'] >= \projects_status::REMBOURSEMENT): ?>
                <tr>
                    <td><?= $this->lng['preteur-operations-detail']['filter-status-' . $aProjectLoans['project_status']] ?></td>
                    <td class="description"><?= $aProjectLoans['name'] ?></td>
                    <td style="white-space: nowrap;"><?= $this->ficelle->formatNumber($aProjectLoans['amount'], 0) ?> €</td>
                    <td style="white-space: nowrap;"><?= $this->ficelle->formatNumber($aProjectLoans['rate'], 1) ?> %</td>
                    <td><?= $this->dates->formatDate($aProjectLoans['debut'], 'd/m/Y') ?></td>
                    <?php if (in_array($aProjectLoans['project_status'], array(\projects_status::REMBOURSE, \projects_status::REMBOURSEMENT_ANTICIPE))) { ?>
                        <td colspan="2">Remboursé intégralement <br /> le <?= $this->dates->formatDate($aProjectLoans['status_change'], 'd/m/Y')?></td>
                    <?php } else { ?>
                        <td><?= $this->dates->formatDate($aProjectLoans['next_echeance'], 'd/m/Y') ?></td>
                        <td><?= $this->dates->formatDate($aProjectLoans['fin'], 'd/m/Y') ?></td>
                    <?php } ?>
                    <td><?= $this->ficelle->formatNumber($aProjectLoans['mensuel']) ?> <?= $this->lng['preteur-operations-detail']['euros-par-mois'] ?></td>
                </tr>
            <?php endif; ?>
        <?php endforeach; ?>
    </table>

    <br/><br/>

    <div class="pdfFooter">
        <?= $this->lng['preteur-operations-pdf']['prestataire-de-services-de-paiement'] ?><br/>
        <?= $this->lng['preteur-operations-pdf']['agent-prestataire-de-services-de-paiement'] ?><br/>
    </div>
