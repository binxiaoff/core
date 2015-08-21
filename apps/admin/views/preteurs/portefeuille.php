<script type="text/javascript">


    $(document).ready(function () {

        <!--partie copié de la vue "edit"-->
        jQuery.tablesorter.addParser({
            id: "fancyNumber", is: function (s) {
                return /[\-\+]?\s*[0-9]{1,3}(\.[0-9]{3})*,[0-9]+/.test(s);
            }, format: function (s) {
                return jQuery.tablesorter.formatFloat(s.replace(/,/g, '').replace(' €', '').replace(' ', ''));
            }, type: "numeric"
        });

        $(".encheres").tablesorter({headers: {6: {sorter: false}}});
        $(".mandats").tablesorter({headers: {}});
        $(".bidsEncours").tablesorter({headers: {6: {sorter: false}}});
        $(".transac").tablesorter({headers: {}});
        $(".favoris").tablesorter({headers: {3: {sorter: false}}});
        <?
        if($this->nb_lignes != '')
        {
        ?>
        $(".encheres").tablesorterPager({container: $("#pager"), positionFixed: false, size: <?=$this->nb_lignes?>});
        $(".mandats").tablesorterPager({container: $("#pager"), positionFixed: false, size: <?=$this->nb_lignes?>});
        <?
        }
        ?>
        $("#annee").change(function () {
            $('#changeDate').attr('href', "<?=$this->lurl?>/preteurs/edit/<?=$this->params[0]?>/" + $(this).val());
        });

    });
    //partie qui vient de la vue "vos_operations"
    $.datepicker.setDefaults($.extend({showMonthAfterYear: false}, $.datepicker.regional['fr']));

    $("#debut").datepicker({
        changeMonth: true,
        changeYear: true,
        yearRange: '<?=(date('Y')-40)?>:<?=(date('Y'))?>',
        maxDate: '<?=$this->date_fin_display?>',
        onClose: function (selectedDate) {
            $("#fin").datepicker("option", "minDate", selectedDate);
        }
    });
    $("#fin").datepicker({
        changeMonth: true,
        changeYear: true,
        yearRange: '<?=(date('Y')-40)?>:<?=(date('Y'))?>',
        minDate: '<?=$this->date_debut_display?>',
        onClose: function (selectedDate) {
            $("#debut").datepicker("option", "maxDate", selectedDate);
        }
    });

    })
    ;

    <!--partie copié de la vue "edit"-->
    <?
