<?php

use Unilend\Entity\{AttachmentType, Companies, ProjectsStatus, UnderlyingContract, UniversignEntityInterface};

?>
<link rel="stylesheet" href="<?= $this->lurl ?>/oneui/js/plugins/dropzonejs/dropzone.min.css">
<script src="<?= $this->lurl ?>/oneui/js/plugins/dropzonejs/dropzone.min.js"></script>

<style type="text/css">
    table.tablesorter tbody td.grisfonceBG, .grisfonceBG {
        background: #d2d2d2 !important;
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
        border-right: 4px solid #2bc9af;
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

    .project-partner th {
        vertical-align: top !important;
    }

    .project-partner .select + .select {
        margin-top: 10px;
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
        border: 2px solid #2bc9af;
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
        background-color: #2bc9af;
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
        background-color: #2bc9af;
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
        background-color: #d2d2d2 !important;
        font-weight: bold;
    }

    #attachments-table .attachment-category {
        cursor: pointer;
        border: 1px solid #fff;
    }

    #attachments-table tr:not(.attachment-category) {
        display: none;
    }

    #attachments-table .attachment-file {
        margin: 5px;
    }

    #attachments-table .attachment-file a.attachment-remove {
        text-decoration: none;
    }

    #attachments-table > tbody > tr > th {
        background-color: #288171;
        color: #fff;
        font-size: 13px;
        font-weight: normal;
        padding: 5px;
        text-align: center;
    }

    .dropzone {
        min-height: auto;
        border: 1px dashed #ccc;
        padding: inherit;
        color: #ccc;
    }

    .dropzone .dz-message {
        margin: auto;
    }

    .dropzone .dz-preview {
        min-height: auto;
    }

    .dropzone .dz-preview.dz-file-preview .dz-image, .dropzone .dz-preview .dz-image {
        border-radius: inherit;
    }

    .spinner_etape {
        display: none;
        height: 32px;
        background: no-repeat center url('<?= $this->surl ?>/images/admin/ajax-loader.gif');
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

    .btn-small {
        border-color: transparent;
        font-size: 11px;
        margin: 2px;
    }

    .btn-small.btnDisabled {
        border-color: #2bc9af;
    }

    .btn-validate {
        background: #009933;
    }

    .btn-reject {
        background: #cc0000;
    }

    .takeover-popup {
        width: 400px;
    }

    .memo-privacy-switch {
        display: inline-block;
        height: 16px;
        width: 16px;
    }

    .memo-privacy-switch.public {
        background: no-repeat center url('<?= $this->surl ?>/images/admin/unlock.png');
    }

    .memo-privacy-switch.private {
        background: no-repeat center url('<?= $this->surl ?>/images/admin/lock.png');
    }

    .memo-privacy-switch.loading {
        background: no-repeat center url('<?= $this->lurl ?>/oneui/js/plugins/bootstrap3-editable/img/loading.gif');
    }

    .memo-privacy-switch > a {
        margin: 0;
    }

    #popup.rejection-popup {
        padding-bottom: 0;
        width: 800px;
    }
</style>

