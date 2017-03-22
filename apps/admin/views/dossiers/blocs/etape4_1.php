<script type="text/javascript">
    $(function() {
        $('#date_dernier_privilege, #date_tresorerie, #target_date_dernier_privilege, #target_date_tresorerie').datepicker({
            showOn: 'both',
            buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            yearRange: '<?= (date('Y') - 40) ?>:<?= (date('Y')) ?>'
        })

        $('#company_projects').tablesorter()

        $('.company_projects').click(function() {
            $(location).attr('href', '<?= $this->lurl ?>/dossiers/edit/' + $(this).data('project'))
        })

        <?php if ($this->iCompanyProjectsCount > 8) : ?>
        $('#company_projects').tablesorterPager({
            container: $("#projectsPager"),
            positionFixed: false,
            size: 8
        })
        <?php endif; ?>

        var displayBankName = function(event) {
            if ('' == $(event.target).val()) {
                $('#nom_banque').hide()
            } else {
                $('#nom_banque').show()
            }
        }

        $('#note_interne_banque')
            .change(displayBankName)
            .keyup(displayBankName)
            .keydown(displayBankName)

        var displayTargetBankName = function(event) {
            if ('' == $(event.target).val()) {
                $('#target_nom_banque').hide()
            } else {
                $('#target_nom_banque').show()
            }
        }

        $('#target_note_interne_banque')
            .change(displayTargetBankName)
            .keyup(displayTargetBankName)
            .keydown(displayTargetBankName)

        $('.rating-tooltip').tooltip({
            content: function() {
                var ratingType = $(this).attr('title'),
                    rating

                if (ratingType.substring(0, 7) === 'target_') {
                    ratingType = ratingType.substring(7)
                    rating = targetRatingsHistory[ratingType]
                } else {
                    rating = ratingsHistory[ratingType]
                }

                var content = '<strong>' + rating.action + '</strong><br>' + rating.date

                if (rating.user) {
                    content += '<br>' + rating.user
                }

                return content
            }
        })

        var ratingsHistory = $.parseJSON('<?= json_encode($this->ratings) ?>')

        <?php if (isset($this->targetRatings)) : ?>
            var targetRatingsHistory = $.parseJSON('<?= json_encode($this->targetRatings) ?>')
        <?php endif; ?>
    });
</script>
<a class="tab_title" id="section-external-ratings" href="#section-external-ratings">4.1. Notation externe</a>
<div class="tab_content<?php if (in_array($this->projects->status, [\projects_status::ANALYSIS_REVIEW, \projects_status::COMITY_REVIEW]) && \users_types::TYPE_RISK == $_SESSION['user']['id_user_type']) : ?> expand<?php endif; ?>" id="etape4_1">
    <form method="post" name="dossier_etape4_1" id="dossier_etape4_1" onsubmit="valid_etape4_1(<?= $this->projects->id_project ?>); return false;" enctype="multipart/form-data" action="<?= $this->lurl ?>/dossiers/edit/<?= $this->params[0] ?>" target="_parent">
        <div id="contenu_etape4_1">
            <?php if ($this->bIsProblematicCompany) : ?>
                <div class="attention">Cette société a déjà eu des problèmes</div>
                <br>
            <?php endif; ?>
            <h1>Notes externes</h1>
            <table class="form" style="width: auto">
                <?php if (isset($this->targetRatings)) : ?>
                    <thead>
                    <tr>
                        <th></th>
                        <th style="text-align: left"><?= $this->companies->name ?></th>
                        <th style="text-align: left"><?= $this->targetCompany->name ?></th>
                    </tr>
                    </thead>
                <?php endif; ?>
                <tbody>
                <tr>
                    <th style="width: 300px"><label>Grade Euler-Hermes</label></th>
                    <td style="width: 250px">
                        <?php if (false === empty($this->ratings['grade_euler_hermes']['value'])) : ?>
                            <span class="rating-tooltip" title="grade_euler_hermes"><?= $this->ratings['grade_euler_hermes']['value'] ?></span>
                        <?php else : ?>-<?php endif; ?>
                    </td>
                    <?php if (isset($this->targetRatings)) : ?>
                        <td style="width: 250px">
                            <?php if (false === empty($this->targetRatings['grade_euler_hermes']['value'])) : ?>
                                <span class="rating-tooltip" title="target_grade_euler_hermes"><?= $this->targetRatings['grade_euler_hermes']['value'] ?></span>
                            <?php else : ?>-<?php endif; ?>
                        </td>
                    <?php endif; ?>
                </tr>
                <tr>
                    <th><label>Score Altares</label></th>
                    <td>
                        <?php if (false === empty($this->ratings['score_altares']['value'])) : ?>
                            <span class="rating-tooltip" title="score_altares"><?= $this->ratings['score_altares']['value'] ?> / 20</span>
                        <?php else : ?>-<?php endif; ?>
                    </td>
                    <?php if (isset($this->targetRatings)) : ?>
                        <td>
                            <?php if (false === empty($this->targetRatings['score_altares']['value'])) : ?>
                                <span class="rating-tooltip" title="target_score_altares"><?= $this->targetRatings['score_altares']['value'] ?> / 20</span>
                            <?php else : ?>-<?php endif; ?>
                        </td>
                    <?php endif; ?>
                </tr>
                <tr>
                    <th><label>Score sectoriel Altares</label></th>
                    <td>
                        <?php if (false === empty($this->ratings['score_sectoriel_altares']['value'])) : ?>
                            <span class="rating-tooltip" title="score_sectoriel_altares"><?= round($this->ratings['score_sectoriel_altares']['value'] / 5) ?> / 20</span>
                        <?php else : ?>-<?php endif; ?>
                    </td>
                    <?php if (isset($this->targetRatings)) : ?>
                        <td>
                            <?php if (false === empty($this->targetRatings['score_sectoriel_altares']['value'])) : ?>
                                <span class="rating-tooltip" title="target_score_sectoriel_altares"><?= round($this->targetRatings['score_sectoriel_altares']['value'] / 5) ?> / 20</span>
                            <?php else : ?>-<?php endif; ?>
                        </td>
                    <?php endif; ?>
                </tr>
                <tr>
                    <th><label>Note Infolegale</label></th>
                    <td>
                        <?php if (false === empty($this->ratings['note_infolegale']['value'])) : ?>
                            <span class="rating-tooltip" title="note_infolegale"><?= $this->ratings['note_infolegale']['value'] ?></span>
                        <?php else : ?>-<?php endif; ?>
                    </td>
                    <?php if (isset($this->targetRatings)) : ?>
                        <td>
                            <?php if (false === empty($this->targetRatings['note_infolegale']['value'])) : ?>
                                <span class="rating-tooltip" title="target_note_infolegale"><?= $this->targetRatings['note_infolegale']['value'] ?></span>
                            <?php else : ?>-<?php endif; ?>
                        </td>
                    <?php endif; ?>
                </tr>
                <tr>
                    <th><label>Score sectoriel Xerfi</label></th>
                    <td>
                        <?php if (isset($this->ratings['xerfi']['value'], $this->ratings['xerfi_unilend']['value'])) : ?>
                            <span class="rating-tooltip" title="xerfi"><?= $this->ratings['xerfi']['value'] ?></span> / <span class="rating-tooltip" title="xerfi_unilend"><?= $this->ratings['xerfi_unilend']['value'] ?></span>
                        <?php else : ?>-<?php endif; ?>
                    </td>
                    <?php if (isset($this->targetRatings)) : ?>
                        <td>
                            <?php if (isset($this->targetRatings['xerfi']['value'], $this->targetRatings['xerfi_unilend']['value'])) : ?>
                                <span class="rating-tooltip" title="target_xerfi"><?= $this->targetRatings['xerfi']['value'] ?></span> / <span class="rating-tooltip" title="target_xerfi_unilend"><?= $this->targetRatings['xerfi_unilend']['value'] ?></span>
                            <?php else : ?>-<?php endif; ?>
                        </td>
                    <?php endif; ?>
                </tr>
                <tr>
                    <th><label>Présence de RPC < 6 mois</label></th>
                    <td>
                        <?php if (isset($this->ratings['rpc_6mois']['value']) && in_array($this->ratings['rpc_6mois']['value'], ['0', '1'], true)) : ?><span class="rating-tooltip" title="rpc_6mois"><?php endif; ?>
                            <?php if ($this->projects->status <= \projects_status::COMITY_REVIEW) : ?>
                                <label><input type="radio" name="ratings[rpc_6mois]" value="1"<?php if (isset($this->ratings['rpc_6mois']['value']) && '1' === $this->ratings['rpc_6mois']['value']) : ?> checked<?php endif; ?>> Oui</label>
                                <label><input type="radio" name="ratings[rpc_6mois]" value="0"<?php if (isset($this->ratings['rpc_6mois']['value']) && '0' === $this->ratings['rpc_6mois']['value']) : ?> checked<?php endif; ?>> Non</label>
                            <?php elseif (isset($this->ratings['rpc_6mois']) && '1' === $this->ratings['rpc_6mois']['value']) : ?>
                                Oui
                            <?php elseif (isset($this->ratings['rpc_6mois']) && '0' === $this->ratings['rpc_6mois']['value']) : ?>
                                Non
                            <?php else : ?>-<?php endif; ?>
                        <?php if (isset($this->ratings['rpc_6mois']['value']) && in_array($this->ratings['rpc_6mois']['value'], ['0', '1'], true)) : ?></span><?php endif; ?>
                    </td>
                    <?php if (isset($this->targetRatings)) : ?>
                        <td>
                            <?php if (isset($this->targetRatings['rpc_6mois']['value']) && in_array($this->targetRatings['rpc_6mois']['value'], ['0', '1'], true)) : ?><span class="rating-tooltip" title="target_rpc_6mois"><?php endif; ?>
                                <?php if ($this->projects->status <= \projects_status::COMITY_REVIEW) : ?>
                                    <label><input type="radio" name="target_ratings[rpc_6mois]" value="1"<?php if (isset($this->targetRatings['rpc_6mois']['value']) && '1' === $this->targetRatings['rpc_6mois']['value']) : ?> checked<?php endif; ?>> Oui</label>
                                    <label><input type="radio" name="target_ratings[rpc_6mois]" value="0"<?php if (isset($this->targetRatings['rpc_6mois']['value']) && '0' === $this->targetRatings['rpc_6mois']['value']) : ?> checked<?php endif; ?>> Non</label>
                                <?php elseif (isset($this->targetRatings['rpc_6mois']) && '1' === $this->targetRatings['rpc_6mois']['value']) : ?>
                                    Oui
                                <?php elseif (isset($this->targetRatings['rpc_6mois']) && '0' === $this->targetRatings['rpc_6mois']['value']) : ?>
                                    Non
                                <?php else : ?>-<?php endif; ?>
                            <?php if (isset($this->targetRatings['rpc_6mois']['value']) && in_array($this->targetRatings['rpc_6mois']['value'], ['0', '1'], true)) : ?></span><?php endif; ?>
                        </td>
                    <?php endif; ?>
                </tr>
                <tr>
                    <th><label>Présence de RPC > 12 mois</label></th>
                    <td>
                        <?php if (isset($this->ratings['rpc_12mois']['value']) && in_array($this->ratings['rpc_12mois']['value'], ['0', '1'], true)) : ?><span class="rating-tooltip" title="rpc_12mois"><?php endif; ?>
                            <?php if ($this->projects->status <= \projects_status::COMITY_REVIEW) : ?>
                                <label><input type="radio" name="ratings[rpc_12mois]" value="1"<?php if (isset($this->ratings['rpc_12mois']['value']) && '1' === $this->ratings['rpc_12mois']['value']) : ?> checked<?php endif; ?>> Oui</label>
                                <label><input type="radio" name="ratings[rpc_12mois]" value="0"<?php if (isset($this->ratings['rpc_12mois']['value']) && '0' === $this->ratings['rpc_12mois']['value']) : ?> checked<?php endif; ?>> Non</label>
                            <?php elseif (isset($this->ratings['rpc_12mois']) && '1' === $this->ratings['rpc_12mois']['value']) : ?>
                                Oui
                            <?php elseif (isset($this->ratings['rpc_12mois']) && '0' === $this->ratings['rpc_12mois']['value']) : ?>
                                Non
                            <?php else : ?>-<?php endif; ?>
                        <?php if (isset($this->ratings['rpc_12mois']['value']) && in_array($this->ratings['rpc_12mois']['value'], ['0', '1'], true)) : ?></span><?php endif; ?>
                    </td>
                    <?php if (isset($this->targetRatings)) : ?>
                        <td>
                        <?php if (isset($this->targetRatings['rpc_12mois']['value']) && in_array($this->targetRatings['rpc_12mois']['value'], ['0', '1'], true)) : ?><span class="rating-tooltip" title="target_rpc_12mois"><?php endif; ?>
                            <?php if ($this->projects->status <= \projects_status::COMITY_REVIEW) : ?>
                                <label><input type="radio" name="target_ratings[rpc_12mois]" value="1"<?php if (isset($this->targetRatings['rpc_12mois']['value']) && '1' === $this->targetRatings['rpc_12mois']['value']) : ?> checked<?php endif; ?>> Oui</label>
                                <label><input type="radio" name="target_ratings[rpc_12mois]" value="0"<?php if (isset($this->targetRatings['rpc_12mois']['value']) && '0' === $this->targetRatings['rpc_12mois']['value']) : ?> checked<?php endif; ?>> Non</label>
                            <?php elseif (isset($this->targetRatings['rpc_12mois']) && '1' === $this->targetRatings['rpc_12mois']['value']) : ?>
                                Oui
                            <?php elseif (isset($this->targetRatings['rpc_12mois']) && '0' === $this->targetRatings['rpc_12mois']['value']) : ?>
                                Non
                            <?php else : ?>-<?php endif; ?>
                        <?php if (isset($this->targetRatings['rpc_12mois']['value']) && in_array($this->targetRatings['rpc_12mois']['value'], ['0', '1'], true)) : ?></span><?php endif; ?>
                        </td>
                    <?php endif; ?>
                </tr>
                <tr>
                    <th><label for="cotation_fiben">Cotation FIBEN</label></th>
                    <td>
                        <?php if (false === empty($this->ratings['cotation_fiben']['value'])) : ?><span class="rating-tooltip" title="cotation_fiben"><?php endif; ?>
                            <?php if ($this->projects->status <= \projects_status::COMITY_REVIEW) : ?>
                                <input type="text" name="ratings[cotation_fiben]" id="cotation_fiben" value="<?php if (false === empty($this->ratings['cotation_fiben']['value'])) : ?><?= $this->ratings['cotation_fiben']['value'] ?><?php endif; ?>" class="input_moy">
                            <?php elseif (false === empty($this->ratings['cotation_fiben']['value'])) : ?>
                                <span class="rating-tooltip" title="cotation_fiben"><?= $this->ratings['cotation_fiben']['value'] ?></span>
                            <?php else : ?>-<?php endif; ?>
                        <?php if (false === empty($this->ratings['cotation_fiben']['value'])) : ?></span><?php endif; ?>
                    </td>
                    <?php if (isset($this->targetRatings)) : ?>
                        <td>
                            <?php if (false === empty($this->targetRatings['cotation_fiben']['value'])) : ?><span class="rating-tooltip" title="target_cotation_fiben"><?php endif; ?>
                                <?php if ($this->projects->status <= \projects_status::COMITY_REVIEW) : ?>
                                    <input type="text" name="target_ratings[cotation_fiben]" id="cotation_fiben" value="<?php if (false === empty($this->targetRatings['cotation_fiben']['value'])) : ?><?= $this->targetRatings['cotation_fiben']['value'] ?><?php endif; ?>" class="input_moy">
                                <?php elseif (false === empty($this->targetRatings['cotation_fiben']['value'])) : ?>
                                    <span class="rating-tooltip" title="target_cotation_fiben"><?= $this->targetRatings['cotation_fiben']['value'] ?></span>
                                <?php else : ?>-<?php endif; ?>
                            <?php if (false === empty($this->targetRatings['cotation_fiben']['value'])) : ?></span><?php endif; ?>
                        </td>
                    <?php endif; ?>
                </tr>
                <tr>
                    <th><label for="cotation_dirigeant_fiben">Cotation dirigeant FIBEN</label></th>
                    <td>
                        <?php if (false === empty($this->ratings['cotation_dirigeant_fiben']['value'])) : ?><span class="rating-tooltip" title="cotation_dirigeant_fiben"><?php endif; ?>
                            <?php if ($this->projects->status <= \projects_status::COMITY_REVIEW) : ?>
                                <input type="text" name="ratings[cotation_dirigeant_fiben]" id="cotation_dirigeant_fiben" value="<?php if (false === empty($this->ratings['cotation_dirigeant_fiben']['value'])) : ?><?= $this->ratings['cotation_dirigeant_fiben']['value'] ?><?php endif; ?>" class="input_moy">
                            <?php elseif (false === empty($this->ratings['cotation_dirigeant_fiben']['value'])) : ?>
                                <span class="rating-tooltip" title="cotation_dirigeant_fiben"><?= $this->ratings['cotation_dirigeant_fiben']['value'] ?></span>
                            <?php else : ?>-<?php endif; ?>
                        <?php if (false === empty($this->ratings['cotation_dirigeant_fiben']['value'])) : ?></span><?php endif; ?>
                    </td>
                    <?php if (isset($this->targetRatings)) : ?>
                        <td>
                            <?php if (false === empty($this->targetRatings['cotation_dirigeant_fiben']['value'])) : ?><span class="rating-tooltip" title="target_cotation_dirigeant_fiben"><?php endif; ?>
                                <?php if ($this->projects->status <= \projects_status::COMITY_REVIEW) : ?>
                                    <input type="text" name="target_ratings[cotation_dirigeant_fiben]" id="target_cotation_dirigeant_fiben" value="<?php if (false === empty($this->targetRatings['cotation_dirigeant_fiben']['value'])) : ?><?= $this->targetRatings['cotation_dirigeant_fiben']['value'] ?><?php endif; ?>" class="input_moy">
                                <?php elseif (false === empty($this->targetRatings['cotation_dirigeant_fiben']['value'])) : ?>
                                    <span class="rating-tooltip" title="target_cotation_dirigeant_fiben"><?= $this->targetRatings['cotation_dirigeant_fiben']['value'] ?></span>
                                <?php else : ?>-<?php endif; ?>
                            <?php if (false === empty($this->targetRatings['cotation_dirigeant_fiben']['value'])) : ?></span><?php endif; ?>
                        </td>
                    <?php endif; ?>
                </tr>
                <tr>
                    <th><label for="note_interne_banque">Note interne banque</label></th>
                    <td>
                        <?php if ($this->projects->status <= \projects_status::COMITY_REVIEW) : ?>
                            <?php if (false === empty($this->ratings['note_interne_banque']['value'])) : ?>
                                <span class="rating-tooltip" title="note_interne_banque">
                                    <input type="text" name="ratings[note_interne_banque]" id="note_interne_banque" value="<?= $this->ratings['note_interne_banque']['value'] ?>" class="input_moy">
                                </span>
                            <?php else : ?>
                                <input type="text" name="ratings[note_interne_banque]" id="note_interne_banque" class="input_moy">
                            <?php endif; ?>
                            <?php if (false === empty($this->ratings['nom_banque']['value'])) : ?>
                                <span class="rating-tooltip" title="nom_banque">
                                    <input type="text" name="ratings[nom_banque]" id="nom_banque" value="<?= $this->ratings['nom_banque']['value'] ?>" placeholder="Nom banque" class="input_moy"<?php if (empty($this->ratings['note_interne_banque']['value'])) : ?> style="display: none;"<?php endif; ?>>
                                </span>
                            <?php else : ?>
                                <input type="text" name="ratings[nom_banque]" id="nom_banque" placeholder="Nom banque" class="input_moy"<?php if (empty($this->ratings['note_interne_banque']['value'])) : ?> style="display: none;"<?php endif; ?>>
                            <?php endif; ?>
                        <?php elseif (false === empty($this->ratings['note_interne_banque']['value'])) : ?>
                            <span class="rating-tooltip" title="note_interne_banque"><?= $this->ratings['note_interne_banque']['value'] ?></span>
                            <?php if (false === empty($this->ratings['nom_banque']['value'])) : ?> / <span class="rating-tooltip" title="nom_banque"><?= $this->ratings['nom_banque']['value'] ?></span><?php endif; ?>
                        <?php else : ?>-<?php endif; ?>
                    </td>
                    <?php if (isset($this->targetRatings)) : ?>
                        <td>
                            <?php if ($this->projects->status <= \projects_status::COMITY_REVIEW) : ?>
                                <?php if (false === empty($this->targetRatings['note_interne_banque']['value'])) : ?>
                                    <span class="rating-tooltip" title="target_note_interne_banque">
                                        <input type="text" name="target_ratings[note_interne_banque]" id="target_note_interne_banque" value="<?= $this->targetRatings['note_interne_banque']['value'] ?>" class="input_moy">
                                    </span>
                                <?php else : ?>
                                    <input type="text" name="target_ratings[note_interne_banque]" id="target_note_interne_banque" class="input_moy">
                                <?php endif; ?>
                                <?php if (false === empty($this->targetRatings['nom_banque']['value'])) : ?>
                                    <span class="rating-tooltip" title="target_nom_banque">
                                        <input type="text" name="target_ratings[nom_banque]" id="target_nom_banque" value="<?= $this->targetRatings['nom_banque']['value'] ?>" placeholder="Nom banque" class="input_moy"<?php if (empty($this->targetRatings['note_interne_banque']['value'])) : ?> style="display: none;"<?php endif; ?>>
                                    </span>
                                <?php else : ?>
                                    <input type="text" name="target_ratings[nom_banque]" id="target_nom_banque" placeholder="Nom banque" class="input_moy"<?php if (empty($this->targetRatings['note_interne_banque']['value'])) : ?> style="display: none;"<?php endif; ?>>
                                <?php endif; ?>
                            <?php elseif (false === empty($this->targetRatings['note_interne_banque']['value'])) : ?>
                                <span class="rating-tooltip" title="target_note_interne_banque"><?= $this->targetRatings['note_interne_banque']['value'] ?></span>
                                <?php if (false === empty($this->targetRatings['nom_banque']['value'])) : ?> / <span class="rating-tooltip" title="target_nom_banque"><?= $this->targetRatings['nom_banque']['value'] ?></span><?php endif; ?>
                            <?php else : ?>-<?php endif; ?>
                        </td>
                    <?php endif; ?>
                </tr>
                <tr>
                    <th><label for="date_dernier_privilege">Date du privilège le plus récent</label></th>
                    <td>
                        <?php if (false === empty($this->ratings['date_dernier_privilege']['value'])) : ?><span class="rating-tooltip" title="date_dernier_privilege"><?php endif; ?>
                            <?php if ($this->projects->status <= \projects_status::COMITY_REVIEW) : ?>
                                <input type="text" name="ratings[date_dernier_privilege]" id="date_dernier_privilege" value="<?php if (false === empty($this->ratings['date_dernier_privilege']['value'])) : ?><?=  $this->dates->formatDate($this->ratings['date_dernier_privilege']['value'], 'd/m/Y') ?><?php endif; ?>" class="input_dp" readonly>
                            <?php elseif (false === empty($this->ratings['date_dernier_privilege']['value'])) : ?>
                                <span class="rating-tooltip" title="date_dernier_privilege"><?= $this->ratings['date_dernier_privilege']['value'] ?></span>
                            <?php else : ?>-<?php endif; ?>
                        <?php if (false === empty($this->ratings['date_dernier_privilege']['value'])) : ?></span><?php endif; ?>
                    </td>
                    <?php if (isset($this->targetRatings)) : ?>
                        <td>
                            <?php if (false === empty($this->ratings['date_dernier_privilege']['value'])) : ?><span class="rating-tooltip" title="target_date_dernier_privilege"><?php endif; ?>
                                <?php if ($this->projects->status <= \projects_status::COMITY_REVIEW) : ?>
                                    <input type="text" name="target_ratings[date_dernier_privilege]" id="target_date_dernier_privilege" value="<?php if (false === empty($this->ratings['date_dernier_privilege']['value'])) : ?><?=  $this->dates->formatDate($this->ratings['date_dernier_privilege']['value'], 'd/m/Y') ?><?php endif; ?>" class="input_dp" readonly>
                                <?php elseif (false === empty($this->ratings['date_dernier_privilege']['value'])) : ?>
                                    <span class="rating-tooltip" title="target_date_dernier_privilege"><?= $this->ratings['date_dernier_privilege']['value'] ?></span>
                                <?php else : ?>-<?php endif; ?>
                            <?php if (false === empty($this->ratings['date_dernier_privilege']['value'])) : ?></span><?php endif; ?>
                        </td>
                    <?php endif; ?>
                </tr>
                <tr>
                    <th><label for="date_tresorerie">Dernière situation de trésorerie connue</label></th>
                    <td>
                        <?php if ($this->projects->status <= \projects_status::COMITY_REVIEW) : ?>
                            <?php if (isset($this->ratings['date_tresorerie']) && '' !== $this->ratings['date_tresorerie']['value']) : ?>
                                <span class="rating-tooltip" title="date_tresorerie">
                                    <input type="text" name="ratings[date_tresorerie]" id="date_tresorerie" value="<?= $this->dates->formatDate($this->ratings['date_tresorerie']['value'], 'd/m/Y') ?>" class="input_dp" readonly>
                                </span>
                            <?php else : ?>
                                <input type="text" name="ratings[date_tresorerie]" id="date_tresorerie" class="input_dp" readonly>
                            <?php endif; ?>
                            &nbsp;&nbsp;
                            <?php if (isset($this->ratings['montant_tresorerie']) && '' !== $this->ratings['montant_tresorerie']['value']) : ?>
                                <span class="rating-tooltip" title="montant_tresorerie">
                                    <input type="text" name="ratings[montant_tresorerie]" id="montant_tresorerie" value="<?= $this->ficelle->formatNumber((float) $this->ratings['montant_tresorerie']['value'], 0) ?>" placeholder="€" class="input_court numbers">
                                </span>
                            <?php else : ?>
                                <input type="text" name="ratings[montant_tresorerie]" id="montant_tresorerie" placeholder="€" class="input_court numbers">
                            <?php endif; ?>
                        <?php elseif (isset($this->ratings['montant_tresorerie']) && '' !== $this->ratings['montant_tresorerie']['value'] || isset($this->ratings['date_tresorerie']) && '' !== $this->ratings['date_tresorerie']['value']) : ?>
                            <?php if (isset($this->ratings['montant_tresorerie']) && '' !== $this->ratings['montant_tresorerie']['value']) : ?><span class="rating-tooltip" title="montant_tresorerie"><?= $this->ficelle->formatNumber($this->ratings['montant_tresorerie']['value'], 0) ?>&nbsp;€</span><?php endif; ?>
                            <?php if (isset($this->ratings['montant_tresorerie'], $this->ratings['date_tresorerie']) && '' !== $this->ratings['montant_tresorerie']['value'] && '' !== $this->ratings['date_tresorerie']['value']) : ?> au <?php endif; ?>
                            <?php if (isset($this->ratings['date_tresorerie']) && '' !== $this->ratings['date_tresorerie']['value']) : ?><span class="rating-tooltip" title="date_tresorerie"><?= $this->dates->formatDate($this->ratings['date_tresorerie']['value'], 'd/m/Y') ?></span><?php endif; ?>
                        <?php else : ?>-<?php endif; ?>
                    </td>
                    <?php if (isset($this->targetRatings)) : ?>
                        <td>
                            <?php if ($this->projects->status <= \projects_status::COMITY_REVIEW) : ?>
                                <?php if (isset($this->targetRatings['date_tresorerie']) && '' !== $this->targetRatings['date_tresorerie']['value']) : ?>
                                    <span class="rating-tooltip" title="target_date_tresorerie">
                                        <input type="text" name="target_ratings[date_tresorerie]" id="target_date_tresorerie" value="<?= $this->dates->formatDate($this->targetRatings['date_tresorerie']['value'], 'd/m/Y') ?>" class="input_dp" readonly>
                                    </span>
                                <?php else : ?>
                                    <input type="text" name="target_ratings[date_tresorerie]" id="target_date_tresorerie" class="input_dp" readonly>
                                <?php endif; ?>
                                &nbsp;&nbsp;
                                <?php if (isset($this->targetRatings['montant_tresorerie']) && '' !== $this->targetRatings['montant_tresorerie']['value']) : ?>
                                    <span class="rating-tooltip" title="target_montant_tresorerie">
                                        <input type="text" name="target_ratings[montant_tresorerie]" id="target_montant_tresorerie" value="<?= $this->ficelle->formatNumber((float) $this->targetRatings['montant_tresorerie']['value'], 0) ?>" placeholder="€" class="input_court numbers">
                                    </span>
                                <?php else : ?>
                                    <input type="text" name="target_ratings[montant_tresorerie]" id="target_montant_tresorerie" placeholder="€" class="input_court numbers">
                                <?php endif; ?>
                            <?php elseif (isset($this->targetRatings['montant_tresorerie']) && '' !== $this->targetRatings['montant_tresorerie']['value'] || isset($this->targetRatings['date_tresorerie']) && '' !== $this->targetRatings['date_tresorerie']['value']) : ?>
                                <?php if (isset($this->targetRatings['montant_tresorerie']) && '' !== $this->targetRatings['montant_tresorerie']['value']) : ?><span class="rating-tooltip" title="target_montant_tresorerie"><?= $this->ficelle->formatNumber($this->targetRatings['montant_tresorerie']['value'], 0) ?>&nbsp;€</span><?php endif; ?>
                                <?php if (isset($this->targetRatings['montant_tresorerie'], $this->targetRatings['date_tresorerie']) && '' !== $this->targetRatings['montant_tresorerie']['value'] && '' !== $this->targetRatings['date_tresorerie']['value']) : ?> au <?php endif; ?>
                                <?php if (isset($this->targetRatings['date_tresorerie']) && '' !== $this->targetRatings['date_tresorerie']['value']) : ?><span class="rating-tooltip" title="target_date_tresorerie"><?= $this->dates->formatDate($this->targetRatings['date_tresorerie']['value'], 'd/m/Y') ?></span><?php endif; ?>
                            <?php else : ?>-<?php endif; ?>
                        </td>
                    <?php endif; ?>
                </tr>
                <tr>
                    <th><label for="delais_paiement_altares">Délais de paiement Altares (à date)</label></th>
                    <td>
                        <?php if (false === empty($this->ratings['delais_paiement_altares']['value'])) : ?><span class="rating-tooltip" title="delais_paiement_altares"><?php endif; ?>
                            <?php if ($this->projects->status <= \projects_status::COMITY_REVIEW) : ?>
                                <input type="text" name="ratings[delais_paiement_altares]" id="delais_paiement_altares" value="<?php if (false === empty($this->ratings['delais_paiement_altares']['value'])) : ?><?= $this->ratings['delais_paiement_altares']['value'] ?><?php endif; ?>" class="input_moy">
                            <?php elseif (false === empty($this->ratings['delais_paiement_altares']['value'])) : ?>
                                <span class="rating-tooltip" title="delais_paiement_altares"><?= $this->ratings['delais_paiement_altares']['value'] ?></span>
                            <?php else : ?>-<?php endif; ?>
                        <?php if (false === empty($this->ratings['delais_paiement_altares']['value'])) : ?></span><?php endif; ?>
                    </td>
                    <?php if (isset($this->targetRatings)) : ?>
                        <td>
                            <?php if (false === empty($this->targetRatings['delais_paiement_altares']['value'])) : ?><span class="rating-tooltip" title="target_delais_paiement_altares"><?php endif; ?>
                                <?php if ($this->projects->status <= \projects_status::COMITY_REVIEW) : ?>
                                    <input type="text" name="target_ratings[delais_paiement_altares]" id="target_delais_paiement_altares" value="<?php if (false === empty($this->targetRatings['delais_paiement_altares']['value'])) : ?><?= $this->targetRatings['delais_paiement_altares']['value'] ?><?php endif; ?>" class="input_moy">
                                <?php elseif (false === empty($this->targetRatings['delais_paiement_altares']['value'])) : ?>
                                    <span class="rating-tooltip" title="target_delais_paiement_altares"><?= $this->targetRatings['delais_paiement_altares']['value'] ?></span>
                                <?php else : ?>-<?php endif; ?>
                            <?php if (false === empty($this->targetRatings['delais_paiement_altares']['value'])) : ?></span><?php endif; ?>
                        </td>
                    <?php endif; ?>
                </tr>
                <tr>
                    <th><label for="delais_paiement_secteur">Délais de paiement du secteur</label></th>
                    <td>
                        <?php if (false === empty($this->ratings['delais_paiement_secteur']['value'])) : ?><span class="rating-tooltip" title="delais_paiement_secteur"><?php endif; ?>
                            <?php if ($this->projects->status <= \projects_status::COMITY_REVIEW) : ?>
                                <input type="text" name="ratings[delais_paiement_secteur]" id="delais_paiement_secteur" value="<?php if (false === empty($this->ratings['delais_paiement_secteur']['value'])) : ?><?= $this->ratings['delais_paiement_secteur']['value'] ?><?php endif; ?>" class="input_moy">
                            <?php elseif (false === empty($this->ratings['delais_paiement_secteur']['value'])) : ?>
                                <span class="rating-tooltip" title="delais_paiement_secteur"><?= $this->ratings['delais_paiement_secteur']['value'] ?></span>
                            <?php else : ?>-<?php endif; ?>
                        <?php if (false === empty($this->ratings['delais_paiement_secteur']['value'])) : ?></span><?php endif; ?>
                    </td>
                    <?php if (isset($this->targetRatings)) : ?>
                        <td>
                            <?php if (false === empty($this->targetRatings['delais_paiement_secteur']['value'])) : ?><span class="rating-tooltip" title="target_delais_paiement_secteur"><?php endif; ?>
                                <?php if ($this->projects->status <= \projects_status::COMITY_REVIEW) : ?>
                                    <input type="text" name="target_ratings[delais_paiement_secteur]" id="target_delais_paiement_secteur" value="<?php if (false === empty($this->targetRatings['delais_paiement_secteur']['value'])) : ?><?= $this->targetRatings['delais_paiement_secteur']['value'] ?><?php endif; ?>" class="input_moy">
                                <?php elseif (false === empty($this->targetRatings['delais_paiement_secteur']['value'])) : ?>
                                    <span class="rating-tooltip" title="target_delais_paiement_secteur"><?= $this->targetRatings['delais_paiement_secteur']['value'] ?></span>
                                <?php else : ?>-<?php endif; ?>
                            <?php if (false === empty($this->targetRatings['delais_paiement_secteur']['value'])) : ?></span><?php endif; ?>
                        </td>
                    <?php endif; ?>
                </tr>
                <tr>
                    <th><label>Dailly</label></th>
                    <td>
                        <?php if (isset($this->ratings['dailly']) && in_array($this->ratings['dailly']['value'], ['0', '1'], true)) : ?><span class="rating-tooltip" title="dailly"><?php endif; ?>
                            <?php if ($this->projects->status <= \projects_status::COMITY_REVIEW) : ?>
                                <label><input type="radio" name="ratings[dailly]" value="1"<?php if (isset($this->ratings['dailly']) && '1' === $this->ratings['dailly']['value']) : ?> checked<?php endif; ?>> Oui</label>
                                <label><input type="radio" name="ratings[dailly]" value="0"<?php if (isset($this->ratings['dailly']) && '0' === $this->ratings['dailly']['value']) : ?> checked<?php endif; ?>> Non</label>
                            <?php elseif (isset($this->ratings['dailly']) && '1' === $this->ratings['dailly']['value']) : ?>
                                <span class="rating-tooltip" title="dailly">Oui</span>
                            <?php elseif (isset($this->ratings['dailly']) && '0' === $this->ratings['dailly']['value']) : ?>
                                <span class="rating-tooltip" title="dailly">Non</span>
                            <?php else : ?>-<?php endif; ?>
                        <?php if (isset($this->ratings['dailly']) && in_array($this->ratings['dailly']['value'], ['0', '1'], true)) : ?></span><?php endif; ?>
                    </td>
                    <?php if (isset($this->targetRatings)) : ?>
                        <td>
                            <?php if (isset($this->targetRatings['dailly']) && in_array($this->targetRatings['dailly']['value'], ['0', '1'], true)) : ?><span class="rating-tooltip" title="target_dailly"><?php endif; ?>
                                <?php if ($this->projects->status <= \projects_status::COMITY_REVIEW) : ?>
                                    <label><input type="radio" name="target_ratings[dailly]" value="1"<?php if (isset($this->targetRatings['dailly']) && '1' === $this->targetRatings['dailly']['value']) : ?> checked<?php endif; ?>> Oui</label>
                                    <label><input type="radio" name="target_ratings[dailly]" value="0"<?php if (isset($this->targetRatings['dailly']) && '0' === $this->targetRatings['dailly']['value']) : ?> checked<?php endif; ?>> Non</label>
                                <?php elseif (isset($this->targetRatings['dailly']) && '1' === $this->targetRatings['dailly']['value']) : ?>
                                    <span class="rating-tooltip" title="target_dailly">Oui</span>
                                <?php elseif (isset($this->targetRatings['dailly']) && '0' === $this->targetRatings['dailly']['value']) : ?>
                                    <span class="rating-tooltip" title="target_dailly">Non</span>
                                <?php else : ?>-<?php endif; ?>
                            <?php if (isset($this->targetRatings['dailly']) && in_array($this->targetRatings['dailly']['value'], ['0', '1'], true)) : ?></span><?php endif; ?>
                        </td>
                    <?php endif; ?>
                </tr>
                <tr>
                    <th><label>Affacturage</label></th>
                    <td>
                        <?php if (isset($this->ratings['affacturage']) && in_array($this->ratings['affacturage']['value'], ['0', '1'], true)) : ?><span class="rating-tooltip" title="affacturage"><?php endif; ?>
                            <?php if ($this->projects->status <= \projects_status::COMITY_REVIEW) : ?>
                                <label><input type="radio" name="ratings[affacturage]" value="1"<?php if (isset($this->ratings['affacturage']) && '1' === $this->ratings['affacturage']['value']) : ?> checked<?php endif; ?>> Oui</label>
                                <label><input type="radio" name="ratings[affacturage]" value="0"<?php if (isset($this->ratings['affacturage']) && '0' === $this->ratings['affacturage']['value']) : ?> checked<?php endif; ?>> Non</label>
                            <?php elseif (isset($this->ratings['affacturage']) && '1' === $this->ratings['affacturage']['value']) : ?>
                                <span class="rating-tooltip" title="affacturage">Oui</span>
                            <?php elseif (isset($this->ratings['affacturage']) && '0' === $this->ratings['affacturage']['value']) : ?>
                                <span class="rating-tooltip" title="affacturage">Non</span>
                            <?php else : ?>-<?php endif; ?>
                        <?php if (isset($this->ratings['affacturage']) && in_array($this->ratings['affacturage']['value'], ['0', '1'], true)) : ?></span><?php endif; ?>
                    </td>
                    <?php if (isset($this->targetRatings)) : ?>
                        <td>
                            <?php if (isset($this->targetRatings['affacturage']) && in_array($this->targetRatings['affacturage']['value'], ['0', '1'], true)) : ?><span class="rating-tooltip" title="target_affacturage"><?php endif; ?>
                                <?php if ($this->projects->status <= \projects_status::COMITY_REVIEW) : ?>
                                    <label><input type="radio" name="target_ratings[affacturage]" value="1"<?php if (isset($this->targetRatings['affacturage']) && '1' === $this->targetRatings['affacturage']['value']) : ?> checked<?php endif; ?>> Oui</label>
                                    <label><input type="radio" name="target_ratings[affacturage]" value="0"<?php if (isset($this->targetRatings['affacturage']) && '0' === $this->targetRatings['affacturage']['value']) : ?> checked<?php endif; ?>> Non</label>
                                <?php elseif (isset($this->targetRatings['affacturage']) && '1' === $this->targetRatings['affacturage']['value']) : ?>
                                    <span class="rating-tooltip" title="target_affacturage">Oui</span>
                                <?php elseif (isset($this->targetRatings['affacturage']) && '0' === $this->targetRatings['affacturage']['value']) : ?>
                                    <span class="rating-tooltip" title="target_affacturage">Non</span>
                                <?php else : ?>-<?php endif; ?>
                            <?php if (isset($this->targetRatings['affacturage']) && in_array($this->targetRatings['affacturage']['value'], ['0', '1'], true)) : ?></span><?php endif; ?>
                        </td>
                    <?php endif; ?>
                </tr>
                </tbody>
            </table>
            <?php if ($this->projects->status <= \projects_status::COMITY_REVIEW) : ?>
                <div id="valid_etape4_1" class="valid_etape"><br>Données sauvegardées</div>
                <div class="btnDroite">
                    <input type="submit" class="btn_link" value="Sauvegarder">
                </div>
                <br>
            <?php endif; ?>
            <h2>Capital restant dû à date : <?= $this->ficelle->formatNumber($this->fCompanyOwedCapital) ?> €</h2>
            <br>
            <?php if (empty($this->aCompanyProjects)) : ?>
                <h2>Aucun autre projet pour cette société (SIREN identique)</h2>
            <?php else : ?>
                <h2><?= $this->iCompanyProjectsCount ?> projets de cette société (SIREN identique)</h2>
                <table id="company_projects" class="tablesorter">
                    <thead>
                        <tr>
                            <th class="header">ID</th>
                            <th class="header">Nom</th>
                            <th class="header">Date demande</th>
                            <th class="header">Date modification</th>
                            <th class="header">Montant</th>
                            <th class="header">Durée</th>
                            <th class="header">Statut</th>
                            <th class="header">Commercial</th>
                            <th class="header">Analyste</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($this->aCompanyProjects as $iIndex => $aProject) : ?>
                        <tr class="company_projects<?php if ($iIndex % 2) : ?> odd<?php endif; ?>" data-project="<?= $aProject['id_project'] ?>">
                            <td><?= $aProject['id_project'] ?></td>
                            <td><?= $aProject['title'] ?></td>
                            <td><?= $this->dates->formatDate($aProject['added'], 'd/m/Y') ?></td>
                            <td><?= $this->dates->formatDate($aProject['updated'], 'd/m/Y') ?></td>
                            <td><?= $this->ficelle->formatNumber($aProject['amount'], 0) ?>&nbsp;€</td>
                            <td><?= $aProject['period'] ?> mois</td>
                            <td><?= $aProject['status_label'] ?></td>
                            <td><?= $aProject['sales_person'] ?></td>
                            <td><?= $aProject['analyst'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if ($this->iCompanyProjectsCount > 8) : ?>
                    <div id="projectsPager" class="pager" style="text-align: center;">
                        <img src="<?= $this->surl ?>/images/admin/first.png" class="first">
                        <img src="<?= $this->surl ?>/images/admin/prev.png" class="prev">
                        <span class="pagedisplay"></span>
                        <img src="<?= $this->surl ?>/images/admin/next.png" class="next">
                        <img src="<?= $this->surl ?>/images/admin/last.png" class="last">
                        <select class="pagesize" style="display: none;">
                            <option value="8">8</option>
                        </select>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </form>
</div>