if(isset($_SESSION['freeow']))
{
    ?>
    $(document).ready(function () {
        var title, message, opts, container;
        title = "<?=$_SESSION['freeow']['title']?>";
        message = "<?=$_SESSION['freeow']['message']?>";
        opts = {};
        opts.classes = ['smokey'];
        $('#freeow-tr').freeow(title, message, opts);
    });
    <?
    unset($_SESSION['freeow']);
}
?>
</script>
<!--vient de "vos_operations"-->
<style type="text/css">
    .iconplusmoins {
        color: white;
        font-size: 18px;
        cursor: default;
        vertical-align: middle;
    }

    .vos_operations {
        font-size: 13px;
    }

    .vos_operations td {
        padding: 5px;
    }

    .vos_operations tr.content_transact td {
        height: 0px;
        padding: 0px;
    }

    table.vos_operations th#order_projects {
        text-align: left;
    }

    .vos_operations tr.transact {
        cursor: pointer;
    }

    .vos_operations tr.content_transact .div_content_transact {
        display: inline-block;
        width: 100%;
    }

    .vos_operations tr.content_transact td .soustable {
        border-bottom: 1px solid #b10366;
        margin-top: 10px;
        padding-bottom: 10px;
    }

    .vos_operations tr.content_transact td .soustable tr td {
        padding: 0px;
        height: 15px;
    }

    .vos_operations tr.content_transact td .soustable tr td.chiffres {
        text-align: right;
    }

    .vos_operations tr.content_transact td .soustable tr td.detail_remb {
        padding-left: 5px;
        vertical-align: top;
    }

    .vos_operations tr:nth-child(even) td {
        background-color: white;
    }

    .vos_operations tr:hover td {
        background-color: white;
    }

    .vos_operations tr.odd td {
        background: #fafafa;
    }

    .vos_operations .icon-arrows {
        cursor: pointer;
    }

    .vos_operations .companieleft {
        text-align: left;
    }

    .soustable .detail_left {
        text-align: left;
    }

    .vos_operations_ligne {
        display: inline-block;
        vertical-align: top;
    }

    /*.vos_operations .print{margin-top: 3px;}
    .vos_operations .xls{margin-top: 6px;}*/

    .vos_operations .print {
        margin-top: 8px;
        width: 50px;
    }

    .vos_operations .xls {
        margin-top: 6px;
        width: 50px;
    }

    .load_table_vos_operations {
        background: none repeat scroll 0 0 white;
        border: 1px solid #b10366;
        border-radius: 5px;
        display: none;
        height: 50px;
        left: 48%;
        margin: 65px auto auto;
        padding: 5px;
        position: absolute;
        text-align: center;
        width: 100px;
    }

    .th-wrap {
        color: white;
    }

    .table-filter .period {
        float: left;
        border: 1px solid;
        padding: 10px;
        border-radius: 3px;
        border-color: #8D8D8D;
    }

    .table-filter .period tr td {
        vertical-align: bottom;
        line-height: 30px;
    }

    .table-filter .period tr td .ou {
        text-align: center;
        width: 80px;
    }

    .table-filter .period tr td .au {
        text-align: center;
        width: 40px;
    }

    .c2-sb-wrap {
        z-index: 1;
    }

    .table-filter .period .c2-sb-wrap {
        z-index: 10;
    }

    .populated .c2-sb-text,
    .populated {
        color: #b20066 !important;
    }

    .table-filter .export {
        float: right;
    }

    .table-filter .filtre {
        float: left;
        border: 1px solid;
        padding: 15px;
        margin-top: 15px;
        border-radius: 3px;
        border-color: #8D8D8D;
        width: 420px;
    }

    .vos_operations .th-wrap {
        text-align: center;
        /*width: 100px;*/
        font-size: 12px;
    }

    .filtre .c2-sb-wrap {
        width: 200px;
    }

    .filtre .c2-sb-text {
        width: 140px !important;
    }

    .soustable tr td {
        padding-top: 5px !important;
        padding-bottom: 5px !important;
    }

    .title-ope {
        margin-top: 12.5px;
    }

</style>


<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
    <ul class="breadcrumbs">
        <li><a href="<?= $this->lurl ?>/preteurs" title="Prêteurs">Prêteurs</a> -</li>
        <li><a href="<?= $this->lurl ?>/preteurs/gestion" title="Gestion prêteurs">Gestion prêteurs</a> -</li>
        <li><a href="<?= $this->lurl ?>/preteurs/gestion" title="Gestion prêteurs">Detail prêteur</a> -</li>
        <li>Portefeuille & Performances</li>
    </ul>

    <?
    // a controler
    if ($this->clients_status->status == 10) {
        ?>
        <div class="attention">
            Attention : compte non validé - créé le <?= date('d/m/Y', $this->timeCreate) ?>
        </div>
        <?
    } // completude
    elseif (in_array($this->clients_status->status, array(20, 30, 40))) {
        ?>
        <div class="attention" style="background-color:#F9B137">
            Attention : compte en complétude - créé le <?= date('d/m/Y', $this->timeCreate) ?>
        </div>
        <?
    } // modification
    elseif (in_array($this->clients_status->status, array(50))) {
        ?>
        <div class="attention" style="background-color:#F2F258">
            Attention : compte en modification - créé le <?= date('d/m/Y', $this->timeCreate) ?>
        </div>
        <?
    }
    ?>

    <!--    section "detail prêteur"    -->
    <h1>Detail prêteur : <?= $this->clients->prenom . ' ' . $this->clients->nom ?></h1>

    <div class="btnDroite">
        <a
            href="<?= $this->lurl ?>/preteurs/edit/<?= $this->lenders_accounts->id_lender_account ?>"
            class="btn_link">Consulter Prêteur</a>
        <a
            href="<?= $this->lurl ?>/preteurs/edit_preteur/<?= $this->lenders_accounts->id_lender_account ?>"
            class="btn_link">Toutes les infos</a>
        <a href="<?= $this->lurl ?>/preteurs/email_history/<?= $this->lenders_accounts->id_lender_account ?>"
           class="btn_link">Historique des emails</a>
    </div>
        <br>

