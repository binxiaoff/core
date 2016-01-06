<?php

$html_filtre = '
    <b>' . $this->lng['preteur-operations']['selectionnez-un-ou-plusieurs-filtres'] . '</b><br /><br />
    <div class="vos_operations_ligne">
        <select name="tri_type_transac" id="tri_type_transac" class="custom-select field-mini" width="200px;">
            <option value="1">' . $this->lng['preteur-operations']['tri-operation'] . '</option>
            <option value="1">' . $this->lng['preteur-operations']['tri-toutes'] . '</option>
            <option value="2">' . $this->lng['preteur-operations']['tri-apports-retraits'] . '</option>
            <option value="3">' . $this->lng['preteur-operations']['tri-apports'] . '</option>
            <option value="4">' . $this->lng['preteur-operations']['tri-retraits'] . '</option>
            <option value="5">' . $this->lng['preteur-operations']['tri-prets'] . '</option>
            <option value="6">' . $this->lng['preteur-operations']['tri-remboursement'] . '</option>
        </select>
    </div>
    &nbsp;&nbsp;&nbsp;
    <div class="vos_operations_ligne">
        <select name="tri_projects" id="tri_projects" class="custom-select field-mini">
            <option value="0">' . $this->lng['preteur-operations']['trier-projet'] . '</option>
            <option value="1">' . $this->lng['preteur-operations']['trier-tous'] . '</option>';

foreach ($this->lProjectsLoans as $pro) {
    $html_filtre .= '<option value="' . $pro['id_project'] . '">' . $pro['title'] . '</option>';
}

$html_filtre .= '
        </select>
    </div>

    <script type="text/javascript">
        $("input,select").change(function() {
            $(".c2-sb-wrap").removeClass("populated");
            $(".load_table_vos_operations").fadeIn();

            var val = {
                debut:            $("#debut").val(),
                fin:              $("#fin").val(),
                nbMois:           $("#nbMois").val(),
                annee:            $("#annee").val(),
                tri_type_transac: $("#tri_type_transac").val(),
                tri_projects:     $("#tri_projects").val(),
                id_last_action:   $(this).attr("id")
            };

            $.post(add_url+"/ajax/vos_operations",val).done(function( data ) {
                var obj = jQuery.parseJSON(data);

                $("#debut").val(obj.debut);
                $("#fin").val(obj.fin);
                $(".content_table_vos_operations").html(obj.html);
                $("#filtres_secondaires").html(obj.html_filtre);
                $(".custom-select").c2Selectbox();
                $(".load_table_vos_operations").fadeOut();
            });
        });
    </script>
';

