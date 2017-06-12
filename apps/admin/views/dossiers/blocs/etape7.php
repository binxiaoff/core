<?php if ($this->projects->status >= \projects_status::COMITY_REVIEW || $this->projects_status_history->projectHasHadStatus($this->projects->id_project, \projects_status::COMITY_REVIEW)) : ?>
    <div id="content_etape7">
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
        <a class="tab_title" id="section-risk-comity" href="#section-risk-comity">7. Comité risque</a>
        <div class="tab_content<?php if (\users_types::TYPE_RISK == $_SESSION['user']['id_user_type']) : ?> expand<?php endif; ?>" id="etape7">
            <table class="form tableNotes" style="width: 100%;">
                <tr>
                    <th><label for="performance_fianciere_comite">Performance financière</label></th>
                    <td><span id="performance_fianciere_comite"><?= $this->projects_notes->performance_fianciere_comite ?></span> / 10</td>
                    <th style="vertical-align:top;"><label for="marche_opere_comite">Marché opéré</label></th>
                    <td style="vertical-align:top;"><span id="marche_opere_comite"><?= $this->projects_notes->marche_opere_comite ?></span> / 10</td>
                    <th><label for="dirigeance_comite">Dirigeance</label></th>
                    <td>
                        <?php if ($this->projects->status == \projects_status::COMITY_REVIEW) : ?>
                            <input id="dirigeance_comite" name="dirigeance_comite" value="<?= $this->projects_notes->dirigeance_comite ?>" type="text" maxlength="4" tabindex="14" class="input_court cal_moyen" onkeyup="nodizaines(this.value, this.id);"> / 10
                        <?php else : ?>
                            <?= $this->projects_notes->dirigeance_comite ?> / 10
                        <?php endif; ?>
                    </td>
                    <th><label for="indicateur_risque_dynamique_comite">Indicateur risque dynamique</label></th>
                    <td>
                        <?php if ($this->projects->status == \projects_status::COMITY_REVIEW) : ?>
                            <input id="indicateur_risque_dynamique_comite" name="indicateur_risque_dynamique_comite" value="<?= $this->projects_notes->indicateur_risque_dynamique_comite ?>" type="text" maxlength="4" tabindex="15" class="input_court cal_moyen" onkeyup="nodizaines(this.value, this.id);"> / 10
                        <?php else : ?>
                            <?= $this->projects_notes->indicateur_risque_dynamique_comite ?> / 10
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><label for="structure_comite">Structure</label></th>
                    <td>
                        <?php if ($this->projects->status == \projects_status::COMITY_REVIEW) : ?>
                            <input id="structure_comite" name="structure_comite" value="<?= $this->projects_notes->structure_comite ?>" type="text" maxlength="4" tabindex="9" class="input_court cal_moyen" onkeyup="nodizaines(this.value, this.id);"> / 10
                        <?php else : ?>
                            <?= $this->projects_notes->structure_comite ?> / 10
                        <?php endif; ?>
                    </td>
                    <th><label for="global_comite">Global</label></th>
                    <td colspan="5">
                        <?php if ($this->projects->status == \projects_status::COMITY_REVIEW) : ?>
                            <input id="global_comite" name="global_comite" value="<?= $this->projects_notes->global_comite ?>" type="text" maxlength="4" tabindex="12" class="input_court cal_moyen" onkeyup="nodizaines(this.value, this.id);"> / 10
                        <?php else : ?>
                            <?= $this->projects_notes->global_comite ?> / 10
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><label for="rentabilite_comite">Rentabilité</label></th>
                    <td>
                        <?php if ($this->projects->status == \projects_status::COMITY_REVIEW) : ?>
                            <input id="rentabilite_comite" name="rentabilite_comite" value="<?= $this->projects_notes->rentabilite_comite ?>" type="text" maxlength="4" tabindex="10" class="input_court cal_moyen" onkeyup="nodizaines(this.value, this.id);"> / 10
                        <?php else : ?>
                            <?= $this->projects_notes->rentabilite_comite ?> / 10
                        <?php endif; ?>
                    </td>
                    <th><label for="individuel_comite">Individuel</label></th>
                    <td colspan="5">
                        <?php if ($this->projects->status == \projects_status::COMITY_REVIEW) : ?>
                            <input id="individuel_comite" name="individuel_comite" value="<?= $this->projects_notes->individuel_comite ?>" type="text" maxlength="4" tabindex="13" class="input_court cal_moyen" onkeyup="nodizaines(this.value, this.id);"> / 10
                        <?php else : ?>
                            <?= $this->projects_notes->individuel_comite ?> / 10
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><label for="tresorerie_comite">Trésorerie</label></th>
                    <td colspan="7">
                        <?php if ($this->projects->status == \projects_status::COMITY_REVIEW) : ?>
                            <input id="tresorerie_comite" name="tresorerie_comite" value="<?= $this->projects_notes->tresorerie_comite ?>" type="text" maxlength="4" tabindex="11" class="input_court cal_moyen" onkeyup="nodizaines(this.value, this.id);"> / 10
                        <?php else : ?>
                            <?= $this->projects_notes->tresorerie_comite ?> / 10
                        <?php endif; ?>
                    </td>
                </tr>
                <tr class="lanote">
                    <th colspan="8" style="text-align:center;">Note : <span class="moyenneNote_comite"><?= $moyenne ?> / 10 (soit <?= $start ?>)</span></th>
                </tr>
                <tr>
                    <td colspan="8">
                        <?php if ($this->projects->status == \projects_status::COMITY_REVIEW) : ?>
                            <label for="avis_comite" style="text-align:left;display: block;">Avis comité</label><br>
                            <textarea tabindex="16" name="avis_comite" style="height:700px;" id="avis_comite" class="textarea_large avis_comite"><?= $this->projects_notes->avis_comite ?></textarea>
                            <script type="text/javascript">var ckedAvis_comite = CKEDITOR.replace('avis_comite', {height: 700});</script>
                        <?php else : ?>
                            <div style="color:black;"><?= $this->projects_notes->avis_comite ?></div>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
            <div id="valid_etape7" class="valid_etape"><br><br>Données sauvegardées</div>
            <?php if ($this->projects->status == \projects_status::COMITY_REVIEW) : ?>
                <div class="btnDroite">
                    <input id="min_rate" type="hidden" value="<?= isset($this->rate_min) ? $this->rate_min : '' ?>">
                    <input id="max_rate" type="hidden" value="<?= isset($this->rate_max) ? $this->rate_max : '' ?>">
                    <input type="button" onclick="valid_rejete_etape7(3, <?= $this->projects->id_project ?>)" class="btn" value="Sauvegarder">
                    <a href="<?= $this->lurl ?>/dossiers/comity_to_analysis/<?= $this->projects->id_project ?>" class="btn btn_link thickbox">Retour à l'analyse</a>
                    <a href="<?= $this->lurl ?>/dossiers/ajax_rejection/7/<?= $this->projects->id_project ?>" class="btn btn_link thickbox" style="background:#CC0000;border-color:#CC0000;">Rejeter</a>
                    <a href="<?= $this->lurl ?>/dossiers/suspensive_conditions/<?= $this->projects->id_project ?>" class="btn btn_link thickbox" style="background:#009933;border-color:#009933;">Conditions suspensives de mise en ligne</a>
                    <input type="button" onclick="valid_rejete_etape7(1, <?= $this->projects->id_project ?>)" class="btn" style="background:#009933;border-color:#009933;" value="Valider">
                </div>
            <?php endif; ?>
        </div>
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
    </div>
<?php endif; ?>