<!--Table pour choisire la periode d'affichage-->
    <div class="table-filter clearfix">
        <div class="period">
            <b>Selectionnez une periode</b>
            <table>
                <tr>
                    <td>Mensuelle<br>
                        <div class="vos_operations_ligne div_mois">
                            <select name="nbMois" id="nbMois" class="custom-select field-hundred">
                                <option value="1">Selectionnez</option>
                                <option value="1">1 Mois</option>
                                <option value="3">3 Mois</option>
                                <option value="6">6 Mois</option>
                                <option value="12">12 Mois</option>
                            </select>
                        </div>
                    </td>
                    <td>
                        <div class="ou">ou</div>
                    </td>
                    <td>
                        Anuelle<br/>
                        <div class="vos_operations_ligne div_annee">
                            <select name="annee" id="annee" class="custom-select field-hundred">
                                <option value="<?= date('Y') ?>"><?= date('Y') ?></option>
                                <?
                                for ($i = date('Y'); $i >= 2013; $i--) {
                                    ?>
                                    <option value="<?= $i ?>"><?= $i ?></option><?
                                }
                                ?>
                            </select>
                        </div>
                    </td>
                    <td>
                        <div class="ou">ou</div>
                    </td>
                    <td>
                        Personalisée<br/>
                        <div class="vos_operations_ligne div_debut">
                            <input type="text" id="debut" name="debut" title="debut"
                                   value="<?= $this->date_debut_display ?>" class="field" style="width:72px;">
                        </div>
                    </td>
                    <td>
                        <div class="au"><?= $this->lng['preteur-operations']['sous-titre-operation-au'] ?></div>
                    </td>
                    <td>
                        <div class="vos_operations_ligne div_fin">
                            <input type="text" id="fin" name="fin" title="fin" value="<?= $this->date_fin_display ?>"
                                   class="field" style="width:72px;">
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div style="clear:both;"></div>
        <div class="filtre" id="filtres_secondaires">
            <b>selectionnez-un-ou-plusieurs-filtres</b><br/><br/>

            <div class="vos_operations_ligne">
                <select name="tri_type_transac" id="tri_type_transac" class="custom-select field-mini" width="200px;">
                    <option value="1">Selectionnez</option>
                    <option value="1">toutes</option>
                    <option value="2">apports-retraits</option>
                    <option value="3">apports</option>
                    <option value="4">retraits</option>
                    <option value="5">prets</option>
                    <option value="6">remboursement</option>
                </select>
            </div>
            &nbsp;&nbsp;&nbsp;
            <div class="vos_operations_ligne">
                <select name="tri_projects" id="tri_projects" class="custom-select field-mini">
                    <option value="0">Projet</option>
                    <option value="1">Tous</option>
                    <?
                    foreach ($this->lProjectsLoans as $pro) {
                        ?>
                        <option value="<?= $pro['id_project'] ?>"><?= $pro['title'] ?></option><?
                    }
                    ?>
                </select>
            </div>
        </div>
        <div style="clear:both;"></div>
    </div>

    <div class="load_table_vos_operations">
        <img src="<?= $this->surl ?>/styles/default/images/loading.gif"/>
        Chargement...
    </div>

    <div class="content_table_vos_operations">
        <table class="table vos_operations transactions-history finances" border="0" cellspacing="0" cellpadding="0">
            <tr>
                <th width="200px" id="order_operations" align="left" class="col1" style="padding-left: 0px;">
                    <div class="th-wrap" style='top:-3px;width: 130px;'>
                        <i title='info-titre-operation' class="tooltip-anchor icon-double"></i>
                        <div class="title-ope">Operations
                            &nbsp;
                            <i class="icon-arrows" style="width:15px;"></i>
                        </div>
                    </div></th>
                <th width="200px" id="order_bdc" align="left" class="col1" style="padding-left: 0px;">
                    <div class="th-wrap" style='top:-3px;width: 200px;'>
                        <i title="info-titre-bon-caisse"
                           class="tooltip-anchor icon-bdc"></i>
                        <div class="title-ope">bdc &nbsp;
                            <i class="icon-arrows" style="width:15px;"></i>
                        </div>
                    </div>
                </th>
                <th width="150px" id="order_projects" align="center">
                    <div class="th-wrap">
                        <i title="" class="icon-person tooltip-anchor" style="margin-left:-15px;"
                           data-original-title="info-titre-projets"></i>
                        <div class="title-ope">projets &nbsp;
                            <i class="icon-arrows" style="width:15px;"></i>
                        </div>
                    </div>
                </th>
                <th width="140px" id="ordpoer_date">
                    <div class="th-wrap">
                        <i title="" class="icon-calendar tooltip-anchor"
                           data-original-title="info-titre-date-operation"></i>

                        <div class="title-ope">date-de-loperation &nbsp;
                            <i class="icon-arrows" style="width:15px;"></i>
                        </div>
                    </div>

                </th>
                <th width="180px" id="order_montant">
                    <div class="th-wrap" style="top:-2px;">
                        <i title="" class="icon-euro tooltip-anchor"
                           data-original-title="info-titre-montant-operation"></i>

                        <div class="title-ope">montant-de-loperation
                            &nbsp;<i class="icon-arrows" style="width:15px;"></i>
                        </div>
                    </div>
                </th>
                <th width="140px">
                    <div class="th-wrap">
                        <i title="" class="icon-bank tooltip-anchor"
                           data-original-title="info-titre-solde-compte"></i>
                        <div class="title-ope">solde-du-compte</div>
                    </div>
                </th>
            </tr>

            <?
            $i = 1;
            $asterix_on = false;
            foreach ($this->lTrans as $t) {
                $t['solde'] = ($t['solde'] / 100);
                $t['montant_prelevement'] = ($t['montant_prelevement'] / 100);

                if ($t['montant_operation'] > 0) {
                    $plus = '<b style="color:#40b34f;">+</b>';
                    $moins = '';
                    $couleur = 'style="color:#40b34f;"';
                } else {
                    $plus = '';
                    $moins = '<b style="color:red;">-</b>';
                    $couleur = 'style="color:red;"';
                } //couleurs

                if ($t['solde'] > 0) {
                    $solde = $t['solde'];
                }


                // Remb preteur
                if ($t['type_transaction'] == 5 || $t['type_transaction'] == 23) {
                    ?>
                    <!-- debut transasction remb -->
                    <tr class="transact remb_<?= $t['id_transaction'] ?> <?= ($i % 2 == 1 ? '' : 'odd') ?>">
                        <td><?= $t['libelle_operation'] ?></td>
                        <td><?= $t['bdc'] ?></td>
                        <td class="companieleft"><?= $t['libelle_projet'] ?></td>
                        <td><?= $this->dates->formatDate($t['date_operation'], 'd-m-Y') ?></td>
                        <td <?= $couleur ?>><?= number_format($t['montant_operation'] / 100, 2, ',', ' ') ?> €</td>
                        <td><?= number_format($t['solde'], 2, ',', ' ') ?> €</td>
                    </tr>
                    <tr class="content_transact <?= ($i % 2 == 1 ? '' : 'odd') ?>" height="0">
                        <td colspan="7">
                            <div class="div_content_transact content_remb_<?= $t['id_transaction'] ?>"
                                 style="display:none;">
                                <table class="soustable" width="100%">
                                    <tbody>
                                    <tr>
                                        <td width="138px" class="detail_remb">voici-le-detail-de-votre-remboursement</td>
                                        <td width="115px" class="detail_left">capital-rembourse</td>
                                        <td width="99px" class="chiffres" style="padding-bottom:8px; color:#40b34f;"><?= number_format(($t['montant_capital'] / 100), 2, ',', ' ') ?>€</td>
                                        <td width="101px">&nbsp;</td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td class="detail_left">interets-recus</td>
                                        <td class="chiffres"
                                            style="color:#40b34f;"><?= number_format(($t['montant_interet'] / 100), 2, ',', ' ') ?>
                                            €
                                        </td>
                                        <td>&nbsp;</td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td class="detail_left"><?= $t['libelle_prelevement'] ?></td>
                                        <td class="chiffres" style="color:red;">
                                            -<?= number_format($t['montant_prelevement'], 2, ',', ' ') ?> €
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
                    <!-- fin transasction remb -->
                <?
                $i++;
                }
                elseif (in_array($t['type_transaction'], array(8, 1, 3, 4, 16, 17, 19, 20)))
                {

                // Récupération de la traduction et non plus du libelle dans l'indexation (si changement on est ko)
                switch ($t['type_transaction']) {
                    case 8:
                        $t['libelle_operation'] = retrait-dargents;
                        break;
                    case 1:
                        $t['libelle_operation'] = depot-de-fonds;
                        break;
                    case 3:
                        $t['libelle_operation'] = depot-de-fonds;
                        break;
                    case 4:
                        $t['libelle_operation'] = depot-de-fonds;
                        break;
                    case 16:
                        $t['libelle_operation'] = offre-de-bienvenue;
                        break;
                    case 17:
                        $t['libelle_operation'] = retrait-offre;
                        break;
                    case 19:
                        $t['libelle_operation'] = gain-filleul;
                        break;
                    case 20:
                        $t['libelle_operation'] = gain-parrain;
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
                        <td><?= date('d/m/Y', strtotime($t['date_operation'])) ?></td>
                        <td <?= $couleur ?>><?= number_format($t['montant_operation'] / 100, 2, ',', ' ') ?> €</td>
                        <td><?= number_format($t['solde'], 2, ',', ' ') ?> €</td>
                    </tr>
                <?
                $i++;
                }
                elseif (in_array($t['type_transaction'], array(2)))
                {

                $bdc = $t['bdc'];
                if ($t['bdc'] == 0) {
                    $bdc = "";
                }


                //asterix pour les offres acceptees
                $asterix = "";
                $offre_accepte = false;
                if ($t['libelle_operation'] == $this->lng['preteur-operations-vos-operations']['offre-acceptee']) {
                    $asterix = " *";
                    $offre_accepte = true;
                    $asterix_on = true;
                }


                ?>
                    <tr <?= ($i % 2 == 1 ? '' : 'class="odd"') ?>>
                        <td><?= $t['libelle_operation'] ?></td>
                        <td><?= $bdc ?></td>
                        <td class="companieleft"><?= $t['libelle_projet'] ?></td>
                        <td><?= $this->dates->formatDate($t['date_operation'], 'd-m-Y') ?></td>
                        <td <?= (!$offre_accepte ? $couleur : '') ?>><?= number_format($t['montant_operation'] / 100, 2, ',', ' ') . ' €' ?></td>
                        <td><?= number_format($t['solde'], 2, ',', ' ') ?> €<?= $asterix ?></td>
                    </tr>
                    <?
                    $i++;
                }
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


        <script type="text/javascript">
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
                }

                $.post(add_url + "/ajax/vos_operations", val).done(function (data) {

                    var obj = jQuery.parseJSON(data);

                    $("#debut").val(obj.debut);
                    $("#fin").val(obj.fin);

                    $(".content_table_vos_operations").html(obj.html);
                    $(".load_table_vos_operations").fadeOut();
                });
            });
        </script>


    </div>
    <div>
        <h2>Affichage du TRI du portefeuille</h2>

        <h2>Affichage du TRI de chaque prêt </h2>

        <h2>Nombre de projets à probleme <?php  ?> /  nombre de projets : <?php ?></h2>

        <h2>Nombre de projets mis en ligne depuis son inscription : <?php echo $this->nblingne; ?><h2>

    </div>


    <script type="text/javascript">
        $("input,select").change(function () {

            $(".c2-sb-wrap").removeClass('populated');


            $(".load_table_vos_operations").fadeIn();


            var val = {
                debut: $("#debut").val(),
                fin: $("#fin").val(),
                nbMois: $("#nbMois").val(),
                annee: $("#annee").val(),
                tri_type_transac: $("#tri_type_transac").val(),
                tri_projects: $("#tri_projects").val(),
                id_last_action: $(this).attr('id')
            };
            //alert('debut : '+debut+' fin : '+fin+' mois : '+mois+' annee : '+annee+' tri_type_transac : '+tri_type_transac+' tri_projects : '+tri_projects);
            $.post(add_url + "/ajax/vos_operations", val).done(function (data) {
                //alert( "Data Loaded: " + data );

                var obj = jQuery.parseJSON(data);

                $("#debut").val(obj.debut);
                $("#fin").val(obj.fin);


                $("#filtres_secondaires").html(obj.html_filtre);
                $(".custom-select").c2Selectbox();

                $(".content_table_vos_operations").html(obj.html);
                $(".load_table_vos_operations").fadeOut();

            });
        });


    </script>

