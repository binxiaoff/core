<?php use Unilend\Bundle\CoreBusinessBundle\Entity\Virements; ?>
<style type="text/css">
    table.tablesorter tbody td.grisfonceBG, .grisfonceBG {
        background: #D2D2D2;
        text-align: right;
    }

    input[type=text].numbers {
        text-align: right;
    }

    .project-main {
        width: 100%;
    }

    .project-main > tbody > tr > td {
        padding: 10px 20px;
        width: 50%;
    }

    .project-main td.left-column {
        border-right: 4px solid #b20066;
        padding-left: 0;
    }

    .project-main td.right-column {
        padding-right: 0;
    }

    .project-main .form th {
        width: 200px;
        white-space: nowrap;
    }

    .project-main .input_large,
    .project-main .select {
        width: 340px;
    }

    .lanote {
        color: #5591EC;
        font-size: 17px;
        font-weight: bold;
    }

    .hidden-fields {
        display: none;
    }

    #problematic_status_error {
        display: none;
        text-align: center;
        font-size: 16px;
        font-weight: bold;
        color: #f00;
    }

    .tab_content {
        border: 2px solid #b20066;
        display: none;
        padding: 10px;
    }

    .tab_content.expand {
        display: block;
    }

    .tab_content .btnDroite {
        margin: 10px 0 0 0;
    }

    .tab_title {
        display: block;
        background-color: #b20066;
        color: #fff;
        font-size: 16px;
        font-weight: bold;
        text-align: center;
        text-decoration: none;
        padding: 5px;
        margin-top: 15px;
    }

    .tab_title:active,
    .tab_title:focus,
    .tab_title:hover,
    .tab_title:visited {
        color: #fff;
        text-decoration: none;
    }

    #contenu_etape4_1 > table td.warning {
        background-color: #b20066;
        color: #fff;
    }

    table.form .section-title th {
        font-size: 14px;
        text-align: center;
    }

    table.annual-accounts td:first-child {
        text-align: left;
    }

    table.annual-accounts td:not(:first-child) {
        text-align: right;
    }

    table.annual-accounts .sub-total td {
        background-color: #d2d2d2;
        font-weight: bold;
    }

    .error_etape,
    .valid_etape {
        display: none;
        text-align: center;
        font-size: 16px;
        font-weight: bold;
        color: #009933;
    }

    .error_etape {
        color: #f00;
    }

    .annual_accounts_dates,
    .company_projects {
        cursor: pointer;
    }

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

    .btn-small {
        border-color: transparent;
        font-size: 11px;
        margin: 2px;
    }

    .btn-small.btnDisabled {
        border-color: #b20066;
    }

    .btn-validate {
        background: #009933;
    }

    .btn-reject {
        background: #cc0000;
    }

    .abandon-popup,
    .postpone-popup,
    .publish-popup,
    .comity-to-analysis-popup {
        width: 600px;
    }

    .takeover-popup {
        width: 400px;
    }
