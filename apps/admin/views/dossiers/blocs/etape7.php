<div id="content_etape7">
    <?php if (
        $this->projects->status >= \projects_status::COMITY_REVIEW
        || $this->projects_status_history->projectHasHadStatus($this->projects->id_project, \projects_status::COMITY_REVIEW)
    ) : ?>
        <?php
        $moyenne  = round($this->projects_notes->performance_fianciere_comite * 0.2 + $this->projects_notes->marche_opere_comite * 0.2 + $this->projects_notes->dirigeance_comite * 0.2 + $this->projects_notes->indicateur_risque_dynamique_comite * 0.4, 1);
        $start = '';
        if ($moyenne >= 0) {
            $start = '2 étoiles';
        }
        if ($moyenne >= 2) {
            $start = '2,5 étoiles';
        }
        if ($moyenne >= 4) {
            $start = '3 étoiles';
        }
        if ($moyenne >= 5.5) {
            $start = '3,5 étoiles';
        }
        if ($moyenne >= 6.5) {
            $start = '4 étoiles';
        }
        if ($moyenne >= 7.5) {
            $start = '4,5 étoiles';
        }
        if ($moyenne >= 8.5) {
            $start = '5 étoiles';
        }
        ?>
        <a class="tab_title" id="section-risk-comity" href="#section-risk-comity">7 - Comité risque</a>
        <div class="tab_content<?php if (\users_types::TYPE_RISK == $_SESSION['user']['id_user_type']) : ?> expand<?php endif; ?>" id="etape7">
            <table class="form tableNotes" style="width: 100%;">
                <tr>
                    <th><label for="performance_fianciere_comite">Performance financière</label></th>
                    <td><span id="performance_fianciere_comite"><?= $this->projects_notes->performance_fianciere_comite ?></span> / 10</td>
                    <th style="vertical-align:top;"><label for="marche_opere_comite">Marché opéré</label></th>
                    <td style="vertical-align:top;"><span id="marche_opere_comite"><?= $this->projects_notes->marche_opere_comite ?></span> / 10</td>
                    <th><label for="dirigeance_comite">Dirigeance</label></th>
                    <td><input tabindex="14" id="dirigeance_comite" name="dirigeance_comite" class="input_court cal_moyen" type="text" value="<?= $this->projects_notes->dirigeance_comite ?>" maxlength="4" onkeyup="nodizaines(this.value, this.id);"<?= $this->bReadonlyRiskNote ? ' readonly' : '' ?> /> / 10</td>
                    <th><label for="indicateur_risque_dynamique_comite">Indicateur de risque dynamique</label></th>
                    <td><input tabindex="15" id="indicateur_risque_dynamique_comite" name="indicateur_risque_dynamique_comite" class="input_court cal_moyen" type="text" value="<?= $this->projects_notes->indicateur_risque_dynamique_comite ?>" maxlength="4" onkeyup="nodizaines(this.value, this.id);"<?= $this->bReadonlyRiskNote ? ' readonly' : '' ?> /> / 10</td>
                </tr>
                <tr>
                    <td colspan="2" style="vertical-align:top;">
                        <table>
                            <tr>
                                <th><label for="structure2">Structure</label></th>
                                <td><input tabindex="9" id="structure_comite" name="structure_comite" class="input_court cal_moyen" type="text" value="<?= $this->projects_notes->structure_comite ?>" maxlength="4" onkeyup="nodizaines(this.value, this.id);"<?= $this->bReadonlyRiskNote ? ' readonly' : '' ?> /> / 10</td>
                            </tr>
                            <tr>
                                <th><label for="rentabilite2">Rentabilité</label></th>
                                <td><input tabindex="10" id="rentabilite_comite" name="rentabilite_comite" class="input_court cal_moyen" type="text" value="<?= $this->projects_notes->rentabilite_comite ?>" maxlength="4" onkeyup="nodizaines(this.value, this.id);"<?= $this->bReadonlyRiskNote ? ' readonly' : '' ?> /> / 10</td>
                            </tr>
                            <tr>
                                <th><label for="tresorerie2">Trésorerie</label></th>
                                <td>
                                    <input tabindex="11" id="tresorerie_comite" name="tresorerie_comite" class="input_court cal_moyen" type="text" value="<?= $this->projects_notes->tresorerie_comite ?>" maxlength="4" onkeyup="nodizaines(this.value, this.id);"<?= $this->bReadonlyRiskNote ? ' readonly' : '' ?> /> / 10
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td colspan="2" style="vertical-align:top;">
                        <table>
                            <tr>
                                <th><label for="global2">Global</label></th>
                                <td><input tabindex="12" id="global_comite" name="global_comite" class="input_court cal_moyen" type="text" value="<?= $this->projects_notes->global_comite ?>" maxlength="4" onkeyup="nodizaines(this.value, this.id);"<?= $this->bReadonlyRiskNote ? ' readonly' : '' ?> /> / 10</td>
                            </tr>
                            <tr>
                                <th><label for="individuel2">Individuel</label></th>
                                <td><input tabindex="13" id="individuel_comite" name="individuel_comite" class="input_court cal_moyen" type="text" value="<?= $this->projects_notes->individuel_comite ?>" maxlength="4" onkeyup="nodizaines(this.value, this.id);"<?= $this->bReadonlyRiskNote ? ' readonly' : '' ?> /> / 10</td>
                            </tr>
                        </table>
                    </td>
                    <td colspan="4"></td>
                </tr>
                <tr class="lanote">
                    <th colspan="8" style="text-align:center;">Note : <span class="moyenneNote_comite"><?= $moyenne ?> / 10 (soit <?= $start ?>)</span></th>
                </tr>
                <tr>
                    <td colspan="8" style="text-align:center;">
                        <?php if (false === $this->bReadonlyRiskNote) : ?>
                            <label for="avis_comite" style="text-align:left;display: block;">Avis comité :</label><br/>
                            <textarea tabindex="16" name="avis_comite" style="height:700px;" id="avis_comite" class="textarea_large avis_comite"><?= $this->projects_notes->avis_comite ?></textarea>
                            <script type="text/javascript">var ckedAvis_comite = CKEDITOR.replace('avis_comite', {height: 700});</script>
                        <?php else : ?>
                            <div style="color:black;"><?= $this->projects_notes->avis_comite ?></div>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
            <script type="text/javascript">
                $(".cal_moyen").keyup(function() {
                    var structure   = parseFloat($("#structure_comite").val().replace(",", "."));
                    var rentabilite = parseFloat($("#rentabilite_comite").val().replace(",", "."));
                    var tresorerie  = parseFloat($("#tresorerie_comite").val().replace(",", "."));
                    var global      = parseFloat($("#global_comite").val().replace(",", "."));
                    var individuel  = parseFloat($("#individuel_comite").val().replace(",", "."));

                    structure   = Math.round(structure * 10) / 10;
                    rentabilite = Math.round(rentabilite * 10) / 10;
                    tresorerie  = Math.round(tresorerie * 10) / 10;
                    global      = Math.round(global * 10) / 10;
                    individuel  = Math.round(individuel * 10) / 10;

                    var performance_fianciere = (structure + rentabilite + tresorerie) / 3;
                    performance_fianciere = Math.round(performance_fianciere * 10) / 10;

                    var marche_opere = (global + individuel) / 2;
                    marche_opere = Math.round(marche_opere * 10) / 10;

                    var dirigeance = parseFloat($("#dirigeance_comite").val().replace(",", "."));
                    var indicateur_risque_dynamique = parseFloat($("#indicateur_risque_dynamique_comite").val().replace(",", "."));

                    dirigeance = Math.round(dirigeance * 10) / 10;
                    indicateur_risque_dynamique = Math.round(indicateur_risque_dynamique * 10) / 10;

                    moyenne = Math.round((performance_fianciere * 0.2 + marche_opere * 0.2 + dirigeance * 0.2 + indicateur_risque_dynamique * 0.4) * 10) / 10;

                    $("#marche_opere_comite").html(marche_opere);
                    $("#performance_fianciere_comite").html(performance_fianciere);
                    var start = '';
                    if (moyenne >= 0) {
                        start = '2 étoiles';
                    }
                    if (moyenne >= 2) {
                        start = '2,5 étoiles';
                    }
                    if (moyenne >= 4) {
                        start = '3 étoiles';
                    }
                    if (moyenne >= 5.5) {
                        start = '3,5 étoiles';
                    }
                    if (moyenne >= 6.5) {
                        start = '4 étoiles';
                    }
                    if (moyenne >= 7.5) {
                        start = '4,5 étoiles';
                    }
                    if (moyenne >= 8.5) {
                        start = '5 étoiles';
                    }
                    $(".moyenneNote_comite").html(moyenne + " / 10" + ' (soit ' + start + ')');
                });
            </script>
            <br/><br/>

            <div id="valid_etape7" class="valid_etape">Données sauvegardées</div>
            <div class="btnDroite">
                <?php if (false === $this->bReadonlyRiskNote) : ?>
                    <input type="button" onclick="valid_rejete_etape7(3, <?= $this->projects->id_project ?>)" class="btn" value="Sauvegarder">
                <?php endif; ?>
                <?php if ($this->projects->status == \projects_status::COMITY_REVIEW) : ?>
                    <input id="min_rate" type="hidden" value="<?= isset($this->rate_min) ? $this->rate_min : '' ?>" />
                    <input id="max_rate" type="hidden" value="<?= isset($this->rate_max) ? $this->rate_max : '' ?>" />
                    <input type="button" onclick="valid_rejete_etape7(1, <?= $this->projects->id_project ?>)" class="btn btnValid_rejet_etape7" style="background:#009933;border-color:#009933;" value="Valider">
                    <a href="<?= $this->lurl ?>/dossiers/ajax_rejection/7/<?= $this->projects->id_project ?>" class="btn btnValid_rejet_etape7 btn_link thickbox" style="background:#CC0000;border-color:#CC0000;">Rejeter</a>
                    <input type="button" onclick="valid_rejete_etape7(4, <?= $this->projects->id_project ?>)" class="btn btnValid_rejet_etape7" value="Plus d'informations">
                <?php endif; ?>
            </div>
        </div>
<?php endif; ?></div>
