<script type="text/javascript">
    $(function() {
        $('#date_dernier_privilege, #date_tresorerie').datepicker({
            showOn: 'both',
            buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            yearRange: '<?= (date('Y') - 40) ?>:<?= (date('Y')) ?>'
        });

        $('#company_projects').tablesorter();
        $('.company_projects').click(function() {
            $(location).attr('href', '<?= $this->lurl ?>/dossiers/edit/' + $(this).data('project'));
        });

        var displayBankName = function(event) {
            if ('' == $(event.target).val()) {
                $('#nom_banque').parents('tr').hide();
            } else {
                $('#nom_banque').parents('tr').show();
            }
        };

        $('#note_interne_banque')
            .change(displayBankName)
            .keyup(displayBankName)
            .keydown(displayBankName);
    });
</script>
<style>
    .company_projects {cursor: pointer;}
</style>
<div class="tab_title" id="title_etape4_1">Etape 4.1 - Notation externe</div>
<div class="tab_content" id="etape4_1">
    <form method="post" name="dossier_etape4_1" id="dossier_etape4_1" onsubmit="valid_etape4_1(<?= $this->projects->id_project ?>); return false;" enctype="multipart/form-data" action="<?= $this->lurl ?>/dossiers/edit/<?= $this->params[0] ?>" target="_parent">
        <div id="contenu_etape4_1">
            <table class="form" style="width: 100%;">
                <tbody>
                    <tr>
                        <td colspan="2"><h2>Notes externes</h2></td>
                        <td colspan="2"><h2>Déclaration client</h2></td>
                    </tr>
                    <tr>
                        <th style="width: 25%;"><label for="grade_sfac">Grade SFAC</label></th>
                        <td style="width: 30%;">
                            <select name="ratings[grade_sfac]" id="grade_sfac">
                                <option value="0"<?php if (isset($this->aRatings['grade_sfac']) && 0 == $this->aRatings['grade_sfac']) : ?> selected="selected"<?php endif; ?>>N/A</option>
                                <?php for ($iCounter = 1; $iCounter <= 9; $iCounter++) : ?>
                                <option value="<?= $iCounter ?>"<?php if (isset($this->aRatings['grade_sfac']) && $iCounter == $this->aRatings['grade_sfac']) : ?> selected="selected"<?php endif; ?>><?= $iCounter ?></option>
                                <?php endfor; ?>
                            </select>
                        </td>
                        <th style="width: 25%;"><label for="ca_declara_client">Chiffe d'affaires declaré par client</label></th>
                        <td style="width: 20%;"><input type="text" name="ca_declara_client" id="ca_declara_client" placeholder="€" class="input_moy numbers" value="<?= $this->ficelle->formatNumber($this->projects->ca_declara_client, 0) ?>"/></td>
                    </tr>
                    <tr>
                        <th><label>Score Altares</label></th>
                        <td><?php if (isset($this->aRatings['score_altares'])) : ?><?= $this->aRatings['score_altares'] ?> / 20<?php else : ?>N/A<?php endif; ?></td>
                        <th><label for="resultat_exploitation_declara_client">Résultat d'exploitation declaré par client</label></th>
                        <td><input type="text" name="resultat_exploitation_declara_client" id="resultat_exploitation_declara_client" placeholder="€" class="input_moy numbers" value="<?= $this->ficelle->formatNumber($this->projects->resultat_exploitation_declara_client, 0) ?>"/></td>
                    </tr>
                    <tr>
                        <th><label>Score sectoriel Altares</label></th>
                        <td><?php if (isset($this->aRatings['score_sectorial_altares'])) : ?><?= round($this->aRatings['score_sectorial_altares'] / 5) ?> / 20<?php else : ?>N/A<?php endif; ?></td>
                        <th><label for="fonds_propres_declara_client">Fonds propres declarés par client</label></th>
                        <td colspan="3"><input type="text" name="fonds_propres_declara_client" id="fonds_propres_declara_client" placeholder="€" class="input_moy numbers" value="<?= $this->ficelle->formatNumber($this->projects->fonds_propres_declara_client, 0) ?>"/></td>
                    </tr>
                    <tr>
                        <th><label for="note_infolegale">Note Infolegale</label></th>
                        <td colspan="3">
                            <select name="ratings[note_infolegale]" id="note_infolegale">
                                <option value="0"<?php if (isset($this->aRatings['note_infolegale']) && 0 == $this->aRatings['note_infolegale']) : ?> selected="selected"<?php endif; ?>>N/A</option>
                                <?php for ($iCounter = 1; $iCounter <= 20; $iCounter++) : ?>
                                    <option value="<?= $iCounter ?>"<?php if (isset($this->aRatings['note_infolegale']) && $iCounter == $this->aRatings['note_infolegale']) : ?> selected="selected"<?php endif; ?>><?= $iCounter ?></option>
                                <?php endfor; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Présence de RPC < 6 mois</label></th>
                        <td colspan="3">
                            <label><input type="radio" name="ratings[rpc_6mois]" value="1"<?php if (isset($this->aRatings['rpc_6mois']) && '1' === $this->aRatings['rpc_6mois']) : ?> checked="checked"<?php endif; ?>/> Oui</label>
                            <label><input type="radio" name="ratings[rpc_6mois]" value="0"<?php if (isset($this->aRatings['rpc_6mois']) && '0' === $this->aRatings['rpc_6mois']) : ?> checked="checked"<?php endif; ?>/> Non</label>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Présence de RPC > 12 mois</label></th>
                        <td colspan="3">
                            <label><input type="radio" name="ratings[rpc_12mois]" value="1"<?php if (isset($this->aRatings['rpc_12mois']) && '1' === $this->aRatings['rpc_12mois']) : ?> checked="checked"<?php endif; ?>/> Oui</label>
                            <label><input type="radio" name="ratings[rpc_12mois]" value="0"<?php if (isset($this->aRatings['rpc_12mois']) && '0' === $this->aRatings['rpc_12mois']) : ?> checked="checked"<?php endif; ?>/> Non</label>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="grabe_fiben">Grade FIBEN / Note interne banque</label></th>
                        <td colspan="3">
                            <input type="text" name="ratings[grabe_fiben]" id="grabe_fiben" value="<?php if (isset($this->aRatings['grabe_fiben'])) : ?><?= $this->aRatings['grabe_fiben'] ?><?php endif; ?>" placeholder="Grade FIBEN" class="input_moy"/>
                            <input type="text" name="ratings[note_interne_banque]" id="note_interne_banque" value="<?php if (isset($this->aRatings['note_interne_banque'])) : ?><?= $this->aRatings['note_interne_banque'] ?><?php endif; ?>" placeholder="Note banque" class="input_moy"/>
                        </td>
                    </tr>
                    <tr<?php if (empty($this->aRatings['nom_banque'])) : ?> style="display: none;"<?php endif; ?>>
                        <th></th>
                        <td>
                            <input type="text" name="ratings[nom_banque]" id="nom_banque" value="<?php if (isset($this->aRatings['nom_banque'])) : ?><?= $this->aRatings['nom_banque'] ?><?php endif; ?>" placeholder="Nom banque" class="input_moy" style="margin-left: 163px;"/>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="grabe_dirigeant_fiben">Grade dirigeant FIBEN</label></th>
                        <td colspan="3"><input type="text" name="ratings[grabe_dirigeant_fiben]" id="grabe_dirigeant_fiben" value="<?php if (isset($this->aRatings['grabe_dirigeant_fiben'])) : ?><?= $this->aRatings['grabe_dirigeant_fiben'] ?><?php endif; ?>" class="input_moy"/></td>
                    </tr>
                    <tr>
                        <th><label>Score sectoriel Xerfi</label></th>
                        <td colspan="3"><?php if (isset($this->aRatings['xerfi'], $this->aRatings['xerfi_unilend'])) : ?><?= $this->aRatings['xerfi'] ?> / <?= $this->aRatings['xerfi_unilend'] ?><?php else : ?>N/A<?php endif; ?></td>
                    </tr>
                    <tr>
                        <th><label for="date_dernier_privilege">Date du privilège le plus récent</label></th>
                        <td colspan="3"><input type="text" name="ratings[date_dernier_privilege]" id="date_dernier_privilege" value="<?php if (isset($this->aRatings['date_dernier_privilege']) && false === empty($this->aRatings['date_dernier_privilege'])) : ?><?= $this->dates->formatDate($this->aRatings['date_dernier_privilege'], 'd/m/Y') ?><?php endif; ?>" class="input_dp" readonly="readonly"/></td>
                    </tr>
                    <tr>
                        <th><label for="">Dernière situation de trésorerie connue</label></th>
                        <td colspan="3">
                            <input type="text" name="ratings[date_tresorerie]" id="date_tresorerie" value="<?php if (isset($this->aRatings['date_tresorerie']) && false === empty($this->aRatings['date_tresorerie'])) : ?><?= $this->dates->formatDate($this->aRatings['date_tresorerie'], 'd/m/Y') ?><?php endif; ?>" class="input_dp" readonly="readonly"/>
                            &nbsp;&nbsp;<input type="text" name="ratings[montant_tresorerie]" id="montant_tresorerie" value="<?php if (isset($this->aRatings['montant_tresorerie'])) : ?><?= $this->ficelle->formatNumber((float) $this->aRatings['montant_tresorerie'], 0) ?><?php endif; ?>" placeholder="€" class="input_moy numbers"/>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="delais_paiement_altares">Délais de paiement Altares (à date)</label></th>
                        <td colspan="3"><input type="text" name="ratings[delais_paiement_altares]" id="delais_paiement_altares" value="<?php if (isset($this->aRatings['delais_paiement_altares'])) : ?><?= $this->aRatings['delais_paiement_altares'] ?><?php endif; ?>" class="input_court numbers"/></td>
                    </tr>
                    <tr>
                        <th><label for="delais_paiement_secteur">Délais de paiement du secteur</label></th>
                        <td colspan="3"><input type="text" name="ratings[delais_paiement_secteur]" id="delais_paiement_secteur" value="<?php if (isset($this->aRatings['delais_paiement_secteur'])) : ?><?= $this->aRatings['delais_paiement_secteur'] ?><?php endif; ?>" class="input_court numbers"/></td>
                    </tr>
                    <tr>
                        <th><label>Dailly</label></th>
                        <td colspan="3">
                            <label><input type="radio" name="ratings[dailly]" value="1"<?php if (isset($this->aRatings['dailly']) && '1' === $this->aRatings['dailly']) : ?> checked="checked"<?php endif; ?>/> Oui</label>
                            <label><input type="radio" name="ratings[dailly]" value="0"<?php if (isset($this->aRatings['dailly']) && '0' === $this->aRatings['dailly']) : ?> checked="checked"<?php endif; ?>/> Non</label>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Affacturage</label></th>
                        <td colspan="3">
                            <label><input type="radio" name="ratings[affacturage]" value="1"<?php if (isset($this->aRatings['affacturage']) && '1' === $this->aRatings['affacturage']) : ?> checked="checked"<?php endif; ?>/> Oui</label>
                            <label><input type="radio" name="ratings[affacturage]" value="0"<?php if (isset($this->aRatings['affacturage']) && '0' === $this->aRatings['affacturage']) : ?> checked="checked"<?php endif; ?>/> Non</label>
                        </td>
                    </tr>
                </tbody>
            </table>
            <br/>
            <h2>Capital restant dû à date : <?= $this->ficelle->formatNumber($this->fCompanyOwedCapital) ?> €</h2>
            <br/>
            <h2>Projets de cette société (SIREN identique)</h2>
            <?php if (empty($this->aCompanyProjects)) : ?>
                <h3>Aucun autre projet pour cette société</h3>
            <?php else : ?>
                <table class="tablesorter" id="company_projects">
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
                            <td><?= $this->ficelle->formatNumber($aProject['amount']) ?>&nbsp;€</td>
                            <td><?= $aProject['period'] ?> mois</td>
                            <td><?= $aProject['status_label'] ?></td>
                            <td><?= $aProject['sales_person'] ?></td>
                            <td><?= $aProject['analyst'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <div id="valid_etape4_1" class="valid_etape"><br/>Données sauvegardées</div>
        <div class="btnDroite">
            <input type="submit" class="btn_link" value="Sauvegarder"/>
        </div>
    </form>
</div>