</style>
<script>
    function deleteWordingli(id) {
        var id_delete = id;
        var id_input = id.replace("delete", "input");
        $("#" + id_delete).remove();
        $("#" + id_input).remove();
    }

    $(function () {
        $('.tooltip').tooltip();

        $.datepicker.setDefaults($.extend({showMonthAfterYear: false}, $.datepicker.regional['fr']));

        $("#date").datepicker({
            showOn: 'both',
            buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            yearRange: '<?= (date('Y') - 10) ?>:<?= (date('Y') + 10) ?>'
        });

        $("#date_publication").datepicker({
            showOn: 'both',
            buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            minDate: 0
        });

        $("#date_retrait").datepicker({
            showOn: 'both',
            buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            minDate: 0
        });

        $("#date_ps").datepicker({
            showOn: 'both',
            buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true
        });

        $("#date_rj").datepicker({
            showOn: 'both',
            buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true
        });

        $("#date_lj").datepicker({
            showOn: 'both',
            buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true
        });

        <?php if ($this->nb_lignes != '') : ?>
        $('.tablesorter').tablesorterPager({
            container: $('#pager'),
            positionFixed: false,
            size: <?= $this->nb_lignes ?>
        })
        <?php endif; ?>

        $('body').on('change', '#partner', function () {
            var partnerId = $(this).find('option:selected').val()

            if (partnerId !== '') {
                $.ajax({
                    type: 'POST',
                    url: '<?= $this->lurl ?>/dossiers/partner_products/<?= $this->projects->id_project ?>/' + partnerId,
                    dataType: 'json',
                    success: function (products) {
                        $('#product').find('option').remove().end().append('<option value=""></option>')
                        $.each(products, function (index, product) {
                            $('#product').append('<option value="' + product.id + '">' + product.label + '</option>')
                        })
                    }
                })
            }
        })

        $(document).click(function (event) {
            var $clicked = $(event.target)
            if ($clicked.hasClass('tab_title')) {
                $clicked.next().slideToggle()
            }
        });

        if ($(location.hash) && $(location.hash).hasClass('tab_title')) {
            $(location.hash).next('.tab_content').addClass('expand')
        }

        window.onhashchange = function () {
            if ($(location.hash) && $(location.hash).hasClass('tab_title')) {
                $(location.hash).next('.tab_content').addClass('expand')
                $(location.hash).scrollTop()
            }
        }

        $('.regenerate-dirs').click(function (event) {
            if (!confirm('Vous allez régénéré le DIRS avec les nouvelles informations')) {
                event.preventDefault()
                return false
            }
            return true
        });

        $('#status').change(function () {
            var status = $(this).val();

            if (
                status == <?= \projects_status::PROBLEME ?>
                || status == <?= \projects_status::PROBLEME_J_X ?>
                || status == <?= \projects_status::RECOUVREMENT ?>
                || status == <?= \projects_status::PROCEDURE_SAUVEGARDE ?>
                || status == <?= \projects_status::REDRESSEMENT_JUDICIAIRE ?>
                || status == <?= \projects_status::LIQUIDATION_JUDICIAIRE ?>
                || status == <?= \projects_status::DEFAUT ?>
            ) {
                $.colorbox({href: "<?= $this->lurl ?>/thickbox/project_status_update/<?= $this->projects->id_project ?>/" + status});
            }
        });

        $("#dossier_resume").submit(function (event) {
            if ($("#statut_encours").val() == '0') {
                var check_ok = true;

                if ($('input[name=mail_a_envoyer_preteur_probleme]:checked', '#dossier_resume').val() == '0') {
                    check_ok = false;
                } else if ($('input[name=mail_a_envoyer_preteur_problemeX]:checked', '#dossier_resume').val() == '0') {
                    check_ok = false;
                } else if ($('input[name=mail_a_envoyer_preteur_probleme_recouvrement]:checked', '#dossier_resume').val() == '0') {
                    check_ok = false;
                } else if ($('input[name=mail_a_envoyer_preteur_ps]:checked', '#dossier_resume').val() == '0') {
                    check_ok = false;
                } else if ($('input[name=mail_a_envoyer_preteur_rj]:checked', '#dossier_resume').val() == '0') {
                    check_ok = false;
                } else if ($('input[name=mail_a_envoyer_preteur_lj]:checked', '#dossier_resume').val() == '0') {
                    check_ok = false;
                } else if ($('input[name=mail_a_envoyer_preteur_default]:checked', '#dossier_resume').val() == '0') {
                    check_ok = false;
                }
                // On a un envoi de mail selected qu'on doit confirmer
                if (check_ok == false && $('#check_confirmation_send_email').val() == '0') {
                    $(".confirmation_send_email").click();
                    event.preventDefault();
                } else {
                    $("#statut_encours").val('1');
                    $(".submitdossier").remove();
                }
            } else {
                event.preventDefault();
            }
        });

        $('.icon_remove_attachment').click(function (e) {
            e.preventDefault();
            var id = $(this).data('id');
            var type = $(this).data('label');
            var response = confirm("Voulez-vous supprimer " + type + "?");
            if (response == true) {
                $.ajax({
                    url: "<?= $this->lurl ?>/dossiers/remove_file",
                    dataType: 'json',
                    type: 'POST',
                    data: {
                        attachment_id: id
                    },
                    error: function () {
                        alert('An error has occurred');
                    },
                    success: function (data) {
                        if (false === $.isEmptyObject(data)) {
                            $.each(data, function (fileId, value) {
                                if ('ok' == value) {
                                    $("#statut_fichier_id_" + fileId).html('Supprimé');
                                    $(this).remove;
                                    $("#statut_fichier_id_" + fileId).parent().find('.label_col').html('');
                                    $("#statut_fichier_id_" + fileId).parent().find('.remove_col').html('');
                                }

                            });
                            $("#valid_etape5").slideDown();
                            setTimeout(function () {
                                $("#valid_etape5").slideUp();
                            }, 4000);
                        } else {
                            alert('An error has occurred');
                        }
                    }
                });
            }
        });

        $(".add_wording").click(function (e) {
            e.preventDefault();
            var id = $(this).attr("id");
            var content = $(".content-" + id).html();
            if ($("#input-" + id).length == 0) {
                var champ = "<input class=\"input_li\" type=\"text\" value=\"" + content + "\" name=\"input-" + id + "\" id=\"input-" + id + "\">";
                var clickdelete = '<a onclick="deleteWordingli(this.id)" class="delete_wording" id="delete-' + id + '"><img src="' + add_surl + '/images/admin/delete.png" ></a>';
                $('.content_li_wording').append(champ + clickdelete);
            }
        });

        $("#completude_preview").click(function () {
            var content = $("#content_email_completude").val();
            var list = '';
            $(".input_li").each(function () {
                list = list + "<li>" + $(this).val() + "</li>";
            });

            $.post(
                add_url + "/ajax/session_project_completude",
                {
                    id_project: "<?= $this->projects->id_project ?>",
                    content: content,
                    list: list
                }
            ).done(function (data) {
                if (data != 'nok') {
                    $("#send_completeness").get(0).click();
                }
            });
        });

        <?php if (\projects_status::NOT_ELIGIBLE != $this->projects->status) : ?>
        $('#commercial').change(function () {
            if ($(this).val() > 0 && $('#current_commercial').val() == 0) {
                $(this).parents('form').submit()
            }
        })
        <?php endif; ?>

        $('#analyste').change(function () {
            if ($(this).val() > 0 && $('#current_analyst').val() == 0) {
                $(this).parents('form').submit()
            }
        })
    });