$html = '<table class="table vos_operations transactions-history finances" border="0" cellspacing="0" cellpadding="0">
    <tr>
        <th width="200px"  id="order_operations" class="narrow-th ' . ((isset($this->type) && $this->type == 'order_operations') && (isset($this->order) && $this->order == "asc") ? "asc" : "") . '" style="text-transform: capitalize; font-weight:bold; padding-left: 0px;">
            <div class="th-wrap" style="top:-3px;width: 130px;">
                <i title="' . $this->lng['preteur-operations-pdf']['info-titre-operation'] . '" class="tooltip-anchor icon-double"></i>
                <div class="title-ope">' . $this->lng['preteur-operations-pdf']['operations'] . '&nbsp;<i class="icon-arrows" style="width:15px;"></i>
                </div>
            </div>
        </th>
        <th width="200px"  id="order_bdc" class="narrow-th ' . ((isset($this->type) && $this->type == 'order_bdc') && (isset($this->order) && $this->order == "asc") ? "asc" : "") . '" style=" font-weight:bold; padding-left: 0px;">
            <div class="th-wrap" style="top:-3px;width: 200px;   text-transform: none;">
                <i title="' . $this->lng['preteur-operations-pdf']['info-titre-loan-id'] . '" class="tooltip-anchor icon-bdc"></i>
                <div class="title-ope">' . $this->lng['preteur-operations-pdf']['loan-id'] . '&nbsp;<i class="icon-arrows" style="width:15px;"></i>
                </div>
            </div>
        </th>
        <th width="150px" id="order_projects" ' . ((isset($this->type) && $this->type == 'order_projects') && (isset($this->order) && $this->order == "asc") ? 'class="asc"' : "") . ' align="center">
            <div class="th-wrap">
                <i title="" class="icon-person tooltip-anchor" data-original-title="' . $this->lng['preteur-operations-pdf']['info-titre-projets'] . '"></i>
                <div class="title-ope">' . $this->lng['preteur-operations-pdf']['projets'] . '&nbsp;<i class="icon-arrows" style="width:15px;"></i></div>
            </div>
        </th>
        <th width="140px" id="order_date" ' . ((isset($this->type) && $this->type == 'order_date') && (isset($this->order) && $this->order == "asc") ? 'class="asc"' : "") . '>
            <div class="th-wrap">
                <i title="" class="icon-calendar tooltip-anchor" data-original-title="' . $this->lng['preteur-operations-pdf']['info-titre-date-operation'] . '"></i>
                <div class="title-ope">' . $this->lng['preteur-operations-pdf']['date-de-loperation'] . '&nbsp;<i class="icon-arrows" style="width:15px;"></i></div>
            </div>
        </th>
        </th>
        <th width="180px" id="order_montant" ' . ((isset($this->type) && $this->type == 'order_montant') && (isset($this->order) && $this->order == "asc") ? 'class="asc"' : "") . '>
             <div class="th-wrap" style="top:-2px;">
                <i title="" class="icon-euro tooltip-anchor" data-original-title="' . $this->lng['preteur-operations-pdf']['info-titre-montant-operation'] . '"></i>
                <div class="title-ope">' . $this->lng['preteur-operations-pdf']['montant-de-loperation'] . '&nbsp;<i class="icon-arrows" style="width:15px;"></i></div>
            </div>
        </th>
        <th width="140px">
            <div class="th-wrap">
                <i title="" class="icon-bank tooltip-anchor" data-original-title="' . $this->lng['preteur-operations-pdf']['info-titre-solde-compte'] . '"></i>
                <div class="title-ope">' . $this->lng['preteur-operations-pdf']['solde-du-compte'] . '</div>
            </div>
        </th>
    </tr>';