<script>
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

            if (status == <?= ProjectsStatus::STATUS_LOST ?>) {
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

        <?php if (ProjectsStatus::STATUS_CANCELLED != $this->projects->status) : ?>
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

        var $partnerSelect = $('select#partner-select')
        var $companySubmitterSelect = $('select#company-submitter-select')
        var $clientSubmitterSelect = $('select#client-submitter-select')
        var $companySubmitterRow = $('#company-submitter')
        var $clientSubmitterRow = $('#client-submitter')
        var $partnerMessages = $('.project-partner .messages')

        $partnerSelect.change(function() {
            $companySubmitterRow.hide()
            $clientSubmitterRow.hide()
            $companySubmitterSelect.val('').html('<option value="0"></option>')
            $clientSubmitterSelect.val('').html('<option value="0"></option>')

            var $select = $(this)
            if ($select.val() !== '') {
                $.ajax({
                    type: 'POST',
                    url: '<?= $this->lurl ?>/dossiers/partner_products/<?= $this->projects->id_project ?>/' + $select.val(),
                    dataType: 'json',
                    success: function (products) {
                        $('#product').find('option').remove().end().append('<option value=""></option>')
                        $.each(products, function (index, product) {
                            $('#product').append('<option value="' + product.id + '">' + product.label + '</option>')
                        })
                    }
                })
            }
            if ($select.val() === '<?= \Unilend\Entity\Partner::PARTNER_CALS_ID ?>') {
                return false
            }

            $.ajax({
                url: '<?= $this->lurl ?>/partenaires/agences',
                type: 'POST',
                data: {partner: $select.val()},
                dataType:'json',
                success: function(response) {
                    if (response.success) {
                        var agencies = response.data
                        var options = '<option value="0"></option>'
                        for (var i in agencies) {
                            options += '<option value="' + agencies[i].id + '">' + agencies[i].name + '</option>'
                        }
                        $companySubmitterRow.show()
                        $companySubmitterSelect.html(options)
                    } else {
                        var html = ''
                        for (var i in response.error) {
                            html += '<p>' + response.error[i] + '</p>'
                        }
                        $partnerMessages.html(html)
                    }
                }
            })
        })

        $companySubmitterSelect.change(function() {
            $clientSubmitterRow.hide()
            $clientSubmitterSelect.val('').html('<option value=""></option>')

            var $select = $(this)
            if ($select.val() === '0') {
                return false
            }

            $.ajax({
                url: '<?= $this->lurl ?>/partenaires/utilisateurs',
                type: 'POST',
                data: {agency: $select.val()},
                dataType:'json',
                success: function(response) {
                    if (response.success) {
                        var users = response.data
                        var options = '<option value="0"></option>'
                        for (var i in users) {
                            options += '<option value="' + users[i].id + '">' + users[i].name + '</option>'
                        }
                        $clientSubmitterRow.show()
                        $clientSubmitterSelect.html(options)
                    } else {
                        var html = ''
                        for (var i in response.error) {
                            html += '<p>' + response.error[i] + '</p>'
                        }
                        $partnerMessages.html(html)
                    }
                }
            })
        })

        $(document).on('click', '.memo-privacy-switch', function (event) {
            event.preventDefault()

            var $switch = $(this)
            var commentId = $switch.closest('[data-comment-id]').data('comment-id')
            var privacy = $switch.hasClass('public')

            if ($switch.hasClass('loading')) {
                alert('La visibilité du mémo est en cours de modification, veuillez patienter')
                return
            }

            $.ajax({
                url: '<?= $this->lurl ?>/dossiers/memo',
                method: 'POST',
                dataType: 'json',
                data: {
                    commentId: commentId,
                    public: privacy ? 0 : 1
                },
                beforeSend: function () {
                    $switch.removeClass('public private').addClass('loading')
                },
                success: function (response) {
                    if (response.success) {
                        $switch.removeClass('loading').addClass(privacy ? 'private' : 'public')
                    } else if (response.error) {
                        $switch.removeClass('loading').addClass(privacy ? 'public' : 'private')
                        alert(response.error)
                    }
                },
                error: function () {
                    $switch.removeClass('loading').addClass(privacy ? 'public' : 'private')
                    alert('Impossible de modifier la visibilité du mémo (erreur inconnue)')
                }
            })
        })
    })
</script>
<link rel="stylesheet" href="/oneui/js/plugins/select2/select2.min.css">
<link rel="stylesheet" href="/oneui/js/plugins/select2/select2-bootstrap.min.css">
<script type="text/javascript" src="<?= $this->url ?>/ckeditor/ckeditor.js"></script>
<script src="/oneui/js/plugins/select2/select2.min.js"></script>
<div id="contenu">
    <?php if (false === empty($this->projectStatusHeader)) : ?>
        <div class="attention">
            <?= $this->projectStatusHeader ?>
        </div>
    <?php endif; ?>
    <?php if (false === empty($this->projects->title)) : ?><h1><?= $this->projects->title ?></h1><?php endif; ?>
    <form method="post" name="dossier_resume" id="dossier_resume" enctype="multipart/form-data" action="<?= $this->lurl ?>/dossiers/edit/<?= $this->params[0] ?>" target="_parent">
        <table class="project-main">
            <tbody>
            <tr>
                <td class="left-column">
                    <h2>Identité</h2>
                    <table class="form project-identity">
                        <?php if ($this->projects->status >= ProjectsStatus::STATUS_REVIEW) : ?>
                            <tr>
                                <th>Lien projet</th>
                                <td>
                                    <a href="<?= $this->furl ?>/projects/detail/<?= $this->projects->slug ?>" target="_blank"><?= $this->furl ?>/projects/detail/<?= $this->projects->slug ?></a>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <tr>
                            <th>Date de la demande</th>
                            <td><?= $this->formatDate($this->projects->added, 'd/m/Y H:i') ?></td>
                        </tr>
                        <tr>
                            <th><label for="source">Source</label></th>
                            <td>
                                <?php if ($this->projects->create_bo && empty($this->clients->source)) : ?>
                                    <select id="source" name="source" class="select">
                                        <option value=""></option>
                                        <?php foreach ($this->sources as $source) : ?>
                                            <option value="<?= htmlspecialchars(stripslashes($source)) ?>"<?= $this->clients->source === $source ? ' selected' : '' ?>><?= $source ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php else : ?>
                                    <?= empty($this->clients->source) ? '-' : $this->clients->source ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <?php if ($this->isSirenEditable) : ?>
                                <th><label for="siren">SIREN</label></th>
                                <td><input type="text" name="siren" id="siren" class="input_moy"></td>
                            <?php else : ?>
                                <th>SIREN</th>
                                <td><?= $this->companies->siren ?><?php if ($this->isTakeover) : ?> - <?= $this->companies->name ?><?php endif; ?></td>
                            <?php endif; ?>
                        </tr>
                        <?php if ($this->isTakeover) : ?>
                            <tr>
                                <th style="position: relative;">
                                    <label for="target_siren" class="tooltip" title="SIREN de la société reprise/rachetée">SIREN cible</label>
                                    <?php if ($this->projects->status < ProjectsStatus::STATUS_REVIEW) : ?>
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
                                <?php if (false === empty($this->companies->code_naf) && $this->companies->code_naf === Companies::NAF_CODE_NO_ACTIVITY) : ?>
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
                            <td>
                                <input type="text" name="title" id="title" class="input_large" value="<?= htmlspecialchars($this->projects->title) ?>">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="societe">Raison sociale</label></th>
                            <td>
                                <input type="text" name="societe" id="societe" class="input_large" value="<?= htmlspecialchars($this->companies->name) ?>">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="tribunal_com">Tribunal de commerce</label></th>
                            <td>
                                <input type="text" name="tribunal_com" id="tribunal_com" class="input_large" value="<?= htmlspecialchars($this->companies->tribunal_com) ?>">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="activite">Activité</label></th>
                            <td>
                                <input type="text" name="activite" id="activite" class="input_large" value="<?= empty($this->companies->activite) ? (empty($this->xerfi->naf) ? '' : htmlspecialchars($this->xerfi->label)) : htmlspecialchars($this->companies->activite) ?>">
                            </td>
                        </tr>
                    </table>
                    <br><br>
                    <h2>Projet</h2>
                    <table class="form project-attributes">
                        <tr>
                            <th><label for="montant">Montant du prêt&nbsp;*</label></th>
                            <td>
                                <input type="text" name="montant" id="montant" class="input_moy"<?php if ($this->projects->status >= ProjectsStatus::STATUS_REVIEW) : ?> disabled<?php endif; ?> value="<?= empty($this->projects->amount) ? '' : $this->ficelle->formatNumber($this->projects->amount, 0) ?>"> €
                            </td>
                        </tr>
                        <tr>
                            <th><label for="duree">Durée du prêt&nbsp;*</label></th>
                            <td>
                                <select name="duree" id="duree" class="select"<?php if ($this->projects->status >= ProjectsStatus::STATUS_REVIEW) : ?> disabled<?php endif; ?>>
                                    <option<?= empty($this->projects->period) ? ' selected' : '' ?> value=""></option>
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
                                <select name="need" id="need" class="select"<?php if ($this->projects->status >= ProjectsStatus::STATUS_REVIEW) : ?> disabled<?php endif; ?>>
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
                        <?php if ($this->projects->status >= ProjectsStatus::STATUS_REVIEW) : ?>
                            <tr class="content_risk">
                                <th><label for="risk">Niveau de risque</label></th>
                                <td>
                                    <?php $stars = ['A' => '5', 'B' => '4,5', 'C' => '4', 'D' => '3,5', 'E' => '3', 'F' => '2,5', 'G' => '2', 'H' => '1,5']; ?>
                                    <?= $stars[$this->projects->risk] ?? '?' ?> étoiles
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php if (isset($this->rate_min, $this->rate_max)) : ?>
                            <tr>
                                <th><label for="project_rate"> Taux min / max</label></th>
                                <td><?= $this->rate_min ?> % - <?= $this->rate_max ?> %</td>
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
                                <div class="messages"></div>
                                <?php if (null === $this->projectEntity->getIdPartner() || $this->isUnilendPartner) : ?>
                                    <select name="partner" id="partner-select" class="select"<?php if ($this->projects->status >= ProjectsStatus::STATUS_REVIEW) : ?> disabled<?php endif; ?>>
                                        <?php if (null === $this->projectEntity->getIdPartner()->getId()) : ?>
                                            <option value="0"></option>
                                        <?php endif; ?>
                                        <?php foreach ($this->partnerList as $partner) : ?>
                                            <option value="<?= $partner->getId() ?>"<?= $this->projectEntity->getIdPartner()->getId() == $partner->getId() ? ' selected' : '' ?>><?= $partner->getIdCompany()->getName() ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php else : ?>
                                    <?= $this->projectEntity->getIdPartner()->getIdCompany()->getName() ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr id="company-submitter"<?php if ($this->isUnilendPartner) : ?> style="display: none;"<?php endif; ?>>
                            <th><label for="company-submitter-select">Agence</label></th>
                            <td>
                                <select id="company-submitter-select" class="select" name="company_submitter">
                                    <option value="0"></option>
                                    <?php foreach ($this->agencies as $agency) : ?>
                                        <option value="<?= $agency->getIdCompany() ?>"<?php if (null !== $this->projectEntity->getIdCompanySubmitter() && $agency->getIdCompany() === $this->projectEntity->getIdCompanySubmitter()->getIdCompany()) : ?> selected<?php endif; ?>><?= $agency->getName() ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr id="client-submitter"<?php if ($this->isUnilendPartner) : ?> style="display: none;"<?php endif; ?>>
                            <th><label for="client-submitter-select">Déposant</label></th>
                            <td>
                                <select id="client-submitter-select" class="select" name="client_submitter">
                                    <option value="0"></option>
                                    <?php foreach ($this->submitters as $submitter) : ?>
                                        <option value="<?= $submitter->getIdClient() ?>"<?php if ($submitter === $this->projectEntity->getIdClientSubmitter()) : ?> selected<?php endif; ?>><?= $submitter->getFirstName() ?> <?= $submitter->getLastName() ?></option>
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
                                <select name="product" id="product" class="select"<?php if ($this->projects->status > ProjectsStatus::STATUS_REVIEW) : ?> disabled<?php endif; ?>>
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
                        <?php if (isset($this->availableContracts) && in_array(UnderlyingContract::CONTRACT_MINIBON, $this->availableContracts)) : ?>
                            <tr>
                                <th>DIRS</th>
                                <td>
                                    <a href="<?= $this->furl ?>/var/dirs/<?= $this->projects->slug ?>.pdf">
                                        <img src="<?= $this->surl ?>/images/admin/pdf.png" alt="PDF">
                                    </a>
                                    <?php if ($this->projects->status >= ProjectsStatus::STATUS_PUBLISHED) : ?>
                                        <a href="<?= $this->url ?>/dossiers/regenerate_dirs/<?= $this->projects->id_project ?>" class="regenerate-dirs thickbox">
                                            <img src="<?= $this->surl ?>/images/admin/reload.png" alt="Regenerate" title="Régénérer le DIRS">
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </table>
                    <br><br>
                    <?php if ($this->projects->status == ProjectsStatus::STATUS_CONTRACTS_SIGNED) : ?>
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
                        <?php if (false === empty($this->projects->id_commercial) || $this->projects->status !== ProjectsStatus::STATUS_CANCELLED) : ?>
                            <tr>
                                <th><label for="commercial">Commercial</label></th>
                                <td>
                                    <input type="hidden" id="current_commercial" value="<?= $this->projects->id_commercial ?>">
                                    <select name="commercial" id="commercial" class="select">
                                        <option value="0"></option>
                                        <?php
                                        /** @var \Unilend\Entity\Users $salesperson */
                                        foreach ($this->salesPersons as $salesperson) :
                                            ?>
                                            <option value="<?= $salesperson->getIdUser() ?>"<?= ($this->projects->id_commercial == $salesperson->getIdUser() ? ' selected' : '') ?>><?= $salesperson->getFirstname() ?> <?= $salesperson->getName() ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php if ($this->projects->status >= ProjectsStatus::STATUS_REQUESTED || false === empty($this->projects->id_analyste)) : ?>
                            <tr>
                                <th><label for="analyste">Analyste</label></th>
                                <td>
                                    <input type="hidden" id="current_analyst" value="<?= $this->projects->id_analyste ?>">
                                    <select name="analyste" id="analyste" class="select">
                                        <option value="0"></option>
                                        <?php
                                        /** @var \Unilend\Entity\Users $analyst */
                                        foreach ($this->analysts as $analyst) :
                                            ?>
                                            <option value="<?= $analyst->getIdUser() ?>"<?= ($this->projects->id_analyste == $analyst->getIdUser() ? ' selected' : '') ?>><?= $analyst->getFirstname() ?> <?= $analyst->getName() ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <tr>
                            <th><label for="status">Statut</label></th>
                            <td id="current_statut">
                                <input type="hidden" name="current_status" value="<?= $this->projects->status ?>">
                                <?php if ($this->projects->status <= ProjectsStatus::STATUS_PUBLISHED || 0 === count($this->possibleProjectStatus)) : // All statuses should be handled this way, i.e. by only using buttons to transition status ?>
                                    <!-- Useful for backward compatibility purpose. Should not be useful -->
                                    <input type="hidden" name="status" value="<?= $this->projects->status ?>">
                                    <?= $this->projectStatus->getLabel() ?>
                                <?php else : ?>
                                    <a href="<?= $this->lurl ?>/thickbox/popup_confirmation_send_email/<?= $this->projects->id_project ?>" class="thickbox confirmation_send_email"></a>
                                    <input type="hidden" name="check_confirmation_send_email" id="check_confirmation_send_email" value="0">
                                    <select name="status" id="status" class="select">
                                        <?php if (false === empty($this->currentProjectStatus) && false === in_array($this->currentProjectStatus, $this->possibleProjectStatus)) : ?>
                                            <option value="<?= $this->currentProjectStatus->getStatus() ?>" selected disabled><?= $this->currentProjectStatus->getLabel() ?></option>
                                        <?php endif; ?>
                                        <?php foreach ($this->possibleProjectStatus as $s) : ?>
                                            <option <?= ($this->projects->status == $s->getStatus() ? 'selected' : '') ?> value="<?= $s->getStatus() ?>"><?= $s->getLabel() ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                                <?php if (true === $this->projectHasMonitoringEvent) : ?>
                                    <a href="<?= $this->lurl ?>/surveillance_risque" title="Changement de de données externes récent. Voir détails"><span class="e-change-warning"></span></a>
                                <?php endif; ?>
                                <a href="<?= $this->lurl ?>/thickbox/project_history/<?= $this->projects->id_project ?>" class="thickbox"><img src="<?= $this->surl ?>/images/admin/info.png" alt="Information"></a>
                            </td>
                        </tr>
                        <!-- Rejection/abandon reason -->
                        <?php if (false === empty($this->statusReasonText['reason'])) : ?>
                            <tr>
                                <th>Motif(s)</th>
                                <td>
                                    <?php $index = 0; ?>
                                    <?php foreach ($this->statusReasonText['reason'] as $idReason => $reason) : ?>
                                        <?php $index++ ?>
                                        <?= $reason ?>
                                        <?php if (false === empty($this->statusReasonText['description'][$idReason])) : ?>
                                            <img src="<?= $this->surl ?>/images/admin/info.png" alt="<?= htmlentities($this->statusReasonText['description'][$idReason]) ?>" title="<?= htmlentities($this->statusReasonText['description'][$idReason]) ?>" class="tooltip">
                                        <?php endif; ?>
                                        <?= $index < count($this->statusReasonText['reason']) ? '<br>' : '' ?>
                                    <?php endforeach; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php if ($this->projects->status == ProjectsStatus::STATUS_REVIEW) : ?>
                            <?php
                            $blockingPublishingError = [];

                            if (empty($this->projects->period)) {
                                $blockingPublishingError[] = 'Veuillez sélectionner une durée de prêt';
                            }

                            if (
                                in_array(UnderlyingContract::CONTRACT_MINIBON, $this->availableContracts)
                                && false === isset($this->projectAttachmentsByType[AttachmentType::DEBTS_STATEMENT])
                            ) {
                                $blockingPublishingError[] = 'Veuillez charger l\'état des engagements (nécessaire au DIRS)';
                            }

                            if (false === $this->isProductUsable) {
                                $blockingPublishingError[] = 'Le produit associé au projet n\'est plus disponible ou éligible. Veuillez sélectionner un autre produit.';
                            }

                            if (false === $this->hasBeneficialOwner) {
                                $blockingPublishingError[] = 'Veuillez déclarer les bénéficiaires effectifs';
                            }

                            if ($this->hasBeneficialOwner && false === $this->ownerIsBeneficialOwner) {
                                $blockingPublishingError[] = 'Veuillez déclarer le proprietaire de l\'entreprise comme bénéficiaire effectif';
                            }
                            ?>
                            <?php if (false === empty($blockingPublishingError)) : ?>
                                <tr>
                                    <td colspan="2"><?= implode('<br>', $blockingPublishingError) ?></td>
                                </tr>
                                <?php if (false === $this->hasBeneficialOwner || false === $this->ownerIsBeneficialOwner) : ?>
                                    <tr>
                                        <th>Déclaration de <br>bénéficiaires effectifs</th>
                                        <td>
                                            <a role="button" class="btn btn-default" href="<?= $this->lurl ?>/beneficiaires_effectifs/<?= $this->companies->id_company ?>" target="_blank">Déclarer les bénéficiaires effectifs</a>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php if ($this->projects->status >= ProjectsStatus::STATUS_REVIEW) : ?>
                            <tr>
                                <th><label for="date_publication">Date de publication&nbsp;*</label></th>
                                <td>
                                    <?php if ($this->projects->status == ProjectsStatus::STATUS_REVIEW) : ?>
                                        <input type="text" name="date_publication" id="date_publication" class="input_dp" value="<?= ($this->projects->date_publication != '0000-00-00 00:00:00' ? $this->formatDate($this->projects->date_publication, 'd/m/Y') : '') ?>">
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
                                        <?= $this->formatDate($this->projects->date_publication, 'd/m/Y H:i') ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="date_retrait">Date de retrait&nbsp;*</label></th>
                                <td>
                                    <?php if ($this->projects->status == ProjectsStatus::STATUS_REVIEW) : ?>
                                        <input type="text" name="date_retrait" id="date_retrait" class="input_dp" value="<?= ($this->projects->date_retrait != '0000-00-00 00:00:00' ? $this->formatDate($this->projects->date_retrait, 'd/m/Y') : '') ?>">
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
                                        <?= $this->formatDate($this->projects->date_retrait, 'd/m/Y H:i') ?>
                                        <?php if ($this->projects->status < ProjectsStatus::STATUS_FUNDED) : ?>
                                            &nbsp;&nbsp;&nbsp;<a href="<?= $this->lurl ?>/thickbox/pop_up_edit_date_retrait/<?= $this->projects->id_project ?>" class="thickbox btn_link ">Modifier</a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php if (false === empty($_SESSION['publish_error'])) : ?>
                            <tr>
                                <td colspan="2" style="color:red; font-weight:bold;"><?= $_SESSION['publish_error'] ?></td>
                            </tr>
                            <?php unset($_SESSION['publish_error']); ?>
                        <?php endif; ?>
                        <?php if ($this->projects_pouvoir->get($this->projects->id_project, 'id_project')) : ?>
                            <tr>
                                <th>
                                    <?php if ($this->projects_pouvoir->status == \Unilend\Entity\ProjectsPouvoir::STATUS_SIGNED) : ?>
                                        <span>&nbsp;&#9989;</span>
                                    <?php else: ?>
                                        <span>&nbsp;&#10060;</span>
                                    <?php endif; ?>
                                    <label for="pouvoir">Pouvoir</label>
                                </th>
                                <td>
                                    <div>
                                        <a href="<?= $this->lurl ?>/protected/pouvoir_project/<?= $this->projects_pouvoir->name ?>"><?= $this->projects_pouvoir->name ?></a>
                                    </div>
                                </td>
                            </tr>
                        <?php elseif ($this->projects->status == ProjectsStatus::STATUS_FUNDED) : ?>
                            <tr>
                                <th><label for="upload_pouvoir">Pouvoir</label></th>
                                <td><input type="file" name="upload_pouvoir" id="upload_pouvoir"></td>
                            </tr>
                        <?php endif; ?>
                        <?php if ($this->projects->status == ProjectsStatus::STATUS_FUNDED) : ?>
                            <tr>
                                <th><label for="pret_refuse">Prêt refusé</label></th>
                                <td>
                                    <select name="pret_refuse" id="pret_refuse" class="select">
                                        <option value="0">Non</option>
                                        <option value="1">Oui</option>
                                    </select>
                                </td>
                            </tr>
                            <?php if (empty($this->proxy) || $this->proxy['status'] != UniversignEntityInterface::STATUS_SIGNED) : ?>
                                <tr>
                                    <th>Pouvoir</th>
                                    <td>
                                        <a href="<?= $this->furl ?>/pdf/pouvoir/<?= $this->clients->hash ?>/<?= $this->projects->id_project ?>">
                                            <img src="<?= $this->surl ?>/images/admin/pdf.png" alt="PDF">
                                        </a>
                                    </td>
                                </tr>
                            <?php endif ?>
                            <?php if (empty($this->mandate) || $this->mandate['status'] != UniversignEntityInterface::STATUS_SIGNED) : ?>
                                <tr>
                                    <th>Mandat</th>
                                    <?php if ($this->validBankAccount) : ?>
                                        <td>
                                            <a href="<?= $this->furl ?>/pdf/mandat/<?= $this->clients->hash ?>/<?= $this->projects->id_project ?>">
                                                <img src="<?= $this->surl ?>/images/admin/pdf.png" alt="PDF">
                                            </a>
                                        </td>
                                    <?php else : ?>
                                        <td>L'emprunteur n'a pas de RIB en vigueur.</td>
                                    <?php endif ?>
                                </tr>
                            <?php endif ?>
                            <?php if (null === $this->beneficialOwnerDeclaration || $this->beneficialOwnerDeclaration->getStatus() != UniversignEntityInterface::STATUS_SIGNED) : ?>
                                <tr>
                                    <th>Déclaration de <br> bénéficiaires effectifs</th>
                                    <td colspan="2">
                                        <a role="button" class="btn btn-default" href="<?= $this->lurl ?>/beneficiaires_effectifs/<?= $this->companies->id_company ?>" target="_blank">Accéder à la gestion des bénéficiaires effectifs<br>(Modifier/Consulter/Renvoyer la déclaration)</a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endif; ?>
                        <tr>
                            <td colspan="2">
                                <?php switch ($this->projects->status) :
                                    case ProjectsStatus::STATUS_REQUESTED: ?>
                                        <div style="text-align: right">
                                            <a role="button" data-memo="#postpone-project-memo" data-memo-onsubmit="/dossiers/postpone/<?= $this->projects->id_project ?>" data-memo-project-id="<?= $this->projects->id_project ?>" class="btn btn-small btnDisabled btn_link">Reporter</a>
                                            <a role="button" data-memo="#abandon-project-memo" data-memo-optional data-memo-onsubmit="/dossiers/abandon/<?= $this->projects->id_project ?>" data-memo-project-id="<?= $this->projects->id_project ?>" class="btn btn-small btnDisabled btn_link">Abandonner</a>
                                            <a href="<?= $this->lurl ?>/dossiers/ajax_rejection/1/<?= $this->projects->id_project ?>" class="btn btn-small btn-reject btn_link thickbox">Rejeter</a>
                                            <?php if (empty($this->projects->id_product)) : ?>
                                                <br><br>Pour passer le projet à l'étude risque, vous devez sélectionner un produit.
                                            <?php else : ?>
                                                <input type="button" id="status_dosier_valider" class="btn btn-small btn-validate" onclick="check_status_dossier(<?= ProjectsStatus::STATUS_REQUESTED ?>, <?= $this->projects->id_project ?>);" value="Passer à l'étude risque">
                                            <?php endif; ?>
                                        </div>
                                        <?php break;
                                    case ProjectsStatus::STATUS_REVIEW: ?>
                                        <div style="text-align: right">
                                            <a href="<?= $this->lurl ?>/dossiers/postpone/<?= $this->projects->id_project ?>/resume" class="btn btn-small btnDisabled btn_link">Reprendre</a>
                                            <a role="button" data-memo="#abandon-project-memo" data-memo-optional data-memo-onsubmit="/dossiers/abandon/<?= $this->projects->id_project ?>" data-memo-project-id="<?= $this->projects->id_project ?>" class="btn btn-small btnDisabled btn_link">Abandonner</a>
                                            <a href="<?= $this->lurl ?>/dossiers/ajax_rejection/1/<?= $this->projects->id_project ?>" class="btn btn-small btn-reject btn_link thickbox">Rejeter</a>
                                            <?php if (empty($this->projects->id_product)) : ?>
                                                <br><br>Pour passer le projet à l'étude risque, vous devez sélectionner un produit.
                                            <?php else : ?>
                                                <input type="button" id="status_dosier_valider" class="btn btn-small btn-validate" onclick="check_status_dossier(<?= ProjectsStatus::STATUS_REQUESTED ?>, <?= $this->projects->id_project ?>);" value="Passer à l'étude risque">
                                            <?php endif; ?>
                                        </div>
                                        <?php break; ?>
                                    <?php endswitch; ?>

                                <div id="abandon-project-memo" style="display: none;">
                                    <label for="reason" style="display: block; margin: 0 0 10px;">Motif d'abandon *</label>
                                    <select style="width: 80%;" name="reason[]" id="reason" class="js-select2 form-control required select" data-placeholder="Séléctionnez un ou plusieurs motifs.." multiple>
                                        <option value=""></option>
                                        <?php foreach ($this->projectAbandonReasonList as $abandonReason) : ?>
                                            <?php /** @var \Unilend\Entity\ProjectAbandonReason $abandonReason */ ?>
                                            <option value="<?= $abandonReason->getIdAbandon() ?>" title="<?= htmlentities($abandonReason->getDescription()) ?>" class="tooltip"><?= $abandonReason->getReason() ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div id="postpone-project-memo" style="display: none;">
                                    <label>Motif de report *</label>
                                </div>
                            </td>
                        </tr>
                    </table>
                    <?php if ($this->projects->status >= ProjectsStatus::STATUS_CONTRACTS_SIGNED) : ?>
                        <h2>
                            Transfert
                            <?php if ($this->restFunds > 0) : ?>
                                <a href="<?= $this->lurl ?>/dossiers/add_wire_transfer_out_lightbox/<?= \Unilend\Service\WireTransferOutManager::TRANSFER_OUT_BY_PROJECT ?>/<?= $this->projects->id_project ?>" class="thickbox cboxElement"><img src="<?= $this->surl ?>/images/admin/add.png"></a>
                            <?php endif; ?>
                        </h2>
                        <p>
                            Fonds restants : <?= $this->currencyFormatter->formatCurrency($this->restFunds, 'EUR'); ?>
                        </p>
                        <?php $this->fireView('blocs/wire_transfer_out_list'); ?>
                    <?php endif; ?>
                </td>
            </tr>
            <tr<?php if (empty($this->projects->id_commercial) && ProjectsStatus::STATUS_CANCELLED != $this->projects->status) : ?> style="display: none"<?php endif; ?>>
                <td colspan="2" class="center">
                    <input type="hidden" name="statut_encours" id="statut_encours" value="0">
                    <input type="hidden" name="send_form_dossier_resume">
                    <button type="submit" class="btn submitdossier">Sauvegarder</button>
                </td>
            </tr>
            </tbody>
        </table>
    </form>
    <hr style="border: 2px solid #2bc9af;">
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
