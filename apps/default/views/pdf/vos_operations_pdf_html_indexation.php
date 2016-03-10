<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en-US" xmlns="http://www.w3.org/1999/xhtml" dir="ltr">
<head>
    <title>Vos operations indexation</title>
    <meta http-equiv="Content-type" content="text/html; charset=utf-8" />
    <link rel="stylesheet" href="<?=$this->surl?>/styles/default/style.css" type="text/css" media="all" />
    <link rel="stylesheet" href="<?=$this->surl?>/styles/default/style-edit.css" type="text/css" media="all" />
    <link rel="stylesheet" href="<?=$this->surl?>/styles/default/pdf/styleOperations.css" type="text/css" media="all" />
</head>
<body>

<div class="pdfHeader">
    <div class="logo"></div>
    <br /><br />
    <div style="float: left;">
        <b>Unilend – Société Française pour le financement des PME</b><br />
        6 Rue du général Clergerie<br />
        75116 Paris
    </div>

    <div style="float: left;padding:0 0 0 300px;">
        <b><?=$this->lng['preteur-operations-pdf']['paris-le']?> <?=(date('d/m/Y'))?></b>
        <br /><br /><br />
        <?
        if(isset($this->oLendersAccounts->id_company) && $this->oLendersAccounts->id_company != 0){

            $this->companies->get($this->oLendersAccounts->id_company,'id_company');
            ?>
            <b><?=$this->companies->name?></b><br />
            <?=$this->companies->adresse1?><br />
            <?=$this->companies->zip.' '.$this->companies->city?>
            <?
        }
        else{
            ?>
            <b><?=$this->clients->prenom.' '.$this->clients->nom?></b><br />
            <?=$this->clients_adresses->adresse1?><br />
            <?=$this->clients_adresses->cp.' '.$this->clients_adresses->ville?>
            <?
        }
        ?>
    </div>

    <div style="clear:both;"></div>
    <br />
    <b><?=$this->lng['preteur-operations-pdf']['objet-releve-doperations-de-votre-compte-unilend-n']?><?=$this->clients->id_client?></b><br />
    <?=$this->lng['preteur-operations-pdf']['titulaire']?> <?=(isset($this->oLendersAccounts->id_company) && $this->oLendersAccounts->id_company != 0?$this->companies->name:$this->clients->prenom.' '.$this->clients->nom)?><br />
    <?
    if(isset($this->oLendersAccounts->id_company) && $this->oLendersAccounts->id_company != 0){
        ?><?=$this->lng['preteur-operations-pdf']['Representant-legal']?> <?=$this->clients->civilite.' '.$this->clients->prenom.' '.$this->clients->nom?><br /><?
    }
    ?>

</div>
<br /><br />