</script>
<script type="text/javascript" src="<?= $this->url ?>/ckeditor/ckeditor.js"></script>
<div id="contenu">
    <?php if (false === empty($this->projects->title)) : ?><h1><?= $this->projects->title ?></h1><?php endif; ?>
    <form method="post" name="dossier_resume" id="dossier_resume" enctype="multipart/form-data" action="<?= $this->lurl ?>/dossiers/edit/<?= $this->params[0] ?>" target="_parent">
        <table class="project-main">
            <tbody>
            <tr>
                <td class="left-column">
                    <h2>Identité</h2>
                    <table class="form project-identity">
                        <?php if ($this->projects->status >= \projects_status::A_FUNDER) : ?>
                            <tr>
                                <th>Lien projet</th>
                                <td><a href="<?= $this->furl ?>/projects/detail/<?= $this->projects->slug ?>" target="_blank"><?= $this->furl ?>/projects/detail/<?= $this->projects->slug ?></a></td>
                            </tr>
                        <?php endif; ?>
                        <tr>
                            <th>Date de la demande</th>
                            <td><?= $this->dates->formatDate($this->projects->added, 'd/m/Y H:i') ?></td>
                        </tr>
                        <tr>
                            <th><label for="source">Source</label></th>
                            <td>
                                <?php if ($this->projects->create_bo && empty($this->clients->source)) : ?>
                                    <select id="source" name="source" class="select">
                                        <option value=""></option>
                                        <?php foreach ($this->sources as $source) : ?>
                                            <option value="<?= stripslashes($source) ?>"<?= $this->clients->source === $source ? ' selected' : '' ?>><?= $source ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php else : ?>
                                    <?= empty($this->clients->source) ? '-' : $this->clients->source ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>SIREN</th>
                            <td><?= $this->companies->siren ?><?php if ($this->isTakeover) : ?> - <?= $this->companies->name ?><?php endif; ?></td>
                        </tr>
                        <?php if ($this->isTakeover) : ?>
                            <tr>
                                <th style="position: relative;">
                                    <label for="target_siren" class="tooltip" title="SIREN de la société reprise/rachetée">SIREN cible</label>
                                    <?php if ($this->projects->status < \projects_status::A_FUNDER) : ?>
                                        <a href="<?= $this->lurl ?>/dossiers/takeover/<?= $this->projects->id_project ?>/swap" style="position: absolute; top: -15px; left: 100px;">
                                            <img src="<?= $this->surl ?>/images/admin/swap.png" alt="Inverser" height="19">
                                        </a>
                                    <?php endif; ?>
                                </th>
                                <td>
                                    <?php if (false === empty($this->targetCompany->id_company)) : ?>
                                        <?= $this->targetCompany->siren ?> - <?= $this->targetCompany->name ?>
                                    <?php else : ?>
                                        <a href="<?= $this->lurl ?>/dossiers/takeover/<?= $this->projects->id_project ?>" class="btn btn-small btn_link thickbox" title="Définir la cible">Définir</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <tr>
                            <th>Code NAF</th>
                            <td>
                                <?php if (empty($this->companies->code_naf)) : ?>
                                    -
                                <?php else : ?>
                                    <?= $this->companies->code_naf ?>
                                    <?php if (false === empty($this->xerfi->naf)) : ?>
                                        <?= ' - ' . $this->xerfi->label ?>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="sector">Secteur de la société</label></th>
                            <td>
                                <?php if (false === empty($this->companies->code_naf) && $this->companies->code_naf === \Unilend\Bundle\CoreBusinessBundle\Entity\Companies::NAF_CODE_NO_ACTIVITY) : ?>
                                    <select name="sector" id="sector" class="select">
                                        <option value="0"></option>
                                        <?php foreach ($this->sectors as $sector) : ?>
                                            <option<?= ($this->companies->sector == $sector['id_company_sector'] ? ' selected' : '') ?> value="<?= $sector['id_company_sector'] ?>">
                                                <?= $this->translator->trans('company-sector_sector-' . $sector['id_company_sector']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php elseif (empty($this->companies->sector)) : ?>
                                    -
                                <?php else : ?>
                                    <?= $this->translator->trans('company-sector_sector-' . $this->companies->sector) ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="title">Titre du projet</label></th>
                            <td><input type="text" name="title" id="title" class="input_large" value="<?= $this->projects->title ?>"></td>
                        </tr>
                        <tr>
                            <th><label for="societe">Raison sociale</label></th>
                            <td><input type="text" name="societe" id="societe" class="input_large" value="<?= $this->companies->name ?>"></td>
                        </tr>
                        <tr>
                            <th><label for="tribunal_com">Tribunal de commerce</label></th>
                            <td><input type="text" name="tribunal_com" id="tribunal_com" class="input_large" value="<?= $this->companies->tribunal_com ?>"></td>
                        </tr>
                        <tr>
                            <th><label for="activite">Activité</label></th>
                            <td><input type="text" name="activite" id="activite" class="input_large" value="<?= empty($this->companies->activite) ? (empty($this->xerfi->naf) ? '' : $this->xerfi->label) : $this->companies->activite ?>"></td>
                        </tr>
                    </table>
                    <br><br>
                    <h2>Projet</h2>
                    <table class="form project-attributes">
                        <tr>
                            <th><label for="montant">Montant du prêt&nbsp;*</label></th>
                            <td><input type="text" name="montant" id="montant" class="input_court"<?php if ($this->projects->status >= \projects_status::PREP_FUNDING) : ?> disabled<?php endif; ?> value="<?= empty($this->projects->amount) ? '' : $this->ficelle->formatNumber($this->projects->amount, 0) ?>"> €</td>
                        </tr>
                        <tr>
                            <th><label for="duree">Durée du prêt&nbsp;*</label></th>
                            <td>
                                <select name="duree" id="duree" class="select"<?php if ($this->projects->status >= \projects_status::PREP_FUNDING) : ?> disabled<?php endif; ?>>
                                    <option<?= (in_array($this->projects->period, [0, 1000000]) ? ' selected' : '') ?> value="0"></option>
                                    <?php foreach ($this->dureePossible as $duree) : ?>
                                        <option<?= ($this->projects->period == $duree ? ' selected' : '') ?> value="<?= $duree ?>"><?= $duree ?> mois</option>
                                    <?php endforeach ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="motive">Motif de l'emprunt</label></th>
                            <td>
                                <select name="motive" id="motive" class="select">
                                    <option<?= (is_null($this->projects->id_borrowing_motive) ? ' selected' : '') ?> value="0"></option>
                                    <?php foreach ($this->aBorrowingMotives as $motive) : ?>
                                        <option<?= ($this->projects->id_borrowing_motive == $motive['id_motive'] ? ' selected' : '') ?> value="<?= $motive['id_motive'] ?>"><?= $this->translator->trans('borrowing-motive_motive-' . $motive['id_motive']) ?></option>
                                    <?php endforeach ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="need">Type de besoin</label></th>
                            <td>
                                <select name="need" id="need" class="select"<?php if ($this->projects->status >= \projects_status::PREP_FUNDING) : ?> disabled<?php endif; ?>>
                                    <option value="0"></option>
                                    <?php foreach ($this->needs as $need) : ?>
                                        <optgroup label="<?= $need['label'] ?>">
                                            <?php foreach ($need['children'] as $needChild) : ?>
                                                <option value="<?= $needChild['id_project_need'] ?>"<?= ($this->projects->id_project_need == $needChild['id_project_need'] ? ' selected' : '') ?>><?= $needChild['label'] ?></option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="specific_commission_rate_funds">Commission déblocage spécifique</label></th>
                            <td>
                                <?php if (true === $this->isFundsCommissionRateEditable) : ?>
                                    <input type="text" name="specific_commission_rate_funds" id="specific_commission_rate_funds" class="input_court" value="<?= null === $this->projects->commission_rate_funds ? '' : $this->ficelle->formatNumber($this->projects->commission_rate_funds, 1) ?>"> %
                                <?php else : ?>
                                    <?= null === $this->projects->commission_rate_funds ? '-' : $this->ficelle->formatNumber($this->projects->commission_rate_funds, 1) . ' %' ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php if ($this->projects->status >= \projects_status::PREP_FUNDING) : ?>
                            <tr class="content_risk">
                                <th><label for="risk">Niveau de risque</label></th>
                                <td>
                                    <?php $stars = ['A' => '5', 'B' => '4,5', 'C' => '4', 'D' => '3,5', 'E' => '3', 'F' => '2,5', 'G' => '2', 'H' => '1,5']; ?>
                                    <?= $stars[$this->projects->risk] ?> étoiles
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php if (isset($this->rate_min, $this->rate_max)) : ?>
                            <tr>
                                <th><label for="project_rate"> Taux min / max</label></th>
                                <td><?= $this->rate_min ?> % - <?= $this->rate_max ?> %</td>
                            </tr>
                        <?php endif; ?>
                        <?php if (false === empty($this->fPredictAutoBid) && $this->projects->status < \projects_status::FUNDE) : ?>
                            <tr>
                                <th><label for="autobid_statistic">Financement Autolend</label></th>
                                <td><?= $this->ficelle->formatNumber($this->fPredictAutoBid, 0) ?> %</td>
                            </tr>
                        <?php endif; ?>
                    </table>
                </td>
                <td class="right-column">
                    <h2>Partenaire</h2>
                    <table class="form project-partner">
                        <tr>
                            <th><label for="partner">Partenaire *</label></th>
                            <td>
                                <select name="partner" id="partner" class="select"<?php if ($this->projects->status >= \projects_status::PREP_FUNDING) : ?> disabled<?php endif; ?>>
                                    <?php if (empty($this->projects->id_partner)) : ?>
                                        <option value="" selected></option>
                                    <?php endif; ?>
                                    <?php foreach ($this->partnerList as $partner) : ?>
                                        <option value="<?= $partner['id'] ?>"<?= $this->projects->id_partner === $partner['id'] ? ' selected' : '' ?>><?= $partner['name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                    <br><br>
                    <h2>Produit</h2>
                    <table class="form project-product">
                        <tr>
                            <th><label for="product">Produit associé&nbsp;*</label></th>
                            <td>
                                <select name="product" id="product" class="select"<?php if ($this->projects->status > \projects_status::PREP_FUNDING) : ?> disabled<?php endif; ?>>
                                    <?php if (empty($this->selectedProduct->id_product)) : ?>
                                        <option value="" selected></option>
                                    <?php endif; ?>
                                    <?php if (false === empty($this->selectedProduct->id_product) && false === in_array($this->selectedProduct, $this->eligibleProducts)) : ?>
                                        <option value="<?= $this->selectedProduct->id_product ?>" selected disabled>
                                            <?= $this->translator->trans('product_label_' . $this->selectedProduct->label) ?>
                                        </option>
                                    <?php endif; ?>
                                    <?php foreach ($this->eligibleProducts as $product) : ?>
                                        <option value="<?= $product->id_product ?>" <?= $this->projects->id_product == $product->id_product ? 'selected' : '' ?>>
                                            <?= $this->translator->trans('product_label_' . $product->label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th>Commission déblocage</th>
                            <td>
                                <?php if (false === empty($this->partnerProduct->commission_rate_funds)) : ?>
                                    <?= $this->ficelle->formatNumber($this->partnerProduct->commission_rate_funds, 1) ?> %
                                <?php else : ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Commission remboursement</th>
                            <td>
                                <?php if (false === empty($this->partnerProduct->commission_rate_repayment)) : ?>
                                    <?= $this->ficelle->formatNumber($this->partnerProduct->commission_rate_repayment, 1) ?> %
                                <?php else : ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php if (isset($this->availableContracts) && in_array(\underlying_contract::CONTRACT_MINIBON, $this->availableContracts)) : ?>
                            <tr>
                                <th>DIRS</th>
                                <td>
                                    <a href="<?= $this->furl ?>/var/dirs/<?= $this->projects->slug ?>.pdf">
                                        <img src="<?= $this->surl ?>/images/admin/pdf.png" alt="PDF">
                                    </a>
                                    <?php if ($this->projects->status >= \projects_status::EN_FUNDING) : ?>
                                        <a href="<?= $this->url ?>/dossiers/regenerate_dirs/<?= $this->projects->id_project ?>" class="regenerate-dirs thickbox">
                                            <img src="<?= $this->surl ?>/images/admin/reload.png" alt="Regenerate" title="Régénérer le DIRS">
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </table>
                    <br><br>
                    <?php if ($this->projects->status == \projects_status::REMBOURSEMENT) : ?>
                        <?php $this->fireView('early_repayment'); ?>
                        <br><br>
                    <?php endif; ?>
                    <h2>
                        Actions
                        <a href="<?= $this->surl ?>/images/admin/projects_workflow.png" class="thickbox">
                            <img src="<?= $this->surl ?>/images/admin/info.png" alt="Worflow statuts">
                        </a>
                    </h2>
                    <table class="form project-actions">
                        <tr>
                            <th>ID dossier</th>
                            <td><?= $this->projects->id_project ?></td>
                        </tr>
                        <tr>
                            <th>ID emprunteur</th>
                            <td>
                                <?= $this->clients->id_client ?>
                                <a href="<?= $this->lurl ?>/emprunteurs/edit/<?= $this->clients->id_client ?>"><img src="<?= $this->surl ?>/images/admin/edit.png" alt="Éditer l'emprunteur"></a>
                                <input id="id_client" type="hidden" value="<?= $this->clients->id_client ?>" name="id_client">
                            </td>
                        </tr>
                        <tr>
                            <th>Prénom</th>
                            <td><?= $this->clients->prenom ?></td>
                        </tr>
                        <tr>
                            <th>Nom</th>
                            <td><?= $this->clients->nom ?></td>
                        </tr>
                        <?php if (false === empty($this->projects->id_commercial) || false === in_array($this->projects->status, [\projects_status::IMPOSSIBLE_AUTO_EVALUATION, \projects_status::ABANDONED])) : ?>
                            <tr>
                                <th><label for="commercial">Commercial</label></th>
                                <td>
                                    <input type="hidden" id="current_commercial" value="<?= $this->projects->id_commercial ?>">
                                    <select name="commercial" id="commercial" class="select">
                                        <option value="0"></option>
                                        <?php foreach ($this->aSalesPersons as $salesperson) : ?>
                                            <option value="<?= $salesperson['id_user'] ?>"<?= ($this->projects->id_commercial == $salesperson['id_user'] ? ' selected' : '') ?>><?= $salesperson['firstname'] ?> <?= $salesperson['name'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php if ($this->projects->status >= \projects_status::PENDING_ANALYSIS || false === empty($this->projects->id_analyste)) : ?>
                            <tr>
                                <th><label for="analyste">Analyste</label></th>
                                <td>
                                    <input type="hidden" id="current_analyst" value="<?= $this->projects->id_analyste ?>">
                                    <select name="analyste" id="analyste" class="select">
                                        <option value="0"></option>
                                        <?php foreach ($this->aAnalysts as $analyst) : ?>
                                            <option value="<?= $analyst['id_user'] ?>"<?= ($this->projects->id_analyste == $analyst['id_user'] ? ' selected' : '') ?>><?= $analyst['firstname'] ?> <?= $analyst['name'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <tr>
                            <th><label for="status">Statut</label></th>
                            <td id="current_statut">
                                <input type="hidden" name="current_status" value="<?= $this->projects->status ?>">
                                <?php if ($this->projects->status <= \projects_status::EN_FUNDING || 0 === count($this->lProjects_status)) : // All statuses should be handled this way, i.e. by only using buttons to transition status ?>
                                    <!-- Useful for backward compatibility purpose. Should not be useful -->
                                    <input type="hidden" name="status" value="<?= $this->projects->status ?>">
                                    <?= $this->projects_status->label ?>
                                    <?php if (
                                        in_array($this->users->id_user_type, [\users_types::TYPE_ADMIN, \users_types::TYPE_RISK])
                                        && in_array($this->projects->status, [\projects_status::COMMERCIAL_REJECTION, \projects_status::ANALYSIS_REJECTION, \projects_status::COMITY_REJECTION])
                                    ) : ?>
                                        <a href="<?= $this->lurl ?>/dossiers/ajax_rejection/0/<?= $this->projects->id_project ?>" title="Modifier le motif de rejet" class="thickbox"><img src="<?= $this->surl ?>/images/admin/edit.png" alt="Modifier le motif de rejet"></a>
                                    <?php endif; ?>
                                <?php else : ?>
                                    <a href="<?= $this->lurl ?>/thickbox/popup_confirmation_send_email/<?= $this->projects->id_project ?>" class="thickbox confirmation_send_email"></a>
                                    <input type="hidden" name="check_confirmation_send_email" id="check_confirmation_send_email" value="0">
                                    <select name="status" id="status" class="select">
                                        <?php foreach ($this->lProjects_status as $s) : ?>
                                            <option <?= ($this->projects->status == $s['status'] ? 'selected' : '') ?> value="<?= $s['status'] ?>"><?= $s['label'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                                <a href="<?= $this->lurl ?>/thickbox/project_history/<?= $this->projects->id_project ?>" class="thickbox"><img src="<?= $this->surl ?>/images/admin/info.png" alt="Information"></a>
                            </td>
                        </tr>
                        <!-- Rejection/abandon reason -->
                        <?php if (false === empty($this->sRejectionReason) || in_array($this->projects->status, [\projects_status::NOT_ELIGIBLE, \projects_status::IMPOSSIBLE_AUTO_EVALUATION, \projects_status::ABANDONED]) && false === empty($this->projects_status_history->content)) : ?>
                            <tr>
                                <th><label for="status">Motif</label></th>
                                <td>
                                    <?php if (false === empty($this->sRejectionReason)) : ?>
                                        <?= $this->sRejectionReason ?>
                                    <?php elseif ($this->projects->status == \projects_status::ABANDONED) : ?>
                                        <?= $this->projects_status_history->content ?>
                                    <?php else : ?>
                                        <?= $this->rejectionReasonMessage ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php if ($this->projects->status == \projects_status::PREP_FUNDING) : ?>
                            <?php
                            $blockingPublishingError = '';

                            if (in_array($this->projects->period, [0, 1000000])) {
                                $blockingPublishingError = 'Veuillez sélectionner une durée de prêt';
                            }

                            if (in_array(\Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContract::CONTRACT_MINIBON, $this->availableContracts)) {
                                $hasDebtsStatement = false;
                                /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\ProjectAttachment $projectAttachment */
                                foreach ($this->aAttachments as $projectAttachment) {
                                    $attachment = $projectAttachment->getAttachment();
                                    if (\Unilend\Bundle\CoreBusinessBundle\Entity\AttachmentType::DEBTS_STATEMENT === $attachment->getType()->getId()) {
                                        $hasDebtsStatement = true;
                                        break;
                                    }
                                }
                                if (false === $hasDebtsStatement) {
                                    $blockingPublishingError = 'Veuillez charger l\'état des créances (nécessaire au DIRS)';
                                }
                            }

                            if (false === $this->isProductUsable) {
                                $blockingPublishingError = 'Le produit associé au projet n\'est plus disponible ou éligible. Veuillez sélectionner un autre produit.';
                            }
                            ?>
                            <?php if (false === empty($blockingPublishingError)) : ?>
                                <tr>
                                    <td colspan="2"><?= $blockingPublishingError ?></td>
                                </tr>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php if ($this->projects->status >= \projects_status::A_FUNDER) : ?>
                            <tr>
                                <th><label for="date_publication">Date de publication&nbsp;*</label></th>
                                <td>
                                    <?php if ($this->projects->status == \projects_status::A_FUNDER) : ?>
                                        <input type="text" name="date_publication" id="date_publication" class="input_dp" value="<?= ($this->projects->date_publication != '0000-00-00 00:00:00' ? $this->dates->formatDate($this->projects->date_publication, 'd/m/Y') : '') ?>">
                                        <select name="date_publication_heure" class="selectMini" title="Heure">
                                            <?php for ($hour = 0; $hour < 24; $hour++) : ?>
                                                <option value="<?= sprintf('%02d', $hour) ?>"<?= (substr($this->projects->date_publication, 11, 2) == $hour ? ' selected' : '') ?>><?= sprintf('%02d', $hour) ?></option>
                                            <?php endfor; ?>
                                        </select>&nbsp;h
                                        <select name="date_publication_minute" class="selectMini" title="Minute">
                                            <?php for ($minute = 0; $minute < 60; $minute += 5) : ?>
                                                <option value="<?= sprintf('%02d', $minute) ?>"<?= (substr($this->projects->date_publication, 14, 2) == $minute ? ' selected' : '') ?>><?= sprintf('%02d', $minute) ?></option>
                                            <?php endfor; ?>
                                        </select>&nbsp;m
                                    <?php else : ?>
                                        <?= $this->dates->formatDate($this->projects->date_publication, 'd/m/Y H:i') ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="date_retrait">Date de retrait&nbsp;*</label></th>
                                <td>
                                    <?php if ($this->projects->status == \projects_status::A_FUNDER) : ?>
                                        <input type="text" name="date_retrait" id="date_retrait" class="input_dp" value="<?= ($this->projects->date_retrait != '0000-00-00 00:00:00' ? $this->dates->formatDate($this->projects->date_retrait, 'd/m/Y') : '') ?>">
                                        <select name="date_retrait_heure" class="selectMini" title="Heure">
                                            <?php for ($hour = 0; $hour < 24; $hour++) : ?>
                                                <option value="<?= sprintf('%02d', $hour) ?>"<?= (substr($this->projects->date_retrait, 11, 2) == $hour ? ' selected' : '') ?>><?= sprintf('%02d', $hour) ?></option>
                                            <?php endfor; ?>
                                        </select>&nbsp;h
                                        <select name="date_retrait_minute" class="selectMini" title="Minute">
                                            <?php for ($minute = 0; $minute < 60; $minute += 5) : ?>
                                                <option value="<?= sprintf('%02d', $minute) ?>"<?= (substr($this->projects->date_retrait, 14, 2) == $minute ? ' selected' : '') ?>><?= sprintf('%02d', $minute) ?></option>
                                            <?php endfor; ?>
                                        </select>&nbsp;m
                                    <?php else : ?>
                                        <?= $this->dates->formatDate($this->projects->date_retrait, 'd/m/Y H:i') ?>
                                        <?php if ($this->projects->status < \projects_status::FUNDE) : ?>
                                            &nbsp;&nbsp;&nbsp;<a href="<?= $this->lurl ?>/thickbox/pop_up_edit_date_retrait/<?= $this->projects->id_project ?>" class="thickbox btn_link ">Modifier</a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php if (false === empty($_SESSION['public_dates_error'])) : ?>
                            <tr>
                                <td colspan="2" style="color:red; font-weight:bold;"><?= $_SESSION['public_dates_error'] ?></td>
                            </tr>
                            <?php unset($_SESSION['public_dates_error']); ?>
                        <?php endif; ?>
                        <?php if ($this->projects_pouvoir->get($this->projects->id_project, 'id_project') && $this->projects_pouvoir->status == 1) : ?>
                            <tr>
                                <th><label for="pouvoir">Pouvoir</label></th>
                                <td>
                                    <div>
                                        <a href="<?= $this->lurl ?>/protected/pouvoir_project/<?= $this->projects_pouvoir->name ?>"><?= $this->projects_pouvoir->name ?></a>
                                        <?php if ($this->projects_pouvoir->status_remb == '1') : ?>
                                            <span style="color:green;">&nbsp;Validé</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php elseif ($this->projects->status == \projects_status::FUNDE) : ?>
                            <tr>
                                <th><label for="upload_pouvoir">Pouvoir</label></th>
                                <td><input type="file" name="upload_pouvoir" id="upload_pouvoir"></td>
                            </tr>
                        <?php endif; ?>
                        <?php if ($this->projects->status == \projects_status::FUNDE) : ?>
                            <tr>
                                <th><label for="pret_refuse">Prêt refusé</label></th>
                                <td>
                                    <select name="pret_refuse" id="pret_refuse" class="select">
                                        <option value="0">Non</option>
                                        <option value="1">Oui</option>
                                    </select>
                                </td>
                            </tr>
                            <?php if (empty($this->proxy) || $this->proxy['status'] != \Unilend\Bundle\CoreBusinessBundle\Entity\UniversignEntityInterface::STATUS_SIGNED) : ?>
                                <tr>
                                    <th>Pouvoir</th>
                                    <td><a href="<?= $this->furl ?>/pdf/pouvoir/<?= $this->clients->hash ?>/<?= $this->projects->id_project ?>"><?= $this->furl ?>/pdf/pouvoir/<?= $this->clients->hash ?>/<?= $this->projects->id_project ?></a></td>
                                </tr>
                            <?php endif ?>
                            <?php if (empty($this->mandate) || $this->mandate['status'] != \Unilend\Bundle\CoreBusinessBundle\Entity\UniversignEntityInterface::STATUS_SIGNED) : ?>
                                <tr>
                                    <th>Mandat</th>
                                    <?php if ($this->validBankAccount) : ?>
                                        <td><a href="<?= $this->furl ?>/pdf/mandat/<?= $this->clients->hash ?>/<?= $this->projects->id_project ?>"><?= $this->furl ?>/pdf/mandat/<?= $this->clients->hash ?>/<?= $this->projects->id_project ?></a></td>
                                    <?php else : ?>
                                        <td>L'emprunteur n'a pas de RIB en vigueur.</td>
                                    <?php endif ?>
                                </tr>
                            <?php endif ?>
                        <?php endif; ?>
                        <tr>
                            <td colspan="2">
                                <?php switch ($this->projects->status) :
                                    case \projects_status::COMMERCIAL_REVIEW: ?>
                                        <div style="text-align: right">
                                            <a href="<?= $this->lurl ?>/dossiers/postpone/<?= $this->projects->id_project ?>" class="btn btn-small btnDisabled btn_link thickbox">Reporter</a>
                                            <a href="<?= $this->lurl ?>/dossiers/abandon/<?= $this->projects->id_project ?>" class="btn btn-small btnDisabled btn_link thickbox">Abandonner</a>
                                            <a href="<?= $this->lurl ?>/dossiers/ajax_rejection/1/<?= $this->projects->id_project ?>" class="btn btn-small btn-reject btn_link thickbox">Rejeter</a>
                                            <?php if (empty($this->projects->id_product)) : ?>
                                                <br><br>Pour passer le projet à l'étude risque, vous devez sélectionner un produit.
                                            <?php else : ?>
                                                <input type="button" id="status_dosier_valider" class="btn btn-small btn-validate" onclick="check_status_dossier(<?= \projects_status::PENDING_ANALYSIS ?>, <?= $this->projects->id_project ?>);" value="Passer à l'étude risque">
                                            <?php endif; ?>
                                        </div>
                                        <?php break;
                                    case \projects_status::POSTPONED: ?>
                                        <div style="text-align: right">
                                            <a href="<?= $this->lurl ?>/dossiers/postpone/<?= $this->projects->id_project ?>/resume" class="btn btn-small btnDisabled btn_link">Reprendre</a>
                                            <a href="<?= $this->lurl ?>/dossiers/abandon/<?= $this->projects->id_project ?>" class="btn btn-small btnDisabled btn_link thickbox">Abandonner</a>
                                            <a href="<?= $this->lurl ?>/dossiers/ajax_rejection/1/<?= $this->projects->id_project ?>" class="btn btn-small btn-reject btn_link thickbox">Rejeter</a>
                                            <?php if (empty($this->projects->id_product)) : ?>
                                                <br><br>Pour passer le projet à l'étude risque, vous devez sélectionner un produit.
                                            <?php else : ?>
                                                <input type="button" id="status_dosier_valider" class="btn btn-small btn-validate" onclick="check_status_dossier(<?= \projects_status::PENDING_ANALYSIS ?>, <?= $this->projects->id_project ?>);" value="Passer à l'étude risque">
                                            <?php endif; ?>
                                        </div>
                                        <?php break;
                                    case \projects_status::ANALYSIS_REVIEW:
                                    case \projects_status::COMITY_REVIEW: ?>
                                        <div style="text-align: right">
                                            <a href="<?= $this->lurl ?>/dossiers/abandon/<?= $this->projects->id_project ?>" class="btn btn-small btnDisabled btn_link thickbox">Abandonner</a>
                                        </div>
                                        <?php break;
                                    case \projects_status::PREP_FUNDING: ?>
                                        <div style="text-align: right">
                                            <a href="<?= $this->lurl ?>/dossiers/abandon/<?= $this->projects->id_project ?>" class="btn btn-small btnDisabled btn_link thickbox">Abandonner</a>
                                            <?php if (empty($blockingPublishingError)) : ?>
                                                <a href="<?= $this->lurl ?>/dossiers/publish/<?= $this->projects->id_project ?>" class="btn btn-small btn_link thickbox">Programmer la mise en ligne</a>
                                            <?php endif; ?>
                                        </div>
                                        <?php break; ?>
                                    <?php endswitch; ?>
                            </td>
                        </tr>
                    </table>
                    <?php if ($this->projects->status >= \Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus::REMBOURSEMENT) : ?>
                        <h2>
                            Transfert
                            <?php if ($this->displayAddButton) : ?>
                                <a href="<?= $this->lurl ?>/dossiers/add_wire_transfer_out_lightbox/<?= $this->projects->id_project ?>" class="thickbox cboxElement"><img src="<?= $this->surl ?>/images/admin/add.png"></a>
                            <?php endif; ?>
                        </h2>
                        <p>
                            Fonds restants : <?= $this->restFunds ?>
                        </p>
                        <?php if (count($this->wireTransferOuts) > 0) : ?>
                            <table class="tablesorter">
                                <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Bénéficiaire</th>
                                    <th>Motif</th>
                                    <th>Montant</th>
                                    <th>Statut</th>
                                    <th>Action</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Virements $wireTransferOut */
                                $i = 0;
                                ?>
                                <?php foreach ($this->wireTransferOuts as $wireTransferOut) : ?>
                                    <?php
                                    $bankAccount = $wireTransferOut->getBankAccount();
                                    if (null === $bankAccount) {
                                        $bankAccount = $this->bankAccountRepository->getClientValidatedBankAccount($wireTransferOut->getClient());
                                    }
                                    $beneficiary        = $bankAccount->getIdClient();
                                    $beneficiaryCompany = $this->companyRepository->findOneBy(['idClientOwner' => $beneficiary->getIdClient()]);
                                    ?>
                                    <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>
                                        <td><?= $wireTransferOut->getTransferAt() === null ? 'Dès validation' : $wireTransferOut->getTransferAt()->format('d/m/Y') ?></td>
                                        <td>
                                            <?= $beneficiaryCompany->getName() ?>
                                            <?= ' (' . $bankAccount->getIdClient()->getPrenom() . ' ' . $bankAccount->getIdClient()->getNom() . ')' ?>
                                        </td>
                                        <td><?= $wireTransferOut->getMotif() ?></td>
                                        <td><?= $this->currencyFormatter->formatCurrency(bcdiv($wireTransferOut->getMontant(), 100, 4), 'EUR'); ?></td>
                                        <td><?= $this->translator->trans('wire-transfer-out_status-' . $wireTransferOut->getStatus()) ?></td>
                                        <td>
                                            <?php if (false === in_array($wireTransferOut->getStatus(),
                                                    [Virements::STATUS_CLIENT_DENIED, Virements::STATUS_DENIED, Virements::STATUS_VALIDATED, Virements::STATUS_SENT])
                                            ) : ?>
                                                <a href="<?= $this->lurl ?>/dossiers/refuse_wire_transfer_out_lightbox/<?= $wireTransferOut->getIdVirement() ?>/project/" class="thickbox cboxElement">
                                                    <img src="<?= $this->surl ?>/images/admin/delete.png">
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php ++$i; ?>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
            </tr>
            <tr<?php if (empty($this->projects->id_commercial) && \projects_status::NOT_ELIGIBLE != $this->projects->status) : ?> style="display: none"<?php endif; ?>>
                <td colspan="2" class="center">
                    <input type="hidden" name="statut_encours" id="statut_encours" value="0">
                    <input type="hidden" name="send_form_dossier_resume">
                    <button type="submit" class="btn submitdossier">Sauvegarder</button>
                </td>
            </tr>
            </tbody>
        </table>
    </form>
    <hr style="border: 2px solid #b20066;">
    <?php $this->fireView('blocs/memos'); ?>
    <?php $this->fireView('blocs/email'); ?>
    <?php $this->fireView('blocs/etape2'); ?>
    <?php $this->fireView('blocs/etape3'); ?>
    <?php $this->fireView('blocs/etape4_1'); ?>
    <?php $this->fireView('blocs/etape4_2'); ?>
    <?php $this->fireView('blocs/etape4_3'); ?>
    <?php $this->fireView('blocs/etape4_4'); ?>
    <?php $this->fireView('blocs/etape5'); ?>
    <?php $this->fireView('blocs/etape6'); ?>
    <?php $this->fireView('blocs/etape7'); ?>
</div>