$i          = 1;
$asterix_on = false;
foreach ($this->lTrans as $t) {
    if ($t['montant_operation'] > 0) {
        $plus    = '<b style="color:#40b34f;">+</b>';
        $moins   = '';
        $couleur = 'style="color:#40b34f;"';
    } else {
        $plus    = '';
        $moins   = '<b style="color:red;">-</b>';
        $couleur = 'style="color:red;"';
    }

    $t['solde']               = $t['solde'] / 100;
    $t['montant_prelevement'] = ($t['montant_prelevement'] / 100);
    if ($t['solde'] > 0) {
        $solde = $t['solde'];
    }

    // Remb preteur
    if ($t['type_transaction'] == 5 || $t['type_transaction'] == 23) {
        // Récupération de la traduction et non plus du libelle dans l'indexation (si changement on est ko)
        $html .= '
            <!-- debut transasction remb -->
            <tr class="transact remb_' . $t['id_transaction'] . ' ' . ($i % 2 == 1 ? '' : 'odd') . '">
                <td>' . $t['libelle_operation'] . '<span class="plusmoinsOperations"></span></td>
                <td>' . $t['bdc'] . '</td>
                <td class="companieleft">' . $t['libelle_projet'] . '</td>
                <td>' . $this->dates->formatDate($t['date_operation'], 'd-m-Y') . '</td>

                <td ' . $couleur . '>' . $this->ficelle->formatNumber($t['montant_operation'] / 100) . ' €</td>
                <td>' . $this->ficelle->formatNumber($t['solde']) . ' €</td>
            </tr>
            <tr class="content_transact ' . ($i % 2 == 1 ? '' : 'odd') . '" height="0">
                <td colspan="7">
                    <div class="div_content_transact content_remb_' . $t['id_transaction'] . '" style="display:none;">
                    <table class="soustable" width="100%">
                        <tbody>
                            <tr>
                                <td width="138px" class="detail_remb">' . $this->lng['preteur-operations-vos-operations']['voici-le-detail-de-votre-remboursement'] . '</td>
                                <td width="115px" class="detail_left">' . $this->lng['preteur-operations-vos-operations']['capital-rembourse'] . '</td>
                                <td width="99px" class="chiffres" style="padding-bottom:8px; color:#40b34f;">' . $this->ficelle->formatNumber(($t['montant_capital'] / 100)) . ' €</td>
                                <td width="101px">&nbsp;</td>
                            </tr>
                            <tr>
                                <td></td>
                                <td class="detail_left">' . $this->lng['preteur-operations-vos-operations']['interets-recus'] . '</td>
                                <td class="chiffres" style="color:#40b34f;">' . $this->ficelle->formatNumber(($t['montant_interet'] / 100)) . ' €</td>
                                <td>&nbsp;</td>
                            </tr>
                            <tr>
                                <td></td>
                                <td class="detail_left">' . $t['libelle_prelevement'] . '</td>
                                <td class="chiffres" style="color:red;">-' . $this->ficelle->formatNumber($t['montant_prelevement']) . ' €</td>
                                <td>&nbsp;</td>
                            </tr>
                            <tr>
                                <td colspan="4" style=" height:4px;"></td>
                            </tr>';

        if ($t['recouvrement'] == 1) {
            $html .= '
                            <tr>
                                <td></td>
                                <td class="detail_left">' . $this->lng['preteur-operations-vos-operations']['com-ht'] . '</td>
                                <td class="chiffres" style="color:red;">-' . $this->ficelle->formatNumber($t['commission_ht'] / 100) . ' €</td>
                                <td>&nbsp;</td>
                            </tr>
                            <tr>
                                <td></td>
                                <td class="detail_left">' . $this->lng['preteur-operations-vos-operations']['com-tva'] . '</td>
                                <td class="chiffres" style="color:red;">-' . $this->ficelle->formatNumber($t['commission_tva'] / 100) . ' €</td>
                                <td>&nbsp;</td>
                            </tr>
                            <tr>
                                <td></td>
                                <td class="detail_left">' . $this->lng['preteur-operations-vos-operations']['com-ttc'] . '</td>
                                <td class="chiffres" style="color:red;">-' . $this->ficelle->formatNumber($t['commission_ttc']) . ' €</td>
                                <td>&nbsp;</td>
                            </tr>';
        }

        $html .= '
                        </tbody>
                    </table>
                    </div>
                </td>
            </tr>
            <script type="text/javascript">

                $(".remb_' . $t['id_transaction'] . '").click(function() {
                    $(".content_remb_' . $t['id_transaction'] . '").slideToggle();

                    if ($(".remb_' . $t['id_transaction'] . '").hasClass("on_display")) {
                        $(".remb_' . $t['id_transaction'] . '").find("span").addClass("plus");
                        $(".remb_' . $t['id_transaction'] . '").find("span").removeClass("moins");
                        $(".remb_' . $t['id_transaction'] . '").addClass("off_display");
                        $(".remb_' . $t['id_transaction'] . '").removeClass("on_display");
                    } else {
                        $(".remb_' . $t['id_transaction'] . '").find("span").addClass("moins");
                        $(".remb_' . $t['id_transaction'] . '").find("span").removeClass("plus");
                        $(".remb_' . $t['id_transaction'] . '").addClass("on_display");
                        $(".remb_' . $t['id_transaction'] . '").removeClass("off_display");
                    }
                });

            </script>
            <!-- fin transasction remb -->
        ';
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
        -    La régularisation devra s’effectuer en date du jour (et non pas en corrigeant la ligne correspondant à la date où avait été demandé ce virement).

        */

        $type = "";
        if ($t['type_transaction'] == 8 && $t['montant_operation'] > 0) {
            $type = "Annulation retrait des fonds - compte bancaire clos";
        } else {
            $type = $t['libelle_operation'];
        }

        $html .= '
            <tr ' . ($i % 2 == 1 ? '' : 'class="odd"') . '>
                <td>' . $type . '</td>
                <td></td>
                <td></td>
                <td>' . $this->dates->formatDate($t['date_operation'], 'd-m-Y') . '</td>
                <td ' . $couleur . '>' . $this->ficelle->formatNumber($t['montant_operation'] / 100) . ' €</td>
                <td>' . $this->ficelle->formatNumber($t['solde']) . ' €</td>
            </tr>
            ';
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

        $html .= '
            <tr ' . ($i % 2 == 1 ? '' : 'class="odd"') . '>
                <td>' . $t['libelle_operation'] . '</td>
                <td>' . $bdc . '</td>
                <td class="companieleft">' . $t['libelle_projet'] . '</td>
                <td>' . $this->dates->formatDate($t['date_operation'], 'd-m-Y') . '</td>
                <td ' . (! $offre_accepte ? $couleur : '') . '>' . $this->ficelle->formatNumber($t['montant_operation'] / 100) . ' €</td>
                <td>' . $this->ficelle->formatNumber($t['solde']) . ' €' . $asterix . '</td>
            </tr>
            ';
        $i++;
    }
}
$html .= '
</table>';

