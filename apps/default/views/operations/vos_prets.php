<style type="text/css">
    .detail-ope .th-wrap {
        text-align: center;
        width: 100px;
        font-size: 12px;
    }

    .detail-ope .th-wrap .title-ope {
        margin-top: 3px;
        display: block;
    }

    .detail-ope .th-wrap-v2 {
        width: 246px;
    }

    .detail-ope .calendar-title {
        border-top: 1px solid white;
        margin-top: 3px;
    }

    .detail-ope .calendar-title span {
        width: 80px;
        display: inline-block;
        padding-top: 3px;
    }

    .detail-ope .calandar-ech {
        width: 79px;
        display: inline-block;
        padding-top: 3px;
    }

    .detail-ope.table td {
        padding-left: 0px;
        padding-right: 0px;
    }

    .detail-ope.table th:first-child {
        padding-left: 0px;
        windows: 189px;
    }

    .detail-ope .cadreEtoiles {
        left: 18px;
    }

    .detail-ope .detailLoans, .detail-ope .detailLoans_declaration_creances {
        display: inline-block;
        width: 100%;
        border-bottom: 1px solid #b10366;
    }

    .detail-ope .detailLoans .borderTop, .detail-ope .detailLoans_declaration_creances .borderTop {
        border-top: 1px solid #b10366;
    }

    .detail-ope .detailLoans .borderBottom, .detail-ope .detailLoans_declaration_creances .borderBottom {
        border-bottom: 1px solid #b10366;
    }

    .detail-ope .detailLoans td, .detail-ope .detailLoans_declaration_creances td {
        padding-top: 3px;
        padding-bottom: 3px;
    }

    .content_declaration_creances td {
        vertical-align: middle;
    }

    .detail-ope .col1 {
        width: 214px;
    }

    .detail-ope .col2 {
        width: 100px;
    }

    .detail-ope .col3 {
        width: 100px;
    }

    .detail-ope .col4 {
        width: 100px;
    }

    .detail-ope .col5 {
        width: 246px;
    }

    .detail-ope .col6 {
        width: 100px;
    }

    .detail-ope tr:nth-child(even) td {
        background-color: white;
    }

    .detail-ope tr:hover td {
        background-color: white;
    }

    .detail-ope tr.odd td {
        background: #fafafa;
    }

    .detail-ope .icon-arrows {
        cursor: pointer;
    }

    .c2-sb-list-wrap {
        max-height: 228px;
    }

    .load {
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

    .override_plus {
        line-height: 18px !important;
        height: 15px !important;
        padding: 0 4px !important;
        top: 0px !important;
        width: 10px;
    }

    .title-ope {
        margin-top: 12.5px !important;
    }


</style>

<h2><?= $this->lng['preteur-operations']['titre-3'] ?></h2>
<p><?= $this->lng['profile']['contenu-partie-4'] ?></p>
<div class="table-filter clearfix">
    <p class="left"><?= $this->lng['profile']['historique-des-projets'] ?><?= $this->clients->id_client ?></p>

    <div class="select-box right" style="margin-left:10px;   width: 175px !important;">
        <select name="anneeDetailPret" id="anneeDetailPret" class="custom-select field-mini">
            <option value="<?= date('Y') ?>"><?= $this->lng['profile']['annee'] ?> <?= date('Y') ?></option>
            <?
            for ($i = date('Y'); $i >= 2013; $i--) {
                ?>
                <option value="<?= $i ?>"><?= $this->lng['profile']['annee'] ?> <?= $i ?></option><?
            }
            ?>
        </select>
    </div>
</div>

<div class="load">
    <img src="<?= $this->surl ?>/styles/default/images/loading.gif"/>
    Chargement...
</div>

<div class="loadDetailOp">
    <table class="table detail-ope finances">
        <tr>
            <th align="left" class="col1" id="order_titre_prets">
                <div class="th-wrap">
                    <i title="<?= $this->lng['preteur-operations-detail']['info-titre-projet'] ?>" class="icon-person tooltip-anchor"></i>
                    <div class="title-ope"><?= $this->lng['preteur-operations-detail']['titre-projet'] ?>&nbsp;<i class="icon-arrows"></i></div>
                </div>
            </th>
            <th class="col2" id="order_note_prets">
                <div class="th-wrap">
                    <i title="<?= $this->lng['preteur-operations-detail']['info-titre-note'] ?>" class="icon-gauge tooltip-anchor"></i>
                    <div class="title-ope"><?= $this->lng['preteur-operations-detail']['titre-note'] ?>&nbsp;<i class="icon-arrows"></i></div>
                </div>
            </th>
            <th class="col3" id="order_montant_prets">
                <div class="th-wrap">
                    <i title="<?= $this->lng['preteur-operations-detail']['info-titre-montant'] ?>" class="icon-euro tooltip-anchor"></i>
                    <div class="title-ope"><?= $this->lng['preteur-operations-detail']['titre-montant'] ?>&nbsp;<i class="icon-arrows"></i></div>
                </div>
            </th>
            <th class="col4" id="order_interet_prets">
                <div class="th-wrap">
                    <i title="<?= $this->lng['preteur-operations-detail']['info-titre-interet'] ?>" class="icon-graph tooltip-anchor"></i>
                    <div class="title-ope"><?= $this->lng['preteur-operations-detail']['titre-interet'] ?>&nbsp;<i class="icon-arrows"></i></div>
                </div>
            </th>
            <th>
                <div class="th-wrap th-wrap-v2">
                    <i title="<?= $this->lng['preteur-operations-detail']['info-calendrier'] ?>" class="icon-calendar tooltip-anchor"></i>
                    <div class="calendar-title" style="margin-top: 8.5px;">
                        <span style=" width:75px;" id="order_debut_prets"><?= $this->lng['preteur-operations-detail']['titre-debut'] ?>&nbsp;<i class="icon-arrows"></i></span>
                        <span style=" width:79px;" id="order_prochaine_prets"><?= $this->lng['preteur-operations-detail']['titre-prochaine'] ?> &nbsp;<i class="icon-arrows"></i></span>
                        <span style=" width:75px;" id="order_fin_prets"><?= $this->lng['preteur-operations-detail']['titre-fin'] ?>&nbsp;<i class="icon-arrows"></i></span>
                    </div>
                </div>
            </th>
            <th class="col6" id="order_mensualite_prets">
                <div class="th-wrap">
                    <i title="<?= $this->lng['preteur-operations-detail']['info-titre-mensualite'] ?>" class="icon-bank tooltip-anchor"></i>
                    <div class="title-ope"><?= $this->lng['preteur-operations-detail']['titre-mensualite'] ?>&nbsp;<i class="icon-arrows"></i></div>
                </div>
            </th>
            <th>
                <div class="th-wrap"><i title="<?= $this->lng['preteur-operations-detail']['info-contrat'] ?>" class="icon-arrow-next tooltip-anchor"></i></div>
            </th>
        </tr>
        <?php

        if ($this->lSumLoans != false) {
            $i = 1;
            foreach ($this->lSumLoans as $k => $l) {
                $Le_projects = $this->loadData('projects');
                $Le_projects->get($l['id_project']);

                $this->projects_status->getLastStatut($l['id_project']);

                //si un seul loan sur le projet
                if ($l['nb_loan'] == 1) {
                    ?>
                    <tr class="<?= ($i % 2 == 1 ? '' : 'odd') ?>">
                        <td>
                            <div class="description">
                                <h5><a href="<?= $this->lurl ?>/projects/detail/<?= $Le_projects->slug ?>"
                                       target="_blank"><?= $l['name'] ?></a></h5>
                                <h6><?= $l['city'] ?>, <?= $l['zip'] ?></h6>
                            </div>
                        </td>
                        <td>
                            <div class="cadreEtoiles">
<!--                                <div class="etoile --><?//= $this->lNotes[$l['risk']]?><!--"></div>-->
                                <div><?=$l['project_status']?></div>
                            </div>
                        </td>
                        <td style="white-space: nowrap;"><?= number_format($l['amount'], 2, ',', ' ') ?> €</td>
                        <td style="white-space: nowrap;"><?= number_format($l['rate'], 2, ',', ' ') ?> %</td>
                        <?php
                        if($l['project_status'] == projects_status::REMBOURSEMENT_ANTICIPE){
                            ?>
                            <td colspan="2">
                                <span class="calandar-ech" style="width: 79px;"><?= $this->dates->formatDate($l['debut'], 'd/m/Y') ?></span>
                                <span class="calandar-ech" style="width: 237px;"><p>Remboursé intégralement <br /> le <?= $this->dates->formatDate($l['status_change'], 'd/m/Y')?></p></span>
                            </td>
                            <?
                        } else {?>
                            <td><span class="calandar-ech"><?= $this->dates->formatDate($l['debut'], 'd/m/Y') ?></span>
                                <span class="calandar-ech"><?= $this->dates->formatDate($l['next_echeance'], 'd/m/Y') ?></span>
                                <span class="calandar-ech"><?= $this->dates->formatDate($l['fin'], 'd/m/Y') ?></span>
                            </td>
                            <td><?= number_format($l['mensuel'], 2, ',', ' ') ?> <?= $this->lng['preteur-operations-detail']['euros-par-mois'] ?></td>
                        <?}
                        ?>
                        <td>
                            <?
                            if ($this->projects_status->status >= 80) {

                                ?>
                                <a href="<?= $this->lurl . '/pdf/contrat/' . $this->clients->hash . '/' . $l['id_loan_if_one_loan'] ?>"><img
                                        src="<?= $this->surl ?>/styles/default/images/pdf50.png"
                                        class="btn-detailLoans_<?= $k ?>" style="margin-right: 20px;"/></a>
                                <?php

                            }
                            ?>
                        </td>
                    </tr>
                    <?php
                    // Debut Déclaration de créances //
                    if (in_array($l['id_project'], $this->arrayDeclarationCreance)) {

                        $i++;
                        ?>
                        <tr class="<?= ($i % 2 == 1 ? '' : 'odd') ?>">
                            <td>
                                <div class="description">
                                    <h5><a href="<?= $this->lurl ?>/projects/detail/<?= $Le_projects->slug ?>"
                                           target="_blank"><?= $l['name'] ?></a></h5>
                                    <h6><?= $l['city'] ?>, <?= $l['zip'] ?></h6>
                                </div>
                            </td>
                            <td>
                                <div class="cadreEtoiles">
                                    <div class="etoile <?= $this->lNotes[$l['risk']] ?>"></div>
                                </div>
                            </td>
                            <td style="white-space: nowrap;"><?= number_format($l['amount'], 2, ',', ' ') ?> €</td>
                            <td style="white-space: nowrap;"><?= number_format($l['rate'], 2, ',', ' ') ?> %</td>
                            <td>
                                <span class="calandar-ech"><?= $this->dates->formatDate($l['debut'], 'd/m/Y') ?></span>
                                <span
                                    class="calandar-ech"><?= $this->dates->formatDate($l['next_echeance'], 'd/m/Y') ?></span>
                                <span class="calandar-ech"><?= $this->dates->formatDate($l['fin'], 'd/m/Y') ?></span>
                            </td>
                            <td><?= number_format($l['mensuel'], 2, ',', ' ') ?> <?= $this->lng['preteur-operations-detail']['euros-par-mois'] ?></td>
                            <td style="">
                                <a style="vertical-align: middle;font-size: 10px;"
                                   href="<?= $this->lurl . '/pdf/declaration_de_creances/' . $this->clients->hash . '/' . $l['id_loan_if_one_loan'] ?>"
                                   class="btn btn-info btn-small multi"><?= $this->lng['preteur-operations-detail']['declaration-de-creances'] ?></a>

                            </td>
                        </tr>
                        <?php
                    }
                    // Fin Déclaration de créances //

                    $i++;
                } // Si plus
                else {
                    ?>
                    <tr class="<?= ($i % 2 == 1 ? '' : 'odd') ?>">
                        <td>
                            <div class="description">
                                <h5><a href="<?= $this->lurl ?>/projects/detail/<?= $Le_projects->slug ?>"
                                       target="_blank"><?= $l['name'] ?></a></h5>
                                <h6><?= $l['city'] ?>, <?= $l['zip'] ?></h6>
                            </div>
                        </td>
                        <td>
                            <div class="cadreEtoiles">
                                <div class="etoile <?= $this->lNotes[$l['risk']] ?>"></div>
                            </div>
                        </td>
                        <td style="white-space: nowrap;"><?= number_format($l['amount'], 2, ',', ' ') ?> €</td>
                        <td style="white-space: nowrap;"><?= number_format($l['rate'], 2, ',', ' ') ?> %</td>
                        <td>
                            <?php
                            if($l['project_status'] == projects_status::REMBOURSEMENT_ANTICIPE){
                            ?>
                        <td colspan="2">
                            <span class="calandar-ech" style="width: 79px;"><?= $this->dates->formatDate($l['debut'], 'd/m/Y') ?></span>
                            <span class="calandar-ech" style="width: 237px; "><p>Remboursé intégralement <br /> le <?= $this->dates->formatDate($l['status_change'], 'd/m/Y')?></p></span>
                        </td>
                        <?
                        } else {?>
                            <td><span class="calandar-ech"><?= $this->dates->formatDate($l['debut'], 'd/m/Y') ?></span>
                                <span class="calandar-ech"><?= $this->dates->formatDate($l['next_echeance'], 'd/m/Y') ?></span>
                                <span class="calandar-ech"><?= $this->dates->formatDate($l['fin'], 'd/m/Y') ?></span>
                            </td>
                            <td><?= number_format($l['mensuel'], 2, ',', ' ') ?> <?= $this->lng['preteur-operations-detail']['euros-par-mois'] ?></td>
                        <?}
                        ?>
                        <td>
                            <?php /*?><a class="btn btn-info btn-small btn-detailLoans_<?=$k?>"><?=$this->lng['profile']['details']?></a><?php */
                            ?>
                            <?php /*?><a class="btn btn-info btn-small btn-detailLoans_<?=$k?>" style="line-height: 27px; padding: 0px 7px 2px 7px; height:20px; width: 9px; ">+</a><?php */
                            ?>
                            <img src="<?= $this->surl ?>/styles/default/images/pdf50.png"
                                 class="btn-detailLoans_<?= $k ?>"/>
                            <a class="btn btn-small btn-detailLoans_<?= $k ?> override_plus">+</a>
                        </td>
                    </tr>

                    <tr class="<?= ($i % 2 == 1 ? '' : 'odd') ?>">
                        <td colspan="7" style="padding:0px;">
                            <div class="detailLoans loans_<?= $k ?>" style="display:none;">
                                <table class="table" style="margin-bottom:0px;">
                                    <?
                                    $a          = 0;
                                    $listeLoans = $this->loans->select('id_lender = ' . $this->lenders_accounts->id_lender_account . ' AND id_project = ' . $l['id_project']);
                                    foreach ($listeLoans as $loan) {

                                        $SumAremb = $this->echeanciers->select('id_loan = ' . $loan['id_loan'] . ' AND status = 0', 'ordre ASC', 0, 1);

                                        $fiscal = $SumAremb[0]['prelevements_obligatoires'] + $SumAremb[0]['retenues_source'] + $SumAremb[0]['csg'] + $SumAremb[0]['prelevements_sociaux'] + $SumAremb[0]['contributions_additionnelles'] + $SumAremb[0]['prelevements_solidarite'] + $SumAremb[0]['crds'];

                                        $b = $a + 1;
                                        ?>

                                        <tr>
                                            <td class="col1"></td>
                                            <td class="col2"></td>
                                            <td class="col3"
                                                style="white-space: nowrap;"><?= number_format($loan['amount'] / 100, 0, ',', ' ') ?>
                                                €
                                            </td>
                                            <td class="col4"
                                                style="white-space: nowrap;"><?= number_format($loan['rate'], 2, ',', ' ') ?>
                                                %
                                            </td>
                                            <td class="col5"></td>
                                            <td class="col6"
                                                style="white-space: nowrap;"><?= number_format(($SumAremb[0]['montant'] / 100) - $fiscal, 2, ',', ' ') ?> <?= $this->lng['preteur-operations-detail']['euros-par-mois'] ?></td>
                                            <td>
                                                <?
                                                if ($this->projects_status->status >= 80) {
                                                    ?>
                                                    <a class="tooltip-anchor icon-pdf"
                                                       href="<?= $this->lurl . '/pdf/contrat/' . $this->clients->hash . '/' . $loan['id_loan'] ?>"></a>
                                                    <?php
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                        <?php

                                        $a++;
                                    }
                                    ?>
                                </table>
                            </div>
                            <script type="text/javascript">
                                $(".btn-detailLoans_<?=$k?>").click(function () {
                                    $(".loans_<?=$k?>").slideToggle();

                                    if ($(".btn-detailLoans_<?=$k?>").hasClass("on_display")) {
                                        $(".btn-detailLoans_<?=$k?>").html('+');

                                        $(".btn-detailLoans_<?=$k?>").addClass("off_display");
                                        $(".btn-detailLoans_<?=$k?>").removeClass("on_display");
                                    }
                                    else {
                                        $(".btn-detailLoans_<?=$k?>").html('-');

                                        $(".btn-detailLoans_<?=$k?>").addClass("on_display");
                                        $(".btn-detailLoans_<?=$k?>").removeClass("off_display");
                                    }

                                });
                            </script>
                        </td>
                    </tr>
                    <?
                    // Début Déclaration de créance //
                    if (in_array($l['id_project'], $this->arrayDeclarationCreance)) {
                        $i++;
                        ?>
                        <tr class="<?= ($i % 2 == 1 ? '' : 'odd') ?> content_declaration_creances">
                            <td>
                                <div class="description">
                                    <h5><a href="<?= $this->lurl ?>/projects/detail/<?= $Le_projects->slug ?>"
                                           target="_blank"><?= $l['name'] ?></a></h5>
                                    <h6><?= $l['city'] ?>, <?= $l['zip'] ?></h6>
                                </div>
                            </td>
                            <td>
                                <div class="cadreEtoiles">
                                    <div class="etoile <?= $this->lNotes[$l['risk']] ?>"></div>
                                </div>
                            </td>
                            <td style="white-space: nowrap;"><?= number_format($l['amount'], 2, ',', ' ') ?> €</td>
                            <td style="white-space: nowrap;"><?= number_format($l['rate'], 2, ',', ' ') ?> %</td>
                            <td>
                                <span class="calandar-ech"><?= $this->dates->formatDate($l['debut'], 'd/m/Y') ?></span>
                                <span
                                    class="calandar-ech"><?= $this->dates->formatDate($l['next_echeance'], 'd/m/Y') ?></span>
                                <span class="calandar-ech"><?= $this->dates->formatDate($l['fin'], 'd/m/Y') ?></span>
                            </td>
                            <td><?= number_format($l['mensuel'], 2, ',', ' ') ?> <?= $this->lng['preteur-operations-detail']['euros-par-mois'] ?></td>
                            <?php /*?><a class="btn btn-info btn-small btn-detailLoans_<?=$k?>"><?=$this->lng['profile']['details']?></a><?php */
                            ?>
                            <?php /*?><a class="btn btn-info btn-small btn-detailLoans_<?=$k?>" style="line-height: 27px; padding: 0px 7px 2px 7px; height:20px; width: 9px; ">+</a><?php */
                            ?>

                            <td>


                                <a class="btn btn-info btn-small btn-detailLoans_declaration_creances_<?= $k ?> override_plus override_plus_<?= $k ?>"
                                   style="float:right;margin-right: 15px;">+</a><br/><br/>
                                <a style="font-size: 10px;vertical-align: middle;margin-right: 13px;"
                                   class="btn-detailLoans_declaration_creances_<?= $k ?> btn-grise btn-warning btn btn-info btn-small multi"><?= $this->lng['preteur-operations-detail']['declaration-de-creances'] ?></a>

                            </td>
                        </tr>

                        <tr class="<?= ($i % 2 == 1 ? '' : 'odd') ?>">
                            <td colspan="7" style="padding:0px;">
                                <div class="detailLoans_declaration_creances loans_declaration_creances_<?= $k ?>"
                                     style="display:none;">
                                    <table class="table" style="margin-bottom:0px;">
                                        <?
                                        $a          = 0;
                                        $listeLoans = $this->loans->select('id_lender = ' . $this->lenders_accounts->id_lender_account . ' AND id_project = ' . $l['id_project']);
                                        foreach ($listeLoans as $loan) {

                                            $SumAremb = $this->echeanciers->select('id_loan = ' . $loan['id_loan'] . ' AND status = 0', 'ordre ASC', 0, 1);

                                            $fiscal = $SumAremb[0]['prelevements_obligatoires'] + $SumAremb[0]['retenues_source'] + $SumAremb[0]['csg'] + $SumAremb[0]['prelevements_sociaux'] + $SumAremb[0]['contributions_additionnelles'] + $SumAremb[0]['prelevements_solidarite'] + $SumAremb[0]['crds'];

                                            $b = $a + 1;
                                            ?>

                                            <tr>
                                                <td class="col1"></td>
                                                <td class="col2"></td>
                                                <td class="col3"
                                                    style="white-space: nowrap;"><?= number_format($loan['amount'] / 100, 0, ',', ' ') ?>
                                                    €
                                                </td>
                                                <td class="col4"
                                                    style="white-space: nowrap;"><?= number_format($loan['rate'], 2, ',', ' ') ?>
                                                    %
                                                </td>
                                                <td class="col5"></td>
                                                <td class="col6"
                                                    style="white-space: nowrap;"><?= number_format(($SumAremb[0]['montant'] / 100) - $fiscal, 2, ',', ' ') ?> <?= $this->lng['preteur-operations-detail']['euros-par-mois'] ?></td>
                                                <td style="padding-top:5px;">
                                                    <?
                                                    if ($this->projects_status->status >= 80) {
                                                        ?><a
                                                        style="font-size:9px;margin-left: 14px;margin-right: 6px;  vertical-align: middle;"
                                                        class="btn btn-info btn-small multi"
                                                        href="<?= $this->lurl . '/pdf/declaration_de_creances/' . $this->clients->hash . '/' . $loan['id_loan'] ?>"><?= $this->lng['preteur-operations-detail']['declaration-de-creances'] ?></a><?php
                                                    }
                                                    ?>
                                                </td>
                                            </tr>

                                            <?php
                                            $a++;
                                        }
                                        ?>
                                    </table>
                                </div>
                                <script type="text/javascript">
                                    $(".btn-detailLoans_declaration_creances_<?=$k?>").click(function () {
                                        $(".loans_declaration_creances_<?=$k?>").slideToggle();

                                        if ($(".btn-detailLoans_declaration_creances_<?=$k?>").hasClass("on_display")) {
                                            $(".override_plus_<?=$k?>").html('+');

                                            $(".btn-detailLoans_declaration_creances_<?=$k?>").addClass("off_display");
                                            $(".btn-detailLoans_declaration_creances_<?=$k?>").removeClass("on_display");
                                        }
                                        else {
                                            $(".override_plus_<?=$k?>").html('-');

                                            $(".btn-detailLoans_declaration_creances_<?=$k?>").addClass("on_display");
                                            $(".btn-detailLoans_declaration_creances_<?=$k?>").removeClass("off_display");
                                        }
                                    });
                                </script>
                            </td>
                        </tr>
                        <?
                    }
                    // Fin Déclaration de créance //
                    $i++;
                }
            }
        }
        ?>
    </table>
</div>

<script type="text/javascript">
    $("input,select").change(function () {
        $(".load").fadeIn();
        var val = {
            order: "",
            type: "",
            annee: $("#anneeDetailPret").val()
        };

        $.post(add_url + "/ajax/detail_op", val).done(function (data) {
            if (data != 'nok') {
                $(".loadDetailOp").html(data);
                $(".load").fadeOut();
            }
        });
    });

    $("#order_titre_prets, #order_note_prets, #order_montant_prets, #order_interet_prets, #order_debut_prets, #order_prochaine_prets, #order_fin_prets, #order_mensualite_prets,input,select").click(function () {
        if ($(this).attr('id') == 'order_titre_prets' ) {
            var type = 'order_titre_prets' ;

            if ($("#order_titre_prets. asc").length) {
                var order = 'desc';
            }
            else {
                var order = 'asc';
            }
        }
        else if ($(this).attr('id') == 'order_note_prets') {
            var type = 'order_note_prets';

            if ($("#order_note_prets.asc").length) {
                var order = 'desc';
            }
            else {
                var order = 'asc';
            }
        }
        else if ($(this).attr('id') == 'order_montant_prets') {
            var type = 'order_montant_prets';

            if ($("#order_montant_prets.asc").length) {
                var order = 'desc';
            }
            else {
                var order = 'asc';
            }
        }
        else if ($(this).attr('id') == 'order_interet_prets') {
            var type = 'order_interet_prets';

            if ($("#order_interet_prets.asc").length) {
                var order = 'desc';
            }
            else {
                var order = 'asc';
            }
        }
        else if ($(this).attr('id') == 'order_debut_prets') {
            var type = 'order_debut_prets';

            if ($("#order_debut_prets.asc").length) {
                var order = 'desc';
            }
            else {
                var order = 'asc';
            }
        }
        else if ($(this).attr('id') == 'order_prochaine_prets') {
            var type = 'order_prochaine_prets';

            if ($("#order_prochaine_prets.asc").length) {
                var order = 'desc';
            }
            else {
                var order = 'asc';
            }
        }
        else if ($(this).attr('id') == 'order_fin_prets') {
            var type = 'order_fin_prets';

            if ($("#order_fin_prets.asc").length) {
                var order = 'desc';
            }
            else {
                var order = 'asc';
            }
        }
        else if ($(this).attr('id') == 'order_mensualite_prets') {
            var type = 'order_mensualite_prets';

            if ($("#order_mensualite_prets.asc").length) {
                var order = 'desc';
            }
            else {
                var order = 'asc';
            }
        }

        $(".load").fadeIn();

        var val = {
            order: order,
            type: type,
            annee: $("#anneeDetailPret").val()
        }

        $.post(add_url + "/ajax/detail_op", val).done(function (data) {
            if (data != 'nok') {
                $(".loadDetailOp").html(data);
                $(".load").fadeOut();
            }
        });
    });
</script>
