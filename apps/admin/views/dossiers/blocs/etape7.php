<div id="content_etape7">
    <?php if ($this->current_projects_status->status >= \projects_status::COMITE) : ?>
        <?php $moyenne  = round($this->projects_notes->performance_fianciere_comite * 0.2 + $this->projects_notes->marche_opere_comite * 0.2 + $this->projects_notes->qualite_moyen_infos_financieres_comite * 0.2 + $this->projects_notes->notation_externe_comite * 0.4, 1); ?>
        <div class="tab_title" id="title_etape7">Etape 7</div>
        <div class="tab_content" id="etape7">
            <table class="form tableNotes" style="width: 100%;">
                <tr>
                    <th><label for="performance_fianciere_comite">Performance financière</label></th>
                    <td><span id="performance_fianciere_comite"><?= $this->projects_notes->performance_fianciere_comite ?></span> / 10</td>
                    <th style="vertical-align:top;"><label for="marche_opere_comite">Marché opéré</label></th>
                    <td style="vertical-align:top;"><span id="marche_opere_comite"><?= $this->projects_notes->marche_opere_comite ?></span> / 10</td>
                    <th><label for="qualite_moyen_infos_financieres2">Qualité des moyens & infos financières</label></th>
                    <td><input tabindex="14" id="qualite_moyen_infos_financieres_comite" name="qualite_moyen_infos_financieres_comite" class="input_court cal_moyen" type="text" value="<?= $this->projects_notes->qualite_moyen_infos_financieres_comite ?>" maxlength="4" onkeyup="nodizaines(this.value, this.id);"<?= $this->bReadonlyRiskNote ? ' readonly' : '' ?> /> / 10</td>
                    <th><label for="notation_externe2">Notation externe</label></th>
                    <td><input tabindex="15" id="notation_externe_comite" name="notation_externe_comite" class="input_court cal_moyen" type="text" value="<?= $this->projects_notes->notation_externe_comite ?>" maxlength="4" onkeyup="nodizaines(this.value, this.id);"<?= $this->bReadonlyRiskNote ? ' readonly' : '' ?> /> / 10</td>
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
                    <th colspan="8" style="text-align:center;">Note : <span class="moyenneNote_comite"><?= $moyenne ?> / 10</span></th>
                </tr>
                <tr>
                    <td colspan="8" style="text-align:center;">
                        <?php if (false === $this->bReadonlyRiskNote): ?>
                            <label for="avis_comite" style="text-align:left;display: block;">Avis comité :</label><br/>
                            <textarea tabindex="16" name="avis_comite" style="height:700px;" id="avis_comite" class="textarea_large avis_comite"><?= $this->projects_notes->avis_comite ?></textarea>
                            <script type="text/javascript">var ckedAvis_comite = CKEDITOR.replace('avis_comite', {height: 700});</script>
                        <?php else: ?>
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

                    var qualite_moyen_infos_financieres = parseFloat($("#qualite_moyen_infos_financieres_comite").val().replace(",", "."));
                    var notation_externe = parseFloat($("#notation_externe_comite").val().replace(",", "."));

                    qualite_moyen_infos_financieres = Math.round(qualite_moyen_infos_financieres * 10) / 10;
                    notation_externe = Math.round(notation_externe * 10) / 10;

                    moyenne = Math.round((performance_fianciere * 0.2 + marche_opere * 0.2 + qualite_moyen_infos_financieres * 0.2 + notation_externe * 0.4) * 10) / 10;

                    $("#marche_opere_comite").html(marche_opere);
                    $("#performance_fianciere_comite").html(performance_fianciere);
                    $(".moyenneNote_comite").html(moyenne + " / 10");
                });
            </script>
            <br/><br/>

            <div id="valid_etape7" class="valid_etape">Données sauvegardées</div>
            <div class="btnDroite">
                <?php if (false === $this->bReadonlyRiskNote): ?>
                    <input type="button" onclick="valid_rejete_etape7(3, <?= $this->projects->id_project ?>)" class="btn" value="Sauvegarder">
                <?php endif; ?>
                <?php if ($this->current_projects_status->status == \projects_status::COMITE): ?>
                    <input type="button" onclick="valid_rejete_etape7(1, <?= $this->projects->id_project ?>)" class="btn btnValid_rejet_etape7" style="background:#009933;border-color:#009933;" value="Valider">
                    <input type="button" onclick="valid_rejete_etape7(2, <?= $this->projects->id_project ?>)" class="btn btnValid_rejet_etape7" style="background:#CC0000;border-color:#CC0000;" value="Rejeter">
                    <input type="button" onclick="valid_rejete_etape7(4, <?= $this->projects->id_project ?>)" class="btn btnValid_rejet_etape7" value="Plus d'informations">
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