<table class="table vos_operations" border="0" cellspacing="0" cellpadding="0">
    <tr>
        <th width="200px" id="order_operations" class="col1">
            <div align="left" class="th-wrap" style='top:-3px;width:300px;'>
                <div class="title-ope" style="color: black"><?= $this->lng['preteur-operations-pdf']['operations'] ?>&nbsp;
                </div>
            </div>
        </th>
        <th width="180px" id="order_date">
            <div align="left" class="th-wrap">
                <div class="title-ope" style="color: black"><?= $this->lng['preteur-operations-pdf']['info-titre-loan-id'] ?>&nbsp;
                </div>
            </div>
        </th>
        <th width="180px" id="order_date">
            <div align="left" class="th-wrap">
                <div class="title-ope" style="color: black"><?= $this->lng['preteur-operations-pdf']['info-titre-projets'] ?>&nbsp;
                </div>
            </div>
        </th>
        <th width="180px" id="order_date">
            <div align="left" class="th-wrap">
                <div class="title-ope" style="color: black"><?= $this->lng['preteur-operations-pdf']['date-de-loperation'] ?>&nbsp;
                </div>
            </div>
        </th>
        <th width="180px" id="order_montant">
            <div align="left" class="th-wrap" style="top:-2px;">
                <div class="title-ope" style="color: black"><?= $this->lng['preteur-operations-pdf']['montant-de-loperation'] ?>&nbsp;
                </div>
            </div>
        </th>
        <th width="140px" id="solde">
            <div align="left" class="th-wrap" style="top:-2px;">
                <div class="title-ope" style="color: black"><?= $this->lng['preteur-operations-pdf']['info-titre-solde-compte'] ?>&nbsp;
                </div>
            </div>
        </th>
    </tr>

    <?
    $i=1;
    $asterix_on = false;
    if(isset($this->lTrans)) {
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
            if ($t['type_transaction'] == 5 || $t['type_transaction'] == 23) {

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
                <?
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

                // ajout KLE 03/03/15 , pour un client à a du lui faire un retrait positif car :
                /*

                Dans le fichier BNP Paribas, nous constatons en date du 25/02/2015 un rejet de virement de EUR 350,00 avec le libellé Christophe Voliotis au motif suivant « Compte clos ».

                Rep :
                -	La régularisation devra s’effectuer en date du jour (et non pas en corrigeant la ligne correspondant à la date où avait été demandé ce virement).

                */

                $type = "";
                if ($t['type_transaction'] == 8 && $t['montant'] > 0) {
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
                <?
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
                <?
                $i++;
            }
        }
    }

    $soldetotal = $this->transactions->getSoldeDateLimite($t['id_client'],$this->date_fin);
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
        <th colspan="2" class="pdfSolde"><?=$this->lng['preteur-operations-pdf']['solde-de-votre-compte']?></th>
        <td style="font-size: 17px;font-weight:bold;"><?= str_replace('[#DATE#]', date('d-m-Y',strtotime($this->date_fin)), $this->lng['preteur-operations-pdf']['date-recap']) ?></td>
        <td></td>
        <td style="font-size: 17px;font-weight:bold;"><?= str_replace('[#TOTAL#]', $this->ficelle->formatNumber($soldetotal), $this->lng['preteur-operations-pdf']['solde-recap']) ?></td>
    </tr>
</table>

<?php
if($asterix_on) {
    ?>
    <div style="padding-left: 10px;">* <?=$this->lng['preteur-operations-vos-operations']['offre-acceptee-asterix-pdf']?></div>
    <?
}
?>


<br /><br />
<div class="pdfFooter">

    <?=$this->lng['preteur-operations-pdf']['prestataire-de-services-de-paiement']?><br />
    <?=$this->lng['preteur-operations-pdf']['agent-prestataire-de-services-de-paiement']?><br />


</div>

<script type="text/javascript">
    $("#order_operations,#order_projects,#order_date").click(function() {

        if($(this).attr('id') == 'order_operations'){
            var type = 'order_operations';

            if($("#order_operations.asc").length){ var order = 'desc';}
            else{ var order = 'asc'; }
        }
        else if($(this).attr('id') == 'order_projects'){
            var type = 'order_projects';

            if($("#order_projects.asc").length){ var order = 'desc';}
            else{ var order = 'asc'; }
        }
        else if($(this).attr('id') == 'order_date'){
            var type = 'order_date';

            if($("#order_date.asc").length){ var order = 'desc'; }
            else{ var order = 'asc'; }
        }

        $(".load_table_vos_operations").fadeIn();

        var val = {
            debut 				: $("#debut").val(),
            fin 				: $("#fin").val(),
            nbMois 				: $("#nbMois").val(),
            annee 				: $("#annee").val(),
            tri_type_transac 	: $("#tri_type_transac").val(),
            tri_projects 		: $("#tri_projects").val(),
            id_last_action		: $(this).attr('id'),
            order 				: order,
            type 				: type
        }

        $.post(add_url+"/ajax/vos_operations",val).done(function( data ) {

            $(".content_table_vos_operations").html(data);
            $(".load_table_vos_operations").fadeOut();
        });
    });
</script>