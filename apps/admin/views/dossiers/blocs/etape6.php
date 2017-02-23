<div id="content_etape6">
    <?php if (
        $this->projects->status >= \projects_status::ANALYSIS_REVIEW
        || $this->projects_status_history->projectHasHadStatus($this->projects->id_project, \projects_status::ANALYSIS_REVIEW)
    ) : ?>
        <?php $moyenne  = round($this->projects_notes->performance_fianciere * 0.2 + $this->projects_notes->marche_opere * 0.2 + $this->projects_notes->dirigeance * 0.2 + $this->projects_notes->indicateur_risque_dynamique * 0.4, 1); ?>
        <a class="tab_title" id="section-risk-analysis" href="#section-risk-analysis">6 - Analyse risque</a>
        <div class="tab_content<?php if (\users_types::TYPE_RISK == $_SESSION['user']['id_user_type']) : ?> expand<?php endif; ?>" id="etape6">
            <table class="form tableNotes" style="width: 100%;">
                <tr>
                    <th><label for="performance_fianciere">Performance financière</label></th>
                    <td><span id="performance_fianciere"><?= $this->projects_notes->performance_fianciere ?></span> / 10</td>
                    <th><label for="marche_opere">Marché opéré</label></th>
                    <td><span id="marche_opere"><?= $this->projects_notes->marche_opere ?></span> / 10</td>
                    <th><label for="dirigeance">Dirigeance</label></th>
                    <td>
                        <?php if ($this->projects->status == \projects_status::ANALYSIS_REVIEW) : ?>
                            <input tabindex="6" id="dirigeance" class="input_court cal_moyen" type="text" value="<?= $this->projects_notes->dirigeance ?>" name="dirigeance" maxlength="4" onkeyup="nodizaines(this.value, this.id);"<?= $this->bReadonlyRiskNote ? ' readonly' : '' ?> /> / 10
                        <?php else : ?>
                            <?= $this->projects_notes->dirigeance ?> / 10
                        <?php endif; ?>
                    </td>
                    <th><label for="indicateur_risque_dynamique">Indicateur de risque dynamique</label></th>
                    <td>
                        <?php if ($this->projects->status == \projects_status::ANALYSIS_REVIEW) : ?>
                            <input tabindex="7" id="indicateur_risque_dynamique" class="input_court cal_moyen" type="text" value="<?= $this->projects_notes->indicateur_risque_dynamique ?>" name="indicateur_risque_dynamique" maxlength="4" onkeyup="nodizaines(this.value, this.id);"<?= $this->bReadonlyRiskNote ? ' readonly' : ''; ?> /> / 10
                        <?php else : ?>
                            <?= $this->projects_notes->indicateur_risque_dynamique ?> / 10
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="vertical-align:top;">
                        <table>
                            <tr>
                                <th><label for="structure">Structure</label></th>
                                <td>
                                    <?php if ($this->projects->status == \projects_status::ANALYSIS_REVIEW) : ?>
                                        <input tabindex="1" class="input_court cal_moyen" type="text" value="<?= $this->projects_notes->structure ?>" name="structure" id="structure" maxlength="4" onkeyup="nodizaines(this.value, this.id);"<?= $this->bReadonlyRiskNote ? ' readonly' : '' ?> /> / 10
                                    <?php else : ?>
                                        <?= $this->projects_notes->structure ?> / 10
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="rentabilite">Rentabilité</label></th>
                                <td>
                                    <?php if ($this->projects->status == \projects_status::ANALYSIS_REVIEW) : ?>
                                        <input tabindex="2" class="input_court cal_moyen" type="text" value="<?= $this->projects_notes->rentabilite ?>" name="rentabilite" id="rentabilite" maxlength="4" onkeyup="nodizaines(this.value, this.id);"<?= $this->bReadonlyRiskNote ? ' readonly' : '' ?> /> / 10
                                    <?php else : ?>
                                        <?= $this->projects_notes->rentabilite ?> / 10
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="tresorerie">Trésorerie</label></th>
                                <td>
                                    <?php if ($this->projects->status == \projects_status::ANALYSIS_REVIEW) : ?>
                                        <input tabindex="3" class="input_court cal_moyen" type="text" value="<?= $this->projects_notes->tresorerie ?>" name="tresorerie" id="tresorerie" maxlength="4" onkeyup="nodizaines(this.value, this.id);"<?= $this->bReadonlyRiskNote ? ' readonly' : '' ?> /> / 10
                                    <?php else : ?>
                                        <?= $this->projects_notes->tresorerie ?> / 10
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td colspan="2" style="vertical-align:top;">
                        <table>
                            <tr>
                                <th><label for="global">Global</label></th>
                                <td>
                                    <?php if ($this->projects->status == \projects_status::ANALYSIS_REVIEW) : ?>
                                        <input tabindex="4" class="input_court cal_moyen" type="text" value="<?= $this->projects_notes->global ?>" name="global" id="global" maxlength="4" onkeyup="nodizaines(this.value, this.id);"<?= $this->bReadonlyRiskNote ? ' readonly' : '' ?> /> / 10
                                    <?php else : ?>
                                        <?= $this->projects_notes->global ?> / 10
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="individuel">Individuel</label></th>
                                <td>
                                    <?php if ($this->projects->status == \projects_status::ANALYSIS_REVIEW) : ?>
                                        <input tabindex="5" class="input_court cal_moyen" type="text" value="<?= $this->projects_notes->individuel ?>" name="individuel" id="individuel" maxlength="4" onkeyup="nodizaines(this.value, this.id);"<?= $this->bReadonlyRiskNote ? ' readonly' : '' ?> /> / 10
                                    <?php else : ?>
                                        <?= $this->projects_notes->individuel ?> / 10
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td colspan="4"></td>
                </tr>
                <tr class="lanote">
                    <th colspan="8" style="text-align:center;">Note : <span class="moyenneNote"><?= $moyenne ?> / 10</span></th>
                </tr>
                <tr>
                    <td colspan="8">
                        <?php if ($this->projects->status == \projects_status::ANALYSIS_REVIEW) : ?>
                            <label for="avis" style="text-align:left;display: block;">Avis :</label><br/>
                            <textarea tabindex="8" name="avis" style="height:700px;" id="avis" class="textarea_large avis"><?= $this->projects_notes->avis ?></textarea>
                            <script type="text/javascript">var ckedAvis = CKEDITOR.replace('avis', {height: 700});</script>
                        <?php else : ?>
                            <div style="color:black;"><?= $this->projects_notes->avis ?></div>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
            <br/><br/>
            <div id="valid_etape6" class="valid_etape">Données sauvegardées</div>
            <div class="btnDroite listBtn_etape6">
                <?php if ($this->projects->status == \projects_status::ANALYSIS_REVIEW) : ?>
                    <input type="button" onclick="valid_rejete_etape6(3, <?= $this->projects->id_project ?>)" class="btn" value="Sauvegarder">
                    <a href="<?= $this->lurl ?>/dossiers/ajax_rejection/6/<?= $this->projects->id_project ?>" class="btn btnValid_rejet_etape6 btn_link thickbox" style="background:#CC0000;border-color:#CC0000;">Rejeter</a>
                    <input type="button" onclick="valid_rejete_etape6(1, <?= $this->projects->id_project ?>)" class="btn btnValid_rejet_etape6" style="background:#009933;border-color:#009933;" value="Valider">
                <?php endif; ?>
            </div>
        </div>
        <script type="text/javascript">
            $(".cal_moyen").keyup(function () {
                var structure   = parseFloat($("#structure").val().replace(",", "."));
                var rentabilite = parseFloat($("#rentabilite").val().replace(",", "."));
                var tresorerie  = parseFloat($("#tresorerie").val().replace(",", "."));
                var global      = parseFloat($("#global").val().replace(",", "."));
                var individuel  = parseFloat($("#individuel").val().replace(",", "."));

                structure   = Math.round(structure * 10) / 10;
                rentabilite = Math.round(rentabilite * 10) / 10;
                tresorerie  = Math.round(tresorerie * 10) / 10;
                global      = Math.round(global * 10) / 10;
                individuel  = Math.round(individuel * 10) / 10;

                var performance_fianciere = (structure + rentabilite + tresorerie) / 3;
                performance_fianciere = Math.round(performance_fianciere * 10) / 10;

                var marche_opere = (global + individuel) / 2;
                marche_opere = Math.round(marche_opere * 10) / 10;

                var dirigeance = parseFloat($("#dirigeance").val().replace(",", "."));
                var indicateur_risque_dynamique = parseFloat($("#indicateur_risque_dynamique").val().replace(",", "."));

                dirigeance = Math.round(dirigeance * 10) / 10;
                indicateur_risque_dynamique = Math.round(indicateur_risque_dynamique * 10) / 10;

                moyenne = Math.round((performance_fianciere * 0.2 + marche_opere * 0.2 + dirigeance * 0.2 + indicateur_risque_dynamique * 0.4) * 10) / 10;

                $("#marche_opere").html(marche_opere);
                $("#performance_fianciere").html(performance_fianciere);
                $(".moyenneNote").html(moyenne + " / 10");
            });
        </script>
    <?php endif; ?>
</div>
