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
        <a href="<?= $this->lurl ?>/preteurs/portefeuille/<?= $this->lenders_accounts->id_lender_account ?>"
        class="btn_link">Portefeuille & Performances</a>
    </div>


    Et ici viedra la vue des l'historique des mails
    avec une lightbox preview à droite


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