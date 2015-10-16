<script type="text/javascript">
    $(document).ready(function () {

        $.datepicker.setDefaults($.extend({showMonthAfterYear: false}, $.datepicker.regional['fr']));

        $("#debut").datepicker({
            changeMonth: true,
            changeYear: true,
            yearRange: '<?= (date('Y') - 40) ?>:<?= (date('Y')) ?>',
                        maxDate: '<?= $this->date_fin_display ?>',
                        onClose: function (selectedDate) {
                            $("#fin").datepicker("option", "minDate", selectedDate);
                        }
                    });
                    $("#fin").datepicker({
                        changeMonth: true,
                        changeYear: true,
                        yearRange: '<?= (date('Y') - 40) ?>:<?= (date('Y')) ?>',
                                    minDate: '<?= $this->date_debut_display ?>',
                                    onClose: function (selectedDate) {
                                        $("#debut").datepicker("option", "maxDate", selectedDate);
                                    }
                                });

                            });
</script>
<style type="text/css">
    .iconplusmoins{color:white;font-size:18px;cursor:default;vertical-align: middle;}


    .vos_operations{font-size:13px;}
    .vos_operations td{padding:5px;}
    .vos_operations tr.content_transact td{height: 0px;padding: 0px;}

    table.vos_operations th#order_projects{text-align:left;}

    .vos_operations tr.transact{cursor:pointer;}
    .vos_operations tr.content_transact .div_content_transact{display:inline-block;width:100%;}

    .vos_operations tr.content_transact td .soustable{border-bottom:1px solid #b10366;margin-top: 10px;padding-bottom: 10px;}
    .vos_operations tr.content_transact td .soustable tr td{padding:0px;height: 15px;}
    .vos_operations tr.content_transact td .soustable tr td.chiffres{text-align:right;}
    .vos_operations tr.content_transact td .soustable tr td.detail_remb{padding-left:5px;vertical-align:top;}

    .vos_operations tr:nth-child(even) td{background-color:white;}
    .vos_operations tr:hover td{background-color:white;}
    .vos_operations tr.odd td{background:#fafafa;}
    .vos_operations .icon-arrows{cursor:pointer;}
    .vos_operations .companieleft{text-align:left;}
    .soustable .detail_left{text-align:left;}
    .vos_operations_ligne{display:inline-block;vertical-align: top;}

    /*.vos_operations .print{margin-top: 3px;}
    .vos_operations .xls{margin-top: 6px;}*/

    .vos_operations .print{margin-top: 8px;width:50px;}
    .vos_operations .xls{margin-top: 6px;width:50px;}

    .load_table_vos_operations{
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
        width: 100px;}

    .th-wrap{color:white;}



    .table-filter .period{float:left;border:1px solid;padding:10px;border-radius:3px; border-color: #8D8D8D;}
    .table-filter .period tr td{vertical-align:bottom;line-height: 30px;}
    .table-filter .period tr td .ou{text-align: center;width: 80px;}
    .table-filter .period tr td .au{text-align: center;width: 40px;}

    .c2-sb-wrap{z-index:1;}
    .table-filter .period .c2-sb-wrap{z-index:10;}

    .populated .c2-sb-text,
    .populated {color: #b20066 !important;}


    .table-filter .export{float:right;}

    .table-filter .filtre{float:left;border:1px solid;padding:15px;margin-top:15px;border-radius:3px; border-color: #8D8D8D; width:420px;}


    .vos_operations .th-wrap {
        text-align: center;
        /*width: 100px;*/
        font-size: 12px;
    }

    .filtre .c2-sb-wrap{width: 200px; }
    .filtre .c2-sb-text{width: 140px !important;}

    .soustable tr td{padding-top: 5px !important; padding-bottom: 5px !important;}

    .title-ope{margin-top:12.5px;}

</style>
<h2><?= $this->lng['preteur-operations']['titre-1'] ?></h2>
<div class="table-filter clearfix">

    <div class="period">
        <b><?= $this->lng['preteur-operations']['selectionnez-une-periode'] ?></b><br />

        <table>
            <tr>
                <td>
                    <?= $this->lng['preteur-operations']['glissante'] ?><br />
                    <div  class="vos_operations_ligne div_mois">
                        <select name="nbMois" id="nbMois" class="custom-select field-hundred">
                            <option value="1"><?= $this->lng['preteur-operations']['1m'] ?></option>
                            <option value="1"><?= $this->lng['preteur-operations']['1m'] ?></option>
                            <option value="3"><?= $this->lng['preteur-operations']['3m'] ?></option>
                            <option value="6"><?= $this->lng['preteur-operations']['6m'] ?></option>
                            <option value="12"><?= $this->lng['preteur-operations']['12m'] ?></option>
                        </select> 
                    </div>
                </td>
                <td>
                    <div class="ou"><?= $this->lng['preteur-operations']['sous-titre-operation-ou'] ?></div>
                </td>
                <td>
                    <?= $this->lng['preteur-operations']['annuelle'] ?><br />
                    <div class="vos_operations_ligne div_annee">
                        <select name="annee" id="annee" class="custom-select field-hundred">
                            <option value="<?= date('Y') ?>"><?= date('Y') ?></option>
                            <?
                            for ($i = date('Y'); $i >= 2013; $i--) {
                                ?><option value="<?= $i ?>"><?= $i ?></option><?
                            }
                            ?>
                        </select> 
                    </div>
                </td>
                <td>
                    <div class="ou"><?= $this->lng['preteur-operations']['sous-titre-operation-ou'] ?></div>
                </td>
                <td>
                    <?= $this->lng['preteur-operations']['personalisee'] ?><br />
                    <div class="vos_operations_ligne div_debut">
                        <input type="text" id="debut" name="debut" title="debut" value="<?= $this->date_debut_display ?>" class="field" style="width:72px;">
                    </div>
                </td>
                <td>
                    <div class="au"><?= $this->lng['preteur-operations']['sous-titre-operation-au'] ?></div>
                </td>
                <td>
                    <div class="vos_operations_ligne div_fin">
                        <input type="text" id="fin" name="fin" title="fin" value="<?= $this->date_fin_display ?>" class="field" style="width:72px;">
                    </div>
                </td>
            </tr>
        </table>

    </div>

    <div class="export">
        <div class="vos_operations_ligne" style="text-align:center;">
            Imprimer<br />
            <a href="<?= $this->lurl ?>/pdf/vos_operations_pdf_indexation" target="_blank"><img class="print" src="<?= $this->surl ?>/styles/default/preteurs/images/icon-print.png" /></a>
        </div>

        <div style="width:30px;display:inline-block;"></div>
        <div class="vos_operations_ligne" style="text-align:center;">
            Exporter<br />
            <a href="<?= $this->lurl ?>/operations/vos_operation_csv" target="_blank"><img class="xls" src="<?= $this->surl ?>/images/default/xls_hd.png" /></a>
        </div>
    </div>
    <div style="clear:both;"></div>
    <div class="filtre" id="filtres_secondaires">
        <b><?= $this->lng['preteur-operations']['selectionnez-un-ou-plusieurs-filtres'] ?></b><br /><br />
        <div class="vos_operations_ligne">
            <select name="tri_type_transac" id="tri_type_transac" class="custom-select field-mini" width="200px;">
                <option value="1"><?= $this->lng['preteur-operations']['tri-operation'] ?></option>
                <option value="1"><?= $this->lng['preteur-operations']['tri-toutes'] ?></option>
                <option value="2"><?= $this->lng['preteur-operations']['tri-apports-retraits'] ?></option>
                <option value="3"><?= $this->lng['preteur-operations']['tri-apports'] ?></option>
                <option value="4"><?= $this->lng['preteur-operations']['tri-retraits'] ?></option>
                <option value="5"><?= $this->lng['preteur-operations']['tri-prets'] ?></option>
                <option value="6"><?= $this->lng['preteur-operations']['tri-remboursement'] ?></option>

            </select> 
        </div>
        &nbsp;&nbsp;&nbsp;
        <div class="vos_operations_ligne">
            <select name="tri_projects" id="tri_projects" class="custom-select field-mini">
                <option value="0"><?= $this->lng['preteur-operations']['trier-projet'] ?></option>
                <option value="1"><?= $this->lng['preteur-operations']['trier-tous'] ?></option>
                <?
                foreach ($this->lProjectsLoans as $pro) {
                    ?><option value="<?= $pro['id_project'] ?>"><?= $pro['title'] ?></option><?
                }
                ?>
            </select> 
        </div>
    </div>


    <div style="clear:both;"></div>

    <?php /* ?><div class="vos_operations_ligne" style="line-height: 35px;"><?=$this->lng['preteur-operations']['sous-titre-operation']?></div>

      <div class="vos_operations_ligne">
      <input type="text" id="debut" name="debut" title="debut" value="<?=$this->date_debut_display?>" class="field" style="width:72px;">
      </div>

      <div class="vos_operations_ligne" style="line-height: 35px;"><?=$this->lng['preteur-operations']['sous-titre-operation-au']?></div>

      <div class="vos_operations_ligne">
      <input type="text" id="fin" name="fin" title="fin" value="<?=$this->date_fin_display?>" class="field" style="width:72px;">
      </div>

      &nbsp;&nbsp;
      <div  class="vos_operations_ligne">
      <select name="nbMois" id="nbMois" class="custom-select field-extra-tiny">
      <option value="1"><?=$this->lng['preteur-operations']['1m']?></option>
      <option value="1"><?=$this->lng['preteur-operations']['1m']?></option>
      <option value="3"><?=$this->lng['preteur-operations']['3m']?></option>
      <option value="6"><?=$this->lng['preteur-operations']['6m']?></option>
      <option value="12"><?=$this->lng['preteur-operations']['12m']?></option>
      </select>
      </div>

      <div class="vos_operations_ligne">
      <select name="annee" id="annee" class="custom-select field-extra-tiny">
      <option value="<?=date('Y')?>"><?=date('Y')?></option>
      <?
      for($i=date('Y');$i>=2013;$i--){
      ?><option value="<?=$i?>"><?=$i?></option><?
      }
      ?>
      </select>
      </div>

      &nbsp;
      <div class="vos_operations_ligne">
      <a href="<?=$this->lurl?>/pdf/vos_operations_pdf" target="_blank"><img class="print" src="<?=$this->surl?>/styles/default/preteurs/images/icon-print.png" /></a>
      </div>

      &nbsp;
      <div class="vos_operations_ligne">
      <a href="<?=$this->lurl?>/operations/vos_operation_csv" target="_blank"><img class="xls" src="<?=$this->surl?>/images/default/xls.png" /></a>
      </div>

      &nbsp;
      <div class="vos_operations_ligne">
      <select name="tri_type_transac" id="tri_type_transac" class="custom-select field-mini">
      <option value="1"><?=$this->lng['preteur-operations']['tri-operation']?></option>
      <option value="1"><?=$this->lng['preteur-operations']['tri-toutes']?></option>
      <option value="2"><?=$this->lng['preteur-operations']['tri-apports-retraits']?></option>
      <option value="3"><?=$this->lng['preteur-operations']['tri-apports']?></option>
      <option value="4"><?=$this->lng['preteur-operations']['tri-retraits']?></option>
      <option value="5"><?=$this->lng['preteur-operations']['tri-prets']?></option>
      <option value="6"><?=$this->lng['preteur-operations']['tri-remboursement']?></option>

      </select>
      </div>

      <div class="vos_operations_ligne">
      <select name="tri_projects" id="tri_projects" class="custom-select field-mini">
      <option value="0"><?=$this->lng['preteur-operations']['trier-projet']?></option>
      <option value="1"><?=$this->lng['preteur-operations']['trier-tous']?></option>
      <?
      foreach($this->lProjectsLoans as $pro){
      ?><option value="<?=$pro['id_project']?>"><?=$pro['title']?></option><?
      }
      ?>
      </select>
      </div><?php */ ?>


</div>
<div class="load_table_vos_operations">
    <img src="<?= $this->surl ?>/styles/default/images/loading.gif" />
    Chargement...
</div>

<div class="content_table_vos_operations">
    <table class="table vos_operations transactions-history finances" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <th width="200px" id="order_operations" align="left" class="col1" style="padding-left: 0px;">
        <div class="th-wrap" style='top:-3px;width: 130px;'>
            <i title="<?= $this->lng['preteur-operations-pdf']['info-titre-operation'] ?>" class="tooltip-anchor icon-double"></i>
            <div class="title-ope"><?= $this->lng['preteur-operations-pdf']['operations'] ?>&nbsp;<i class="icon-arrows" style="width:15px;"></i></div>
        </div>                
        </th>

        <th width="200px" id="order_bdc" align="left" class="col1" style="padding-left: 0px;">
        <div class="th-wrap" style='top:-3px;width: 200px;'>
            <i title="<?= $this->lng['preteur-operations-pdf']['info-titre-bon-caisse'] ?>" class="tooltip-anchor icon-bdc"></i>
            <div class="title-ope"><?= $this->lng['preteur-operations-pdf']['bdc'] ?>&nbsp;<i class="icon-arrows" style="width:15px;"></i></div>
        </div>                
        </th>           

        <th width="150px" id="order_projects" align="center">
        <div class="th-wrap">
            <i title="" class="icon-person tooltip-anchor" style="margin-left:-15px;" data-original-title="<?= $this->lng['preteur-operations-pdf']['info-titre-projets'] ?>"></i>
            <div class="title-ope"><?= $this->lng['preteur-operations-pdf']['projets'] ?>&nbsp;<i class="icon-arrows" style="width:15px;"></i></div>
        </div>            

        </th>
        <th width="140px" id="ordpoer_date">
        <div class="th-wrap">
            <i title="" class="icon-calendar tooltip-anchor" data-original-title="<?= $this->lng['preteur-operations-pdf']['info-titre-date-operation'] ?>"></i>
            <div class="title-ope"><?= $this->lng['preteur-operations-pdf']['date-de-loperation'] ?>&nbsp;<i class="icon-arrows" style="width:15px;"></i></div>
        </div>

        </th>
        <?php /* ?><th width="80">
          <div class="th-wrap"><i title="" class="tooltip-anchor"><span class="iconplusmoins">+</span></i></div>
          </th>
          <th width="51">
          <div class="th-wrap"><i title="" class="tooltip-anchor"><span class="iconplusmoins">-</span></i></div>
          </th><?php */ ?>
        <th width="180px" id="order_montant" >
        <div class="th-wrap"  style="top:-2px;">
            <i title="" class="icon-euro tooltip-anchor" data-original-title="<?= $this->lng['preteur-operations-pdf']['info-titre-montant-operation'] ?>"></i>
            <div class="title-ope"><?= $this->lng['preteur-operations-pdf']['montant-de-loperation'] ?>&nbsp;<i class="icon-arrows" style="width:15px;"></i></div>
        </div>

        </th>
        <th width="140px">
        <div class="th-wrap" >
            <i title="" class="icon-bank tooltip-anchor" data-original-title="<?= $this->lng['preteur-operations-pdf']['info-titre-solde-compte'] ?>"></i>
            <div class="title-ope"><?= $this->lng['preteur-operations-pdf']['solde-du-compte'] ?></div>
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
            }

            //$solde = $this->transactions->getSoldeDateLimite($t['id_client'],date('Y-m-d',strtotime($t['added'])));
            if ($t['solde'] > 0) {
                $solde = $t['solde'];
            }


            // Remb preteur
            if ($t['type_transaction'] == 5 || $t['type_transaction'] == 23) {
                // Récupération de la traduction et non plus du libelle dans l'indexation (si changement on est ko)
                //$t['libelle_operation'] = $this->lng['preteur-operations-vos-operations']['remboursement'];
                ?>
                <!-- debut transasction remb -->
                <tr class="transact remb_<?= $t['id_transaction'] ?> <?= ($i % 2 == 1 ? '' : 'odd') ?>">
                    <td><?= $t['libelle_operation'] ?> <span class="plusmoinsOperations"></span></td>
                    <td><?= $t['bdc'] ?></td>
                    <td class="companieleft"><?= $t['libelle_projet'] ?></td>
                    <td><?= $this->dates->formatDate($t['date_operation'], 'd-m-Y') ?></td>
                    <?php /* ?><td><?=$plus?></td>
                      <td><?=$moins?></td><?php */ ?>
                    <td <?= $couleur ?>><?= number_format($t['montant_operation'] / 100, 2, ',', ' ') ?> €</td>
                    <td><?= number_format($t['solde'], 2, ',', ' ') ?> €</td>
                </tr>
                <tr class="content_transact <?= ($i % 2 == 1 ? '' : 'odd') ?>" height="0">
                    <?php /* ?><tr<?=($i%2 == 1?'':' class="odd"')?>><?php */ ?>
                    <td colspan="7">
                        <div class="div_content_transact content_remb_<?= $t['id_transaction'] ?>" style="display:none;">
                            <table class="soustable" width="100%" >
                                <tbody>
                                    <?php /* ?><tr>
                                      <td colspan="4">&nbsp;</td>
                                      </tr><?php */ ?>
                                    <tr>
                                        <td width="138px" class="detail_remb"><?= $this->lng['preteur-operations-vos-operations']['voici-le-detail-de-votre-remboursement'] ?></td>
                                        <td width="115px" class="detail_left"><?= $this->lng['preteur-operations-vos-operations']['capital-rembourse'] ?></td>
                                        <td width="99px" class="chiffres" style="padding-bottom:8px; color:#40b34f;"><?= number_format(($t['montant_capital'] / 100), 2, ',', ' ') ?> €</td>
                                        <td width="101px">&nbsp;</td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td class="detail_left"><?= $this->lng['preteur-operations-vos-operations']['interets-recus'] ?></td>
                                        <td class="chiffres" style="color:#40b34f;"><?= number_format(($t['montant_interet'] / 100), 2, ',', ' ') ?> €</td>
                                        <td>&nbsp;</td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td class="detail_left"><?= $t['libelle_prelevement'] ?></td>
                                        <td class="chiffres" style="color:red;">-<?= number_format($t['montant_prelevement'], 2, ',', ' ') ?> €</td>
                                        <td>&nbsp;</td>
                                    </tr>
                                    <?php
                                    if ($t['recouvrement'] == 1) {
                                        ?>
                                        <tr>
                                            <td></td>
                                            <td class="detail_left"><?= $this->lng['preteur-operations-vos-operations']['com-ht'] ?></td>
                                            <td class="chiffres" style="color:red;">-<?= number_format($t['commission_ht']/100, 2, ',', ' ') ?> €</td>
                                            <td>&nbsp;</td>
                                        </tr>
                                        <tr>
                                            <td></td>
                                            <td class="detail_left"><?= $this->lng['preteur-operations-vos-operations']['com-tva'] ?></td>
                                            <td class="chiffres" style="color:red;">-<?= number_format($t['commission_tva']/100, 2, ',', ' ') ?> €</td>
                                            <td>&nbsp;</td>
                                        </tr>
                                        <tr>
                                            <td></td>
                                            <td class="detail_left"><?= $this->lng['preteur-operations-vos-operations']['com-ttc'] ?></td>
                                            <td class="chiffres" style="color:red;">-<?= number_format($t['commission_ttc'], 2, ',', ' ') ?> €</td>
                                            <td>&nbsp;</td>
                                        </tr>
                                        <?php
                                    }
                                    ?>
                                    <tr>
                                        <td colspan="4" style=" height:4px;"></td>
                                    </tr>                            
                                </tbody>
                            </table>
                        </div>
                    </td>
                </tr>
                <script type="text/javascript">
                    $(".remb_<?= $t['id_transaction'] ?>").click(function ()
                    {
                        $(".content_remb_<?= $t['id_transaction'] ?>").slideToggle();
                        if ($(".remb_<?= $t['id_transaction'] ?>").hasClass("on_display"))
                        {
                            $(".remb_<?= $t['id_transaction'] ?>").find('span').addClass('plus');
                            $(".remb_<?= $t['id_transaction'] ?>").find('span').removeClass('moins');

                            $(".remb_<?= $t['id_transaction'] ?>").addClass("off_display");
                            $(".remb_<?= $t['id_transaction'] ?>").removeClass('on_display');
                        }
                        else
                        {
                            $(".remb_<?= $t['id_transaction'] ?>").find('span').addClass('moins');
                            $(".remb_<?= $t['id_transaction'] ?>").find('span').removeClass('plus');

                            $(".remb_<?= $t['id_transaction'] ?>").addClass("on_display");
                            $(".remb_<?= $t['id_transaction'] ?>").removeClass('off_display');
                        }
                    });

                </script>
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
                if ($t['type_transaction'] == 8 && $t['montant_operation'] > 0) {
                    $type = "Annulation retrait des fonds - compte bancaire clos";
                } else {
                    $type = $t['libelle_operation'];
                }

                // si l'offre est accepté on prend la date d'acceptation
                /* $date_transaction = $this->dates->formatDate($t['date_transaction'],'d-m-Y');
                  if($t['type_transaction'] == 3)
                  {
                  // si on a le pouvoir
                  $this->projects_pouvoir = $this->loadData('projects_pouvoir');
                  if($this->projects_pouvoir->get($t['id_project'],'id_project'))
                  {
                  $date_transaction = date('d/m/Y',strtotime($this->projects_pouvoir->updated));
                  }
                  } */
                ?>

                <tr <?= ($i % 2 == 1 ? '' : 'class="odd"') ?>>
                    <td><?= $type ?></td>
                    <td></td>
                    <td></td>
                    <td><?= date('d/m/Y', strtotime($t['date_operation'])) ?></td>
                    <?php /* ?><td><?=$plus?></td>
                      <td><?=$moins?></td><?php */ ?>
                    <td <?= $couleur ?>><?= number_format($t['montant_operation'] / 100, 2, ',', ' ') ?> €</td>
                    <td><?= number_format($t['solde'], 2, ',', ' ') ?> €</td>
                </tr>
                <?
                $i++;
            } elseif (in_array($t['type_transaction'], array(2))) {

                /* if($t['id_bid_remb'] != 0){
                  $this->bids->get($t['id_bid_remb'],'id_bid');
                  $id_loan = '';
                  }
                  else{
                  $this->wallets_lines->get($t['id_transaction'],'id_transaction');
                  $this->bids->get($this->wallets_lines->id_wallet_line,'id_lender_wallet_line');

                  if($this->loans->get($this->bids->id_bid,'status = 0 AND id_bid')){
                  //$id_loan = ' - '.$this->loans->id_loan;
                  $id_loan = $this->loans->id_loan;
                  }
                  else $id_loan = '';
                  } */

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
                    <?php /* ?><td><?=$plus?></td>
                      <td><?=$moins?></td><?php */ ?>
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
        <div >* <?= $this->lng['preteur-operations-vos-operations']['offre-acceptee-asterix'] ?></div>
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