if ($asterix_on) {
    $html .= '<div >* ' . $this->lng['preteur-operations-vos-operations']['offre-acceptee-asterix'] . '</div>';
}

$html .= '
 <script type="text/javascript">
     $(".tooltip-anchor").tooltip();

    $("#order_operations,#order_projects,#order_date,#order_montant, #order_bdc").click(function() {

        if($(this).attr("id") == "order_operations"){
            var type = "order_operations";

            if($("#order_operations.asc").length){ var order = "desc";}
            else{ var order = "asc"; }
        }
        else if($(this).attr("id") == "order_projects"){
            var type = "order_projects";

            if($("#order_projects.asc").length){ var order = "desc";}
            else{ var order = "asc"; }
        }
        else if($(this).attr("id") == "order_date"){
            var type = "order_date";

            if($("#order_date.asc").length){ var order = "desc"; }
            else{ var order = "asc"; }
        }
        else if($(this).attr("id") == "order_montant"){
            var type = "order_montant";

            if($("#order_montant.asc").length){ var order = "desc"; }
            else{ var order = "asc"; }
        }
        else if($(this).attr("id") == "order_bdc"){
            var type = "order_bdc";

            if($("#order_bdc.asc").length){ var order = "desc"; }
            else{ var order = "asc"; }
        }

        $(".load_table_vos_operations").fadeIn();

        var val = {
            debut:            $("#debut").val(),
            fin:              $("#fin").val(),
            nbMois:           $("#nbMois").val(),
            annee:            $("#annee").val(),
            tri_type_transac: $("#tri_type_transac").val(),
            tri_projects:     $("#tri_projects").val(),
            id_last_action:   $(this).attr("id"),
            order:            order,
            type:             type
        };

        $.post(add_url+"/ajax/vos_operations",val).done(function( data ) {
            var obj = jQuery.parseJSON(data);

            $("#debut").val(obj.debut);
            $("#fin").val(obj.fin);
            $(".content_table_vos_operations").html(obj.html);
            $("#filtres_secondaires").html(obj.html_filtre);
            $(".custom-select").c2Selectbox();
            $(".load_table_vos_operations").fadeOut();
        });
    });
    </script>';

echo json_encode(array('html' => $html, 'html_filtre' => $html_filtre, 'debut' => $this->date_debut_display, 'fin' => $this->date_fin_display));
