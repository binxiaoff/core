<?php
if (isset($this->bIsPdf) && true === $this->bIsPdf) {
?>
<table class="table vos_operations" border="0" cellspacing="0" cellpadding="0">
    <tr>
        <th width="200px" id="order_operations" class="col1">
            <div align="left" class="th-wrap" style='top:-3px;width:300px;'>
                <?= $this->lng['preteur-operations-pdf']['operations'] ?>&nbsp;
            </div>
        </th>
        <th width="180px" id="order_date">
            <div align="left" class="th-wrap">
                <?= $this->lng['preteur-operations-pdf']['info-titre-loan-id'] ?>&nbsp;
            </div>
        </th>
        <th width="180px" id="order_date">
            <div align="left" class="th-wrap">
                <?= $this->lng['preteur-operations-pdf']['info-titre-projets'] ?>&nbsp;
            </div>
        </th>
        <th width="180px" id="order_date">
            <div align="left" class="th-wrap">
                <?= $this->lng['preteur-operations-pdf']['date-de-loperation'] ?>&nbsp;
            </div>
        </th>
        <th width="180px" id="order_montant">
            <div align="left" class="th-wrap" style="top:-2px;">
                <?= $this->lng['preteur-operations-pdf']['montant-de-loperation'] ?>&nbsp;
            </div>
        </th>
        <th width="140px" id="solde">
            <div align="left" class="th-wrap" style="top:-2px;">
                <?= $this->lng['preteur-operations-pdf']['info-titre-solde-compte'] ?>&nbsp;
            </div>
        </th>
    </tr>
    <?php
    } else {
    ?>
    <table class="table vos_operations transactions-history finances" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <th width="200px" id="order_operations" class="narrow-th <?= ((isset($this->type) && $this->type == 'order_operations') && (isset($this->order) && $this->order == "asc") ? "asc" : "") ?>" style="text-transform: capitalize; font-weight:bold; padding-left: 0px;">
                <div class="th-wrap" style="top:-3px;width: 130px;">
                    <i title="<?= $this->lng['preteur-operations-pdf']['info-titre-operation'] ?>" class="tooltip-anchor icon-double"></i>
                    <div class="title-ope"><?= $this->lng['preteur-operations-pdf']['operations'] ?>&nbsp;<i class="icon-arrows" style="width:15px;"></i>
                    </div>
                </div>
            </th>
            <th width="200px" id="order_bdc" class="narrow-th <?= ((isset($this->type) && $this->type == 'order_bdc') && (isset($this->order) && $this->order == "asc") ? "asc" : "") ?>" style=" font-weight:bold; padding-left: 0px;">
                <div class="th-wrap" style="top:-3px;width: 200px;   text-transform: none;">
                    <i title="<?= $this->lng['preteur-operations-pdf']['info-titre-loan-id'] ?>" class="tooltip-anchor icon-bdc"></i>
                    <div class="title-ope"><?= $this->lng['preteur-operations-pdf']['loan-id'] ?>&nbsp;<i class="icon-arrows" style="width:15px;"></i>
                    </div>
                </div>
            </th>
            <th width="150px" id="order_projects" <?= ((isset($this->type) && $this->type == 'order_projects') && (isset($this->order) && $this->order == "asc") ? 'class="asc"' : "") ?> align="center">
                <div class="th-wrap">
                    <i title="" class="icon-person tooltip-anchor" data-original-title="' . $this->lng['preteur-operations-pdf']['info-titre-projets'] . '"></i>
                    <div class="title-ope"><?= $this->lng['preteur-operations-pdf']['projets'] ?>&nbsp;<i class="icon-arrows" style="width:15px;"></i>
                    </div>
                </div>
            </th>
            <th width="140px" id="order_date" <?= ((isset($this->type) && $this->type == 'order_date') && (isset($this->order) && $this->order == "asc") ? 'class="asc"' : "") ?>>
                <div class="th-wrap">
                    <i title="" class="icon-calendar tooltip-anchor" data-original-title="' . $this->lng['preteur-operations-pdf']['info-titre-date-operation'] . '"></i>
                    <div class="title-ope"><?= $this->lng['preteur-operations-pdf']['date-de-loperation'] ?>&nbsp;<i class="icon-arrows" style="width:15px;"></i>
                    </div>
                </div>
            </th>
            </th>
            <th width="180px" id="order_montant" <?= ((isset($this->type) && $this->type == 'order_montant') && (isset($this->order) && $this->order == "asc") ? 'class="asc"' : "") ?>>
                <div class="th-wrap" style="top:-2px;">
                    <i title="" class="icon-euro tooltip-anchor" data-original-title="<?= $this->lng['preteur-operations-pdf']['info-titre-montant-operation'] ?>"></i>
                    <div class="title-ope"><?= $this->lng['preteur-operations-pdf']['montant-de-loperation'] ?>&nbsp;<i class="icon-arrows" style="width:15px;"></i>
                    </div>
                </div>
            </th>
            <th width="140px">
                <div class="th-wrap">
                    <i title="" class="icon-bank tooltip-anchor" data-original-title="<?= $this->lng['preteur-operations-pdf']['info-titre-solde-compte'] ?>"></i>
                    <div class="title-ope"><?= $this->lng['preteur-operations-pdf']['solde-du-compte'] ?></div>
                </div>
            </th>
        </tr>
        <?php
        }
        $iRow       = 1;
        $asterix_on = false;
        foreach ($this->lTrans as $t) {
            $t['solde']               = ($t['solde'] / 100);
            $t['montant_prelevement'] = ($t['montant_prelevement'] / 100);

            if ($t['montant_operation'] > 0) {
                $couleur = 'style="color:#40b34f;"';
            } else {
                $couleur = 'style="color:red;"';
            }

            if ($t['solde'] > 0) {
                $solde = $t['solde'];
            }
            if (in_array($t['type_transaction'], array(\transactions_types::TYPE_LENDER_REPAYMENT, \transactions_types::TYPE_LENDER_ANTICIPATED_REPAYMENT, \transactions_types::TYPE_LENDER_RECOVERY_REPAYMENT))) {
                ?>
                <tr class="transact remb_<?= $t['id_transaction'] ?> <?= ($iRow % 2 == 1 ? '' : 'odd') ?>">
                    <td><?= $t['libelle_operation'] ?>
                        <?php if (\transactions_types::TYPE_LENDER_RECOVERY_REPAYMENT != $t['type_transaction'] && false === isset($this->bIsPdf)): ?>
                            <span class="plusmoinsOperations"></span>
                        <?php endif; ?>
                    </td>
                    <td><?= true === empty($t['bdc']) ? '' : $t['bdc']; ?></td>
                    <td class="companieleft"><?= $t['libelle_projet'] ?></td>
                    <td><?= $this->dates->formatDate($t['date_operation'], 'd-m-Y') ?></td>
                    <td <?= $couleur ?>><?= $this->ficelle->formatNumber($t['montant_operation'] / 100) ?> â‚¬</td>
                    <td><?= $this->ficelle->formatNumber($t['solde']) ?> â‚¬</td>
                </tr>
                <tr class="content_transact <?= ($iRow % 2 == 1 ? '' : 'odd') ?>" height="0">
                    <td colspan="7">
                        <?php if (\transactions_types::TYPE_LENDER_RECOVERY_REPAYMENT != $t['type_transaction']): ?>
                            <div class="div_content_transact content_remb_<?= $t['id_transaction'] ?>" <?php if (false === isset($this->bIsPdf)): ?>style="display:none;"<?php endif; ?>>
                                <table class="soustable" width="100%">
                                    <tbody>
                                    <tr>
                                        <td width="146px" class="detail_remb"><?= $this->lng['preteur-operations-vos-operations']['voici-le-detail-de-votre-remboursement'] ?></td>
                                        <td width="145px" class="detail_left"><?= $this->lng['preteur-operations-vos-operations']['capital-rembourse'] ?></td>
                                        <td width="100px" class="chiffres" style="padding-bottom:8px; color:#40b34f;"><?= $this->ficelle->formatNumber(($t['montant_capital'] / 100)) ?> â‚¬</td>
                                        <td width="101px">&nbsp;</td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td class="detail_left"><?= $this->lng['preteur-operations-vos-operations']['interets-recus'] ?></td>
                                        <td class="chiffres" style="color:#40b34f;"><?= $this->ficelle->formatNumber(($t['montant_interet'] / 100)) ?> â‚¬</td>
                                        <td>&nbsp;</td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td class="detail_left"><?= $t['libelle_prelevement'] ?></td>
                                        <td class="chiffres" style="color:red;">-<?= $this->ficelle->formatNumber($t['montant_prelevement']) ?> â‚¬</td>
                                        <td>&nbsp;</td>
                                    </tr>
                                    <?php if ($t['recouvrement'] == 1): ?>
                                        <tr>
                                            <td></td>
                                            <td class="detail_left"><?= $this->lng['preteur-operations-vos-operations']['com-ht'] ?></td>
                                            <td class="chiffres" style="color:red;">-<?= $this->ficelle->formatNumber($t['commission_ht'] / 100) ?> â‚¬</td>
                                            <td>&nbsp;</td>
                                        </tr>
                                        <tr>
                                            <td></td>
                                            <td class="detail_left"><?= $this->lng['preteur-operations-vos-operations']['com-tva'] ?></td>
                                            <td class="chiffres" style="color:red;">-<?= $this->ficelle->formatNumber($t['commission_tva'] / 100) ?> â‚¬</td>
                                            <td>&nbsp;</td>
                                        </tr>
                                        <tr>
                                            <td></td>
                                            <td class="detail_left"><?= $this->lng['preteur-operations-vos-operations']['com-ttc'] ?></td>
                                            <td class="chiffres" style="color:red;">-<?= $this->ficelle->formatNumber($t['commission_ttc']) ?> â‚¬</td>
                                            <td>&nbsp;</td>
                                        </tr>
                                    <?php endif; ?>
                                    <tr>
                                        <td colspan="4" style=" height:4px;"></td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                        <script type="text/javascript">
                            $(".remb_<?=$t['id_transaction']?>").click(function () {
                                $(".content_remb_<?=$t['id_transaction']?>").slideToggle();
                                if ($(".remb_<?=$t['id_transaction']?>").hasClass("on_display")) {
                                    $(".remb_<?=$t['id_transaction']?>").find('span').addClass('plus');
                                    $(".remb_<?=$t['id_transaction']?>").find('span').removeClass('moins');

                                    $(".remb_<?=$t['id_transaction']?>").addClass("off_display");
                                    $(".remb_<?=$t['id_transaction']?>").removeClass('on_display');
                                }
                                else {
                                    $(".remb_<?=$t['id_transaction']?>").find('span').addClass('moins');
                                    $(".remb_<?=$t['id_transaction']?>").find('span').removeClass('plus');

                                    $(".remb_<?=$t['id_transaction']?>").addClass("on_display");
                                    $(".remb_<?=$t['id_transaction']?>").removeClass('off_display');
                                }
                            });
                        </script>
                    </td>
                </tr>
                <?php
                $iRow++;
            } elseif (in_array($t['type_transaction'], array(8, 1, 3, 4, 16, 17, 19, 20))) {
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
                <tr <?= ($iRow % 2 == 1 ? '' : 'class="odd"') ?>>
                    <td><?= $type ?></td>
                    <td></td>
                    <td></td>
                    <td><?= $this->dates->formatDate($t['date_operation'], 'd-m-Y') ?></td>
                    <td <?= $couleur ?>><?= $this->ficelle->formatNumber($t['montant_operation'] / 100) ?> â‚¬</td>
                    <td><?= $this->ficelle->formatNumber($t['solde']) ?> â‚¬</td>
                </tr>
                <?php
                $iRow++;
            } elseif (in_array($t['type_transaction'], array(2))) {
                $asterix       = "";
                $offre_accepte = false;
                if ($t['libelle_operation'] == $this->lng['preteur-operations-vos-operations']['offre-acceptee']) {
                    $asterix       = " *";
                    $offre_accepte = true;
                    $asterix_on    = true;
                }
                ?>
                <tr <?= ($iRow % 2 == 1 ? '' : 'class="odd"') ?>>
                    <td><?= $t['libelle_operation'] ?></td>
                    <td><?= empty($t['bdc']) ? '' : $t['bdc'] ?></td>
                    <td class="companieleft"><?= $t['libelle_projet'] ?></td>
                    <td><?= $this->dates->formatDate($t['date_operation'], 'd-m-Y') ?></td>
                    <td <?= (false == $offre_accepte ? $couleur : '') ?>><?= $this->ficelle->formatNumber($t['montant_operation'] / 100) . ' â‚¬' ?></td>
                    <td><?= $this->ficelle->formatNumber($t['solde']) ?> â‚¬<?= $asterix ?></td>
                </tr>
                <?php
                $iRow++;
            }
        }
        if (isset($this->bIsPdf) && true === $this->bIsPdf) {
            $soldetotal = $this->transactions->getSoldeDateLimite($t['id_client'], $this->date_fin);
            ?>
            <tr>
                <td colspan="7"></td>
            </tr>
            <tr>
                <td colspan="7"></td>
            </tr>
            <tr>
                <td colspan="7"></td>
            </tr>
            <tr>
                <th colspan="3" class="pdfSolde"><?= $this->lng['preteur-operations-pdf']['solde-de-votre-compte'] ?></th>
                <th style="font-size: 17px;font-weight:bold;"><?= str_replace('[#DATE#]', date('d-m-Y', strtotime($this->date_fin)), $this->lng['preteur-operations-pdf']['date-recap']) ?></th>
                <th></th>
                <th style="font-size: 17px;font-weight:bold;"><?= str_replace('[#TOTAL#]', $this->ficelle->formatNumber($soldetotal), $this->lng['preteur-operations-pdf']['solde-recap']) ?></th>
            </tr>
        <?php
        } else {
        ?>
            <script type="text/javascript">
                $(".tooltip-anchor").tooltip();

                $("#order_operations,#order_projects,#order_date,#order_montant, #order_bdc").click(function () {

                    if ($(this).attr('id') == 'order_operations') {
                        var type = 'order_operations';

                        if ($("#order_operations.asc").length) {
                            var order = 'desc';
                        }
                        else {
                            var order = 'asc';
                        }
                    }
                    else if ($(this).attr('id') == 'order_projects') {
                        var type = 'order_projects';

                        if ($("#order_projects.asc").length) {
                            var order = 'desc';
                        }
                        else {
                            var order = 'asc';
                        }
                    }
                    else if ($(this).attr('id') == 'order_date') {
                        var type = 'order_date';

                        if ($("#order_date.asc").length) {
                            var order = 'desc';
                        }
                        else {
                            var order = 'asc';
                        }
                    }
                    else if ($(this).attr('id') == 'order_montant') {
                        var type = 'order_montant';

                        if ($("#order_montant.asc").length) {
                            var order = 'desc';
                        }
                        else {
                            var order = 'asc';
                        }
                    }
                    else if ($(this).attr('id') == 'order_bdc') {
                        var type = 'order_bdc';

                        if ($("#order_bdc.asc").length) {
                            var order = 'desc';
                        }
                        else {
                            var order = 'asc';
                        }
                    }

                    $(".load_table_vos_operations").fadeIn();

                    var val = {
                        debut: $("#debut").val(),
                        fin: $("#fin").val(),
                        nbMois: $("#nbMois").val(),
                        annee: $("#annee").val(),
                        tri_type_transac: $("#tri_type_transac").val(),
                        tri_projects: $("#tri_projects").val(),
                        id_last_action: $(this).attr('id'),
                        order: order,
                        type: type
                    };

                    $.post(add_url + "/ajax/vos_operations", val).done(function (data) {

                        var obj = jQuery.parseJSON(data);

                        $("#debut").val(obj.debut);
                        $("#fin").val(obj.fin);

                        $(".content_table_vos_operations").html(obj.html);
                        $(".load_table_vos_operations").fadeOut();
                    });
                });
            </script>
            <?php
        }
        ?>
    </table>
    <?php
    if ($asterix_on) {
        ?>
        <div>* <?= $this->lng['preteur-operations-vos-operations']['offre-acceptee-asterix'] ?></div>
        <?php
    }
    ?>