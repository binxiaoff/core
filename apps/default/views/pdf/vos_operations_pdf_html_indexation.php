<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="fr-FR" xmlns="http://www.w3.org/1999/xhtml" dir="ltr">
<head>
    <title>Vos operations</title>
    <meta http-equiv="Content-type" content="text/html; charset=utf-8" />
    <link rel="stylesheet" href="<?= $this->surl ?>/styles/default/style.css" type="text/css" media="all" />
    <link rel="stylesheet" href="<?= $this->surl ?>/styles/default/style-edit.css" type="text/css" media="all" />
    <link rel="stylesheet" href="<?= $this->surl ?>/styles/default/pdf/styleOperations.css" type="text/css" media="all" />
</head>
<body>
    <div class="pdfHeader">
        <div class="logo"></div>
        <br /><br />
        <div style="float: left;">
            <strong>Unilend – Société Française pour le financement des PME</strong><br />
            6 Rue du général Clergerie<br />
            75116 Paris
        </div>
        <div style="float: right;">
            <b><?= $this->lng['preteur-operations-pdf']['paris-le'] ?> <?= date('d/m/Y') ?></b>
            <br /><br /><br />
            <?php if (false === empty($this->oLendersAccounts->id_company_owner)) : ?>
                <?php $this->companies->get($this->oLendersAccounts->id_company_owner); ?>
                <b><?= $this->companies->name ?></b><br/>
                <b><?= $this->clients->prenom . ' ' . $this->clients->nom ?></b><br/>
                <?= $this->companies->adresse1 ?><br/>
                <?= $this->companies->zip . ' ' . $this->companies->city ?>
            <?php else : ?>
                <b><?= $this->clients->prenom . ' ' . $this->clients->nom ?></b><br/>
                <?= $this->clients_adresses->adresse1 ?><br/>
                <?= $this->clients_adresses->cp . ' ' . $this->clients_adresses->ville ?>
            <?php endif; ?>
        </div>
        <div style="clear:both;"></div>
        <br />
        <strong><?= $this->lng['preteur-operations-pdf']['objet-releve-doperations-de-votre-compte-unilend-n'] ?><?= $this->clients->id_client ?></strong><br />
        <?= $this->lng['preteur-operations-pdf']['titulaire'] ?> <?= (empty($this->oLendersAccounts->id_company_owner) ? $this->clients->prenom . ' ' . $this->clients->nom : $this->companies->name) ?><br/>
        <?php if (false === empty($this->oLendersAccounts->id_company_owner)) : ?>
            <?= $this->lng['preteur-operations-pdf']['representant-legal'] ?> <?= $this->clients->civilite . ' ' . $this->clients->prenom . ' ' . $this->clients->nom ?><br/>
        <?php endif; ?>
    </div>

    <br /><br />

    <table class="table vos_operations" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <th><?= $this->lng['preteur-operations-pdf']['operations'] ?></th>
            <th><?= $this->lng['preteur-operations-pdf']['info-titre-loan-id'] ?></th>
            <th><?= $this->lng['preteur-operations-pdf']['info-titre-projets'] ?></th>
            <th><?= $this->lng['preteur-operations-pdf']['date-de-loperation'] ?></th>
            <th><?= $this->lng['preteur-operations-pdf']['montant-de-loperation'] ?></th>
            <th><?= $this->lng['preteur-operations-pdf']['info-titre-solde-compte'] ?></th>
        </tr>
        <?php

        $i          = 1;
        $asterix_on = false;

        if (isset($this->lTrans)) {
            foreach ($this->lTrans as $t) {
                $t['solde']               = ($t['solde'] / 100);
                $t['montant_prelevement'] = ($t['montant_prelevement'] / 100);

                if ($t['montant_operation'] > 0) {
                    $plus    = '<b style="color:#40b34f;">+</b>';
                    $moins   = '';
                    $couleur = 'style="color:#40b34f;"';
                } else {
                    $plus    = '';
                    $moins   = '<b style="color:red;">-</b>';
                    $couleur = 'style="color:red;"';
                }

                if ($t['solde'] > 0) {
                    $solde = $t['solde'];
                }

                // Remb preteur
                if (in_array($t['type_transaction'], array(\transactions_types::TYPE_LENDER_REPAYMENT, \transactions_types::TYPE_LENDER_ANTICIPATED_REPAYMENT, \transactions_types::TYPE_LENDER_RECOVERY_REPAYMENT))) {
                    $this->echeanciers->get($t['id_echeancier'], 'id_echeancier');
                    $retenuesfiscals = $this->echeanciers->prelevements_obligatoires + $this->echeanciers->retenues_source + $this->echeanciers->csg + $this->echeanciers->prelevements_sociaux + $this->echeanciers->contributions_additionnelles + $this->echeanciers->prelevements_solidarite + $this->echeanciers->crds;
                    ?>
                    <!-- debut transasction remb -->
                    <tr class="transact remb_<?= $t['id_transaction'] ?> <?= ($i % 2 == 1 ? '' : 'odd') ?>">
                        <td><?= $t['libelle_operation'] ?></td>
                        <td><?= $t['bdc'] ?></td>
                        <td class="companieleft"><?= $t['libelle_projet'] ?></td>
                        <td><?= $this->dates->formatDate($t['date_operation'], 'd-m-Y') ?></td>
                        <td <?= $couleur ?>><?= $this->ficelle->formatNumber($t['montant_operation'] / 100) ?> €</td>
                        <td><?= $this->ficelle->formatNumber($t['solde']) ?> €</td>
                    </tr>
                    <tr class="content_transact <?= ($i % 2 == 1 ? '' : 'odd') ?>" height="0">
                        <td colspan="7">
                            <div class="div_content_transact content_remb_<?= $t['id_transaction'] ?>">
                                <table class="soustable" width="100%">
                                    <tbody>
                                    <tr>
                                        <td width="146px"
                                            class="detail_remb"><?= $this->lng['preteur-operations-vos-operations']['voici-le-detail-de-votre-remboursement'] ?></td>
                                        <td width="145px"
                                            class="detail_left"><?= $this->lng['preteur-operations-vos-operations']['capital-rembourse'] ?></td>
                                        <td width="100px" class="chiffres"
                                            style="padding-bottom:8px; color:#40b34f;"><?= $this->ficelle->formatNumber(($t['montant_capital'] / 100)) ?>
                                            €
                                        </td>
                                        <td width="107px">&nbsp;</td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td class="detail_left"><?= $this->lng['preteur-operations-vos-operations']['interets-recus'] ?></td>
                                        <td class="chiffres"
                                            style="color:#40b34f;"><?= $this->ficelle->formatNumber(($t['montant_interet'] / 100)) ?>
                                            €
                                        </td>
                                        <td>&nbsp;</td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td class="detail_left"><?= $t['libelle_prelevement'] ?></td>
                                        <td class="chiffres" style="color:red;">
                                            -<?= $this->ficelle->formatNumber($t['montant_prelevement']) ?> €
                                        </td>
                                        <td>&nbsp;</td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" style=" height:4px;"></td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </td>
                    </tr>
                    <!-- fin transasction remb -->
                    <?php
                    $i++;
                } elseif (in_array($t['type_transaction'], array(8, 1, 3, 4, 16, 17, 19, 20))) {
                    // Récupération de la traduction et non plus du libelle dans l'indexation (si changement on est ko)
                    switch ($t['type_transaction']) {
                        case 8:
                            $t['libelle_operation'] = $this->lng['preteur-operations-vos-operations']['retrait-dargents'];
                            break;
                        case 1:
                            $t['libelle_operation'] = $this->lng['preteur-operations-vos-operations']['depot-de-fonds'];
                            break;
                        case 3:
                            $t['libelle_operation'] = $this->lng['preteur-operations-vos-operations']['depot-de-fonds'];
                            break;
                        case 4:
                            $t['libelle_operation'] = $this->lng['preteur-operations-vos-operations']['depot-de-fonds'];
                            break;
                        case 16:
                            $t['libelle_operation'] = $this->lng['preteur-operations-vos-operations']['offre-de-bienvenue'];
                            break;
                        case 17:
                            $t['libelle_operation'] = $this->lng['preteur-operations-vos-operations']['retrait-offre'];
                            break;
                        case 19:
                            $t['libelle_operation'] = $this->lng['preteur-operations-vos-operations']['gain-filleul'];
                            break;
                        case 20:
                            $t['libelle_operation'] = $this->lng['preteur-operations-vos-operations']['gain-parrain'];
                            break;
                    }

                    $type = "";
                    if ($t['type_transaction'] == 8 && $t['montant_operation'] > 0) {
                        $type = "Annulation retrait des fonds - compte bancaire clos";
                    } else {
                        $type = $t['libelle_operation'];
                    }
                    ?>

                    <tr <?= ($i % 2 == 1 ? '' : 'class="odd"') ?>>
                        <td><?= $type ?></td>
                        <td></td>
                        <td></td>
                        <td><?= $this->dates->formatDate($t['date_operation'], 'd-m-Y') ?></td>
                        <td <?= $couleur ?>><?= $this->ficelle->formatNumber($t['montant_operation'] / 100) ?> €</td>
                        <td><?= $this->ficelle->formatNumber($t['solde']) ?> €</td>
                    </tr>
                    <?php
                    $i++;
                } elseif (in_array($t['type_transaction'], array(2))) {
                    $bdc = $t['bdc'];
                    if ($t['bdc'] == 0) {
                        $bdc = "";
                    }

                    //asterix pour les offres acceptees
                    $asterix       = "";
                    $offre_accepte = false;
                    if ($t['libelle_operation'] == $this->lng['preteur-operations-vos-operations']['offre-acceptee']) {
                        $asterix       = " *";
                        $offre_accepte = true;
                        $asterix_on    = true;
                    }


                    ?>
                    <tr <?= ($i % 2 == 1 ? '' : 'class="odd"') ?>>
                        <td><?= $t['libelle_operation'] ?></td>
                        <td><?= $bdc ?></td>
                        <td class="companieleft"><?= $t['libelle_projet'] ?></td>
                        <td><?= $this->dates->formatDate($t['date_operation'], 'd-m-Y') ?></td>
                        <td <?= (!$offre_accepte ? $couleur : '') ?>><?= $this->ficelle->formatNumber($t['montant_operation'] / 100) . ' €' ?></td>
                        <td><?= $this->ficelle->formatNumber($t['solde']) ?> €<?= $asterix ?></td>
                    </tr>
                    <?php
                    $i++;
                }
            }
        }

        $soldetotal = $this->transactions->getSoldeDateLimite($this->clients->id_client, $this->date_fin);
        ?>
        <tr>
            <td colspan="7" ></td>
        </tr>
        <tr>
            <td colspan="7" ></td>
        </tr>
        <tr>
            <td colspan="7" ></td>
        </tr>
        <tr>
            <th colspan="2" class="pdfSolde"><?= str_replace('[#TOTAL#]', $this->ficelle->formatNumber($soldetotal), $this->lng['preteur-operations-pdf']['solde-de-votre-compte']) ?></th>
            <td></td>
            <td><?= $this->date_fin > date('Y-m-d') ? date('d/m/Y') : date('d/m/Y', strtotime($this->date_fin)) ?></td>
            <td></td>
            <td><?= $soldetotal ?> €</td>
        </tr>
    </table>

    <?php if($asterix_on) : ?>
        <div style="padding-left: 10px;margin-top:20px;">* <?= $this->lng['preteur-operations-vos-operations']['offre-acceptee-asterix-pdf'] ?></div>
    <? endif; ?>

    <br /><br />
    <div class="pdfFooter">
        <?= $this->lng['preteur-operations-pdf']['prestataire-de-services-de-paiement'] ?><br />
        <?= $this->lng['preteur-operations-pdf']['agent-prestataire-de-services-de-paiement'] ?><br />
    </div>
