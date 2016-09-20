<style type="text/css">
    .div-2-columns {
        display: -webkit-flex;
        display: flex;
        -webkit-flex: 1;
        -ms-flex: 1;
        flex: 1;
    }

    .div-left-pos, .div-right-pos {
        margin: 2px;
        min-width: 50%;
    }
</style>
<?php if (false === empty($this->eligible) && true === empty($this->afterDeadline) && true === empty($this->nextTaxExemptionRequestDone)): ?>
    <?= $this->fireView('../blocs/taxExemption') ?>
<?php endif; ?>
<div class="div-2-columns">
    <div class="div-left-pos">
        <p><?= $this->lng['etape1']['adresse-fiscale'] ?>
            <i class="icon-help tooltip-anchor" data-placement="right" title="<?= $this->lng['etape1']['info-adresse-fiscale'] ?>"></i>
        </p>
        <div class="row">
            <input disabled readonly type="text" value="<?= $this->fiscalAddress['address'] ?>" class="field"/>
        </div>
        <div class="row row-triple-fields">
            <input disabled readonly type="text" class="field field-small" value="<?= $this->fiscalAddress['zipCode'] ?>"/>
            <input disabled readonly type="text" class="field field-small" value="<?= $this->fiscalAddress['city'] ?>"/>
            <?php if (false === empty($this->fiscalAddress['country'])): ?>
                <input disabled readonly type="text" class="field field-small" value="<?= $this->fiscalAddress['country'] ?>"/>
            <?php endif; ?>
        </div>
    </div>
    <div class="div-right-pos">
        <p><?= $this->lng['preteur-operations']['desc-vos-documents'] ?></p>
        <?php if (count($this->liste_docs) > 0): ?>
            <table class="table vos_operations" border="0" cellspacing="0" cellpadding="0">
                <tr>
                    <th class="narrow-th" width="105">
                        <b><?= $this->lng['preteur-operations']['vos-documents-nom-colonne'] ?></b>
                    </th>
                    <th width="50">
                        <div class="th-wrap">
                            <i title="<?= $this->lng['profile']['info-8'] ?>" class="icon-empty-folder tooltip-anchor"></i>
                        </div>
                    </th>
                </tr>
                <?php
                $i = 1;
                foreach ($this->liste_docs as $doc) {
                    $annee          = $doc['annee'];
                    $annee_suivante = $annee + 1;
                    $libelle        = $this->lng['preteur-operations']['imprime-fiscal-unique-revenus'];
                    // on remplace les var de la trad
                    eval("\$libelle = \"$libelle\";");
                    ?>
                    <tr <?= ($i % 2 == 1 ? '' : 'class="odd"') ?>>
                        <td><?= $libelle ?></td>
                        <td>
                            <a class="tooltip-anchor icon-pdf" href="<?= $this->lurl . '/operations/get_ifu/' . $this->clients->hash . '/' . $annee ?>"></a>
                        </td>
                    </tr>
                    <?php
                }
                ?>
            </table>
        <?php else: ?>
            <?= $this->lng['preteur-operations']['aucun-document'] ?>
        <?php endif; ?>
    </div>
</div>
<div>
    <h2><?= $this->lng['preteur-operations']['titre-vos-documents-isf'] ?></h2>
    <?php
    $annee      = date('Y');
    $annee_prec = $annee - 1;
    $solde      = current(current($this->bdd->run("SELECT solde FROM indexage_vos_operations WHERE id_client = " . $this->clients->id_client . " AND date_operation < '$annee-01-01 00:00:00' ORDER BY date_operation DESC LIMIT 0,1"))) / 100 + current(current($this->bdd->run("SELECT sum(amount) FROM bids INNER JOIN `lenders_accounts` ON lenders_accounts.id_lender_account = bids.id_lender_account WHERE lenders_accounts.id_client_owner = " . $this->clients->id_client . " AND bids.added < '$annee-01-01 00:00:00' AND bids.updated >= '$annee-01-01 00:00:00'"))) / 100;

    $projects_en_remboursement = $this->bdd->run("SELECT id_project FROM `projects_status_history` WHERE id_project_status = (SELECT id_project_status FROM projects_status WHERE status = " . \projects_status::REMBOURSEMENT . ") AND added < '$annee-01-01 00:00:00'");
    foreach ($projects_en_remboursement as $key => $value) {
        $projects_en_remboursement[$key] = $value['id_project'];
    }

    $capital_du = current(current($this->bdd->run("SELECT sum(capital - capital_rembourse) FROM `echeanciers` INNER JOIN `lenders_accounts` ON lenders_accounts.id_lender_account = echeanciers.id_lender WHERE (date_echeance_reel >= '$annee-01-01 00:00:00' OR echeanciers.status = 0) AND id_project IN(" . implode(',', $projects_en_remboursement) . ") AND lenders_accounts.id_client_owner = " . $this->clients->id_client))) / 100;

    $solde      = $this->ficelle->formatNumber($solde);
    $capital_du = $this->ficelle->formatNumber($capital_du);

    $texte = $this->lng['preteur-operations']['texte-vos-documents-isf'];
    eval("\$texte = \"$texte\";");
    echo nl2br($texte); ?>
</div>
<div class="tax-exemption-history">
    <h2><?= $this->lng['lender-dashboard']['tax-exemption'] ?></h2>
    <?php if (false === empty($this->eligible) && false === empty($this->afterDeadline) && true === empty($this->nextTaxExemptionRequestDone)): ?>
        <span>
            <?= str_replace(
                '%taxExemptionRequestLimitDate%',
                $this->taxExemptionRequestLimitDate, $this->lng['lender-dashboard']['tax-reminder-message']
            ) ?>
        </span>
    <?php endif; ?>
    <?php foreach ( $this->taxExemptionHistory as $row ): ?>
        <span>
            <?= $this->lng['lender-dashboard']['tax-the'] . ' ' . strftime('%d %B %Y', \DateTime::createFromFormat('Y-m-d H:i:s', $row['added'])->getTimestamp()) . ', ' .
            str_replace(
                '%exemptionYear%',
                $row['year'],
                $this->lng['lender-dashboard']['tax-history-message']
            )
            ?>
        </span>
        <br>
    <?php endforeach; ?>
</div>
