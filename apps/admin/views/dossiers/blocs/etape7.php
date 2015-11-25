<div id="content_etape7">
    <?php if ($this->current_projects_status->status >= \projects_status::COMITE): ?>
        <div class="tab_title" id="title_etape7">Etape 7</div>
        <div class="tab_content" id="etape7">
            <table class="form tableNotes" style="width: 100%;">
                <tr>
                    <th><label for="performance_fianciere2">Performance financière</label></th>
                    <td><span id="performance_fianciere2"><?= $this->projects_notes->performance_fianciere ?></span> / 10</td>
                    <th style="vertical-align:top;"><label for="marche_opere2">Marché opéré</label></th>
                    <td style="vertical-align:top;"><span id="marche_opere2"><?= $this->projects_notes->marche_opere ?></span> / 10</td>
                    <th><label for="qualite_moyen_infos_financieres2">Qualité des moyens & infos financières</label></th>
                    <td><input tabindex="14" id="qualite_moyen_infos_financieres2" class="input_court cal_moyen" type="text" value="<?= $this->projects_notes->qualite_moyen_infos_financieres ?>" name="qualite_moyen_infos_financieres" maxlength="4" onkeyup="nodizaines(this.value, this.id);" <?= ($this->bReadonlyRiskNote) ? 'readonly' : ''; ?>/> / 10</td>
                    <th><label for="notation_externe2">Notation externe</label></th>
                    <td><input tabindex="15" id="notation_externe2" class="input_court cal_moyen" type="text" value="<?= $this->projects_notes->notation_externe ?>" name="notation_externe" maxlength="4" onkeyup="nodizaines(this.value, this.id);" <?= ($this->bReadonlyRiskNote) ? 'readonly' : ''; ?>/> / 10</td>
                </tr>
                <tr>
                    <td colspan="2" style="vertical-align:top;">
                        <table>
                            <tr>
                                <th><label for="structure2">Structure</label></th>
                                <td><input tabindex="9" class="input_court cal_moyen" type="text" value="<?= $this->projects_notes->structure ?>" name="structure2" id="structure2" maxlength="4" onkeyup="nodizaines(this.value, this.id);"/> / 10</td>
                            </tr>
                            <tr>
                                <th><label for="rentabilite2">Rentabilité</label></th>
                                <td><input tabindex="10" class="input_court cal_moyen" type="text" value="<?= $this->projects_notes->rentabilite ?>" name="rentabilite2" id="rentabilite2" maxlength="4" onkeyup="nodizaines(this.value, this.id);"/> / 10</td>
                            </tr>
                            <tr>
                                <th><label for="tresorerie2">Trésorerie</label></th>
                                <td>
                                    <input tabindex="11" class="input_court cal_moyen" type="text" value="<?= $this->projects_notes->tresorerie ?>" name="tresorerie2" id="tresorerie2" maxlength="4" onkeyup="nodizaines(this.value, this.id);" <?= ($this->bReadonlyRiskNote) ? 'readonly' : ''; ?>/> / 10
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td colspan="2" style="vertical-align:top;">
                        <table>
                            <tr>
                                <th><label for="global2">Global</label></th>
                                <td><input tabindex="12" class="input_court cal_moyen" type="text" value="<?= $this->projects_notes->global ?>" name="global2" id="global2" maxlength="4" onkeyup="nodizaines(this.value, this.id);"/> / 10</td>
                            </tr>
                            <tr>
                                <th><label for="individuel2">Individuel</label></th>
                                <td><input tabindex="13" class="input_court cal_moyen" type="text" value="<?= $this->projects_notes->individuel ?>" name="individuel2" id="individuel2" maxlength="4" onkeyup="nodizaines(this.value, this.id);" <?= ($this->bReadonlyRiskNote) ? 'readonly' : ''; ?>/> / 10</td>
                            </tr>
                        </table>
                    </td>
                    <td colspan="4"></td>
                </tr>
                <tr class="lanote">
                    <th colspan="8" style="text-align:center;">Note : <span class="moyenneNote2"><?= $moyenne ?> / 10</span></th>
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
                    // --- Chiffre et marché ---

                    // Variables
                    var structure = parseFloat($("#structure2").val().replace(",", "."));
                    var rentabilite = parseFloat($("#rentabilite2").val().replace(",", "."));
                    var tresorerie = parseFloat($("#tresorerie2").val().replace(",", "."));

                    var global = parseFloat($("#global2").val().replace(",", "."));
                    var individuel = parseFloat($("#individuel2").val().replace(",", "."));

                    // Arrondis
                    structure = (Math.round(structure * 10) / 10);
                    rentabilite = (Math.round(rentabilite * 10) / 10);
                    tresorerie = (Math.round(tresorerie * 10) / 10);

                    global = (Math.round(global * 10) / 10);
                    individuel = (Math.round(individuel * 10) / 10);

                    // Calcules
                    var performance_fianciere = ((structure + rentabilite + tresorerie) / 3);
                    performance_fianciere = (Math.round(performance_fianciere * 10) / 10);

                    // Arrondis
                    var marche_opere = ((global + individuel) / 2)
                    marche_opere = (Math.round(marche_opere * 10) / 10);

                    // --- Fin chiffre et marché ---

                    // Variables
                    var qualite_moyen_infos_financieres = parseFloat($("#qualite_moyen_infos_financieres2").val().replace(",", "."));
                    var notation_externe = parseFloat($("#notation_externe2").val().replace(",", "."));

                    // Arrondis
                    qualite_moyen_infos_financieres = (Math.round(qualite_moyen_infos_financieres * 10) / 10);
                    notation_externe = (Math.round(notation_externe * 10) / 10);

                    // Calcules
                    var moyenne1 = (((performance_fianciere * 0.4) + (marche_opere * 0.3) + (qualite_moyen_infos_financieres * 0.2) + (notation_externe * 0.1)));

                    // Arrondis
                    moyenne = (Math.round(moyenne1 * 10) / 10);

                    // Affichage
                    $("#marche_opere2").html(marche_opere);
                    $("#performance_fianciere2").html(performance_fianciere);
                    $(".moyenneNote2").html(moyenne + "/ 10");
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
