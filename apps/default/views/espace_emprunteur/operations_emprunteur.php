<style type="text/css">
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

    .vos_operations tr.content_transact td {
        border-bottom: 1px solid #b10366;
        margin-top: 10px;
        padding-bottom: 10px;
    }

    .vos_operations tr.content_transact td .soustable tr td {
        padding: 0px;
        height: 15px;
    }

    .vos_operations tr.content_transact td .soustable tr td {
        text-align: right;
    }

    .vos_operations tr.content_transact td .soustable tr td {
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

    .vos_operations  {
        text-align: left;
    }

    .vos_operations_ligne {
        display: inline-block;
        vertical-align: top;

    }

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

    .table-filter .period {
        z-index: 10;
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
        font-size: 12px;
    }

    .title-ope {
        margin-top: 12.5px;
    }

    .body_content_table_vos_operations .positif {
        color:#40b34f;
    }
    .body_content_table_vos_operations .negatif {
        color:red;
    }

    .body_content_table_vos_operations .col2 {
        width: 140px;
        text-align: left;
    }


</style>
<h2><?= $this->lng['espace-emprunteur']['operations'] ?></h2>
<div class="table-filter clearfix">
    <div class="period">
        <strong><?= $this->lng['espace-emprunteur']['selectionnez-une-periode'] ?></strong><br/>
        <table>
            <tr>
                <td>
                    <?= $this->lng['espace-emprunteur']['glissante'] ?><br/>

                    <div class="vos_operations_ligne div_mois">
                        <select name="nbMois" id="nbMois" class="custom-select field-hundred">
                            <option value="1"><?= $this->lng['espace-emprunteur']['1m'] ?></option>
                            <option value="1"><?= $this->lng['espace-emprunteur']['1m'] ?></option>
                            <option value="3"><?= $this->lng['espace-emprunteur']['3m'] ?></option>
                            <option value="6"><?= $this->lng['espace-emprunteur']['6m'] ?></option>
                            <option value="12"><?= $this->lng['espace-emprunteur']['12m'] ?></option>
                        </select>
                    </div>
                </td>
                <td>
                    <div class="ou"><?= $this->lng['espace-emprunteur']['sous-titre-operation-ou'] ?></div>
                </td>
                <td>
                    <?= $this->lng['espace-emprunteur']['annuelle'] ?><br/>

                    <div class="vos_operations_ligne div_annee">
                        <select name="annee" id="annee" class="custom-select field-hundred">
                            <option value="<?= date('Y') ?>"><?= date('Y') ?></option>
                            <?php for ($i = date('Y'); $i >= 2013; $i--) : ?>
                                <option value="<?= $i ?>"><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </td>
                <td>
                    <div class="ou"><?= $this->lng['espace-emprunteur']['sous-titre-operation-ou'] ?></div>
                </td>
                <td>
                    <?= $this->lng['espace-emprunteur']['personalisee'] ?><br/>

                    <div class="vos_operations_ligne div_debut">
                        <input type="text" id="debut" name="debut" title="debut"
                               value="<?= $this->sDisplayDateTimeStart ?>" class="field" style="width:72px;">
                    </div>
                </td>
                <td>
                    <div class="au"><?= $this->lng['espace-emprunteur']['sous-titre-operation-au'] ?></div>
                </td>
                <td>
                    <div class="vos_operations_ligne div_fin">
                        <input type="text" id="fin" name="fin" title="fin" value="<?= $this->sDisplayDateTimeEnd ?>" class="field" style="width:72px;">
                    </div>
                </td>
            </tr>
        </table>
    </div>
    <div class="export">
        <div class="vos_operations_ligne" style="text-align:center;">
            <?= $this->lng['espace-emprunteur']['imprimer'] ?><br/>
            <a href="<?= $this->lurl ?>/espace_emprunteur/getPdfOperations" target="_blank"><img class="print" src="<?= $this->surl ?>/styles/default/preteurs/images/icon-print.png"/></a>
        </div>
        <div style="width:30px;display:inline-block;"></div>
        <div class="vos_operations_ligne" style="text-align:center;">
            <?= $this->lng['espace-emprunteur']['exporter'] ?><br/>
            <a href="<?= $this->lurl ?>/espace_emprunteur/getCSVOperations" target="_blank"><img class="xls" src="<?= $this->surl ?>/images/default/xls_hd.png"/></a>
        </div>
    </div>
    <div style="clear:both;"></div>
    <div class="filtre" id="filtres_secondaires">
        <b><?= $this->lng['espace-emprunteur']['selectionnez-un-ou-plusieurs-filtres'] ?></b><br/><br/>

        <div class="vos_operations_ligne">
            <select name="tri_type_transac" id="tri_type_transac" class="custom-select field-mini" style="width: 200px;">
                <option value="0"><?= $this->lng['espace-emprunteur']['tri-operation'] ?></option>
                <option value="99"><?= $this->lng['espace-emprunteur']['tri-toutes'] ?></option>
                <option value="<?= \clients::COMMISSION_MENSUELLE ?>"><?= $this->lng['espace-emprunteur']['operations-type-commission-mensuelle'] ?></option>
                <option value="<?= \clients::AFF_MENSUALITE_PRETEURS ?>"><?= $this->lng['espace-emprunteur']['operations-type-affectation-preteurs'] ?></option>
                <option value="<?= \clients::PRLV_MENSUALITE ?>"><?= $this->lng['espace-emprunteur']['operations-type-prelevement-mensualite'] ?></option>
                <option value="<?= \clients::VIREMENT ?>"><?= $this->lng['espace-emprunteur']['operations-type-virement'] ?></option>
                <option value="<?= \clients::OCTROI_FINANCMENT ?>"><?= $this->lng['espace-emprunteur']['operations-type-financement'] ?></option>
                <option value="<?= \clients::REMBOURSEMENT_ANTICIPE ?>"><?= $this->lng['espace-emprunteur']['operations-type-remboursement-anticipe'] ?></option>
                <option value="<?= \clients::AFFECTATION_RA_PRETEURS ?>"><?= $this->lng['espace-emprunteur']['operations-type-affectation-ra-preteur'] ?></option>
            </select>
        </div>
        &nbsp;&nbsp;&nbsp;
        <div class="vos_operations_ligne">
            <select name="tri_projects" id="tri_projects" class="custom-select field-mini">
                <option value="0"><?= $this->lng['espace-emprunteur']['trier-projet'] ?></option>
                <option value="99"><?= $this->lng['espace-emprunteur']['trier-tous'] ?></option>
                <?php  foreach ($this->aClientsProjects as $aProject) : ?>
                    <option value="<?= $aProject['id_project'] ?>"><?= $aProject['id_project'] . ' ' . $aProject['title'] ?></option>
                <?php endforeach; ?>
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
    <table class="table vos_operations transactions-history finances" border="5px" cellspacing="0" cellpadding="0" style="table-layout: fixed;">
        <thead>
        <tr>
            <th width="260px" id="order_operations" align="left" class="col1" style="padding-left: 0px;">
                <div class="th-wrap" style='top:-3px;width: 250px;'>
                    <i class="tooltip-anchor icon-double"></i>
                    <div class="title-ope"><?= $this->lng['espace-emprunteur']['operation'] ?>&nbsp;
                    </div>
                </div>
            </th>
            <th width="140px" id="order_id_projet" align="right" class="col2" style="padding-left: 0px;">
                <div class="th-wrap" style='top:-3px;width: 60px;'>
                    <i class="tooltip-anchor icon-bdc"></i>
                    <div class="title-ope"><?= $this->lng['espace-emprunteur']['projet'] ?>&nbsp;
                    </div>
                </div>
            </th>
            <th width="140px" id="order_date">
                <div class="th-wrap">
                    <i class="icon-calendar tooltip-anchor" style="margin-left:-15px;" ></i>
                    <div class="title-ope"><?= $this->lng['espace-emprunteur']['date-de-loperation'] ?>&nbsp;
                    </div>
                </div>
            </th>
            <th width="180px" id="order_montant">
                <div class="th-wrap" style="top:-2px;">
                    <i class="icon-euro tooltip-anchor"></i>
                    <div class="title-ope"><?= $this->lng['espace-emprunteur']['montant-de-loperation'] ?>&nbsp;
                    </div>
                </div>
            </th>
            <th width="140px">
                <div class="th-wrap">
                    <i class="icon-person tooltip-anchor" style="margin-left:-15px;"></i>
                    <div class="title-ope"><?= $this->lng['espace-emprunteur']['detail-preteurs'] ?></div>
                </div>
            </th>
        </tr>
        </thead>
        <tbody class="body_content_table_vos_operations">

        </tbody>
    </table>
</div>

<script type="text/javascript">
     $(function() {
         $("input, select").change(function() {
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

             $.post(add_url + "/ajax/operations_emprunteur", val).done(function (data) {
                 var obj = jQuery.parseJSON(data);

                 $("#debut").val(obj.debut);
                 $("#fin").val(obj.fin);

                 $(".custom-select").c2Selectbox();

                 $(".body_content_table_vos_operations").html(obj.html);
                 $(".load_table_vos_operations").fadeOut();
             });
         });

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
    });
</script>
