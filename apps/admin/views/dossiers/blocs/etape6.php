<div id="content_etape6">
    <?php
    if ($this->current_projects_status->status >= \projects_status::REVUE_ANALYSTE) {
        $moyenne1 = (($this->projects_notes->performance_fianciere * 0.4) + ($this->projects_notes->marche_opere * 0.3) + ($this->projects_notes->qualite_moyen_infos_financieres * 0.2) + ($this->projects_notes->notation_externe * 0.1));
        $moyenne = round($moyenne1, 1);
        ?>
        <div class="tab_title" id="title_etape6">Etape 6</div>
        <div class="tab_content" id="etape6">
            <table class="form tableNotes" style="width: 100%;">
                <tr>
                    <th><label for="performance_fianciere">Performance financière</label></th>
                    <td><span id="performance_fianciere"><?= $this->projects_notes->performance_fianciere ?></span> / 10</td>
                    <th><label for="marche_opere">Marché opéré</label></th>
                    <td><span id="marche_opere"><?= $this->projects_notes->marche_opere ?></span> / 10</td>
                    <th><label for="qualite_moyen_infos_financieres">Qualité des moyens & infos financières</label></th>
                    <td><input tabindex="6" id="qualite_moyen_infos_financieres" class="input_court cal_moyen" type="text" value="<?= $this->projects_notes->qualite_moyen_infos_financieres ?>" name="qualite_moyen_infos_financieres" maxlength="4" onkeyup="nodizaines(this.value, this.id);" <?= ($this->bReadonlyRiskNote) ? 'readonly' : ''; ?>/> / 10</td>
                    <th><label for="notation_externe">Notation externe</label></th>
                    <td><input tabindex="7" id="notation_externe" class="input_court cal_moyen" type="text" value="<?= $this->projects_notes->notation_externe ?>" name="notation_externe" maxlength="4" onkeyup="nodizaines(this.value, this.id);" <?= ($this->bReadonlyRiskNote) ? 'readonly' : ''; ?>/> / 10</td>
                </tr>
                <tr>
                    <td colspan="2" style="vertical-align:top;">
                        <table>
                            <tr>
                                <th><label for="structure">Structure</label></th>
                                <td><input tabindex="1" class="input_court cal_moyen" type="text" value="<?= ($this->projects_notes->structure) ?>" name="structure" id="structure" maxlength="4" onkeyup="nodizaines(this.value, this.id);" <?= ($this->bReadonlyRiskNote) ? 'readonly' : ''; ?>/> / 10</td>
                            </tr>
                            <tr>
                                <th><label for="rentabilite">Rentabilité</label></th>
                                <td><input tabindex="2" class="input_court cal_moyen" type="text" value="<?= $this->projects_notes->rentabilite ?>" name="rentabilite" id="rentabilite" maxlength="4" onkeyup="nodizaines(this.value, this.id);" <?= ($this->bReadonlyRiskNote) ? 'readonly' : ''; ?>/> / 10</td>
                            </tr>
                            <tr>
                                <th><label for="tresorerie">Trésorerie</label></th>
                                <td><input tabindex="3" class="input_court cal_moyen" type="text" value="<?= $this->projects_notes->tresorerie ?>" name="tresorerie" id="tresorerie" maxlength="4" onkeyup="nodizaines(this.value, this.id);" <?= ($this->bReadonlyRiskNote) ? 'readonly' : ''; ?>/> / 10</td>
                            </tr>
                        </table>
                    </td>
                    <td colspan="2" style="vertical-align:top;">
                        <table>
                            <tr>
                                <th><label for="global">Global</label></th>
                                <td><input tabindex="4" class="input_court cal_moyen" type="text" value="<?= $this->projects_notes->global ?>" name="global" id="global" maxlength="4" onkeyup="nodizaines(this.value, this.id);"/> / 10</td>
                            </tr>
                            <tr>
                                <th><label for="individuel">Individuel</label></th>
                                <td><input tabindex="5" class="input_court cal_moyen" type="text" value="<?= $this->projects_notes->individuel ?>" name="individuel" id="individuel" maxlength="4" onkeyup="nodizaines(this.value, this.id);"/> / 10</td>
                            </tr>
                        </table>
                    </td>
                    <td colspan="4"></td>
                </tr>
                <tr class="lanote">
                    <th colspan="8" style="text-align:center;">Note : <span class="moyenneNote"><?= $moyenne ?>/ 10</span></th>
                </tr>
                <tr>
                    <td colspan="8" style="text-align:center;">
                        <?php if (false === $this->bReadonlyRiskNote): ?>
                            <label for="avis" style="text-align:left;display: block;">Avis :</label><br/>
                            <textarea tabindex="8" name="avis" style="height:700px;" id="avis" class="textarea_large avis"/><?= $this->projects_notes->avis ?></textarea>
                            <script type="text/javascript">var ckedAvis = CKEDITOR.replace('avis', {height: 700});</script>
                        <?php else: ?>
                            <div style="color:black;"><?= $this->projects_notes->avis ?></div>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
            <br/><br/>
            <div id="valid_etape6" class="valid_etape">Données sauvegardées</div>
            <div class="btnDroite listBtn_etape6">
                <?php if(false === $this->bReadonlyRiskNote): ?>
                    <input type="button" onclick="valid_rejete_etape6(3,<?= $this->projects->id_project ?>)" class="btn" value="Sauvegarder">
                <?php endif; ?>
                <?php if ($this->current_projects_status->status == 31): ?>
                    <input type="button" onclick="valid_rejete_etape6(1,<?= $this->projects->id_project ?>)" class="btn btnValid_rejet_etape6" style="background:#009933;border-color:#009933;" value="Valider">
                    <input type="button" onclick="valid_rejete_etape6(2,<?= $this->projects->id_project ?>)" class="btn btnValid_rejet_etape6" style="background:#CC0000;border-color:#CC0000;" value="Rejeter">
                <?php endif; ?>
            </div>
        </div>
        <script type="text/javascript">
            $(".cal_moyen").keyup(function () {
                // --- Chiffre et marché ---
                // Variables
                var structure = parseFloat($("#structure").val().replace(",", "."));
                var rentabilite = parseFloat($("#rentabilite").val().replace(",", "."));
                var tresorerie = parseFloat($("#tresorerie").val().replace(",", "."));
                var global = parseFloat($("#global").val().replace(",", "."));
                var individuel = parseFloat($("#individuel").val().replace(",", "."));

                // Arrondis
                structure = (Math.round(structure * 10) / 10);
                rentabilite = (Math.round(rentabilite * 10) / 10);
                tresorerie = (Math.round(tresorerie * 10) / 10);
                global = (Math.round(global * 10) / 10);
                individuel = (Math.round(individuel * 10) / 10);

                // Calcules
                var performance_fianciere = ((structure + rentabilite + tresorerie) / 3)
                performance_fianciere = (Math.round(performance_fianciere * 10) / 10);

                // Arrondis
                var marche_opere = ((global + individuel) / 2);
                marche_opere = (Math.round(marche_opere * 10) / 10);

                // --- Fin chiffre et marché ---

                // Variables
                var qualite_moyen_infos_financieres = parseFloat($("#qualite_moyen_infos_financieres").val().replace(",", "."));
                var notation_externe = parseFloat($("#notation_externe").val().replace(",", "."));

                // Arrondis
                qualite_moyen_infos_financieres = (Math.round(qualite_moyen_infos_financieres * 10) / 10);
                notation_externe = (Math.round(notation_externe * 10) / 10);

                // Calcules
                var moyenne1 = (((performance_fianciere * 0.4) + (marche_opere * 0.3) + (qualite_moyen_infos_financieres * 0.2) + (notation_externe * 0.1)));

                // Arrondis
                moyenne = (Math.round(moyenne1 * 10) / 10);

                // Affichage
                $("#marche_opere").html(marche_opere);
                $("#performance_fianciere").html(performance_fianciere);
                $(".moyenneNote").html(moyenne + "/ 10");
            });
        </script>
        <?php
    }
    ?>
</div>
