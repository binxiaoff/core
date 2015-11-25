<div class="tab_title" id="title_etape4_4">Etape 4.4</div>
<div class="tab_content" id="etape4_4">
    <form method="post" enctype="multipart/form-data" action="<?= $this->lurl ?>/dossiers/edit/<?= $this->params[0] ?>" target="_parent">
        <?php if (count($this->lbilans) > 0): ?>
            <table class="tablesorter" style="text-align:center;">
                <thead>
                <tr>
                    <th width="200"></th>
                    <?php foreach ($this->lbilans as $b): ?>
                        <th><?= $b['date'] ?></th>
                    <?php endforeach; ?>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>Chiffe d'affaires</td>
                    <?php for ($i = 0; $i < 5; $i++): ?>
                        <td<?= ($i < 3 ? ' class="grisfonceBG"' : '') ?>>
                            <input name="ca_<?= $i ?>" id="ca_<?= $i ?>" type="text" class="input_moy<?= ($i < 3 ? ' grisfonceBG' : '') ?>" value="<?= ($this->lbilans[$i]['ca'] != false ? $this->ficelle->formatNumber($this->lbilans[$i]['ca'], 0) : ''); ?>"/>
                            <input type="hidden" name="ca_id_<?= $i ?>" id="ca_id_<?= $i ?>" value="<?= $this->lbilans[$i]['id_bilan'] ?>"/>
                        </td>
                    <?php endfor; ?>
                </tr>
                <tr>
                    <td>Résultat brut d'exploitation</td>
                    <?php
                    for ($i = 0; $i < 5; $i++) {
                        ?>
                    <td class="<?= ($i < 3 ? 'grisfonceBG' : '') ?>">
                        <input name="resultat_brute_exploitation_<?= $i ?>" id="resultat_brute_exploitation_<?= $i ?>" type="text" class="input_moy <?= ($i < 3 ? 'grisfonceBG' : '') ?>" value="<?= ($this->lbilans[$i]['resultat_brute_exploitation'] != false ? $this->ficelle->formatNumber($this->lbilans[$i]['resultat_brute_exploitation'], 0) : ''); ?>"/>
                        <input type="hidden" name="resultat_brute_exploitation_id_<?= $i ?>" id="resultat_brute_exploitation_id_<?= $i ?>" value="<?= $this->lbilans[$i]['id_bilan'] ?>"/>
                        </td><?php
                    }
                    ?>
                </tr>
                <tr>
                    <td>Résultat d'exploitation</td>
                    <?php
                    for ($i = 0; $i < 5; $i++) {
                        ?>
                    <td class="<?= ($i < 3 ? 'grisfonceBG' : '') ?>">
                        <input name="resultat_exploitation_<?= $i ?>" id="resultat_exploitation_<?= $i ?>" type="text" class="input_moy <?= ($i < 3 ? 'grisfonceBG' : '') ?>" value="<?= ($this->lbilans[$i]['resultat_exploitation'] != false ? $this->ficelle->formatNumber($this->lbilans[$i]['resultat_exploitation'], 0) : ''); ?>"/>
                        <input type="hidden" name="resultat_exploitation_id_<?= $i ?>" id="resultat_exploitation_id_<?= $i ?>" value="<?= $this->lbilans[$i]['id_bilan'] ?>"/>
                        </td><?php
                    }
                    ?>
                </tr>
                <tr>
                    <td>Investissements</td>
                    <?php
                    for ($i = 0; $i < 5; $i++) {
                        ?>
                        <td <?= ($i < 3 ? 'class="grisfonceBG"' : '') ?>>
                            <input name="investissements_<?= $i ?>" id="investissements_<?= $i ?>" type="text" class="input_moy <?= ($i < 3 ? 'grisfonceBG' : '') ?>" value="<?= ($this->lbilans[$i]['investissements'] != false ? $this->ficelle->formatNumber($this->lbilans[$i]['investissements'], 0) : ''); ?>"/>
                            <input type="hidden" name="investissements_id_<?= $i ?>" id="investissements_id_<?= $i ?>" value="<?= $this->lbilans[$i]['id_bilan'] ?>"/>
                        </td>
                        <?php
                    }
                    ?>
                </tr>
                </tbody>
            </table>
        <?php endif; ?>
        <?php if (count($this->lCompanies_actif_passif) > 0): ?>
            <br/>
            <h2>Actif</h2>
            <?php

            $totalAnnee  = 0;
            $arrayBilans = array(
                'immobilisations_corporelles'     => 'Immobilisations corporelles',
                'immobilisations_incorporelles'   => 'Immobilisations incorporelles',
                'immobilisations_financieres'     => 'Immobilisations financières',
                'stocks'                          => 'Stocks',
                'creances_clients'                => 'Créances clients',
                'disponibilites'                  => 'Disponibilités',
                'valeurs_mobilieres_de_placement' => 'Valeurs mobilières de placement',
            );

            ?>
            <table class="tablesorter actif_passif" style="text-align:center;">
                <thead>
                <tr>
                    <th width="300">Ordre</th>
                    <?php foreach (array_slice($this->lCompanies_actif_passif, 0, 3) as $ap): ?>
                        <th><?= $ap['annee'] ?></th>
                    <?php endforeach; ?>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($arrayBilans as $sFieldName => $sTitle): ?>
                    <tr>
                        <td><?= $sTitle ?></td>
                        <?php foreach (array_slice($this->lCompanies_actif_passif, 0, 3) as $ap): ?>
                            <td>
                                <input name="<?= $sFieldName ?>_<?= $ap['ordre'] ?>"
                                       id="<?= $sFieldName ?>_<?= $ap['ordre'] ?>" type="text" class="input_moy"
                                       value="<?= ($ap[$sFieldName] != false ? $this->ficelle->formatNumber($ap[$sFieldName], 0) : ''); ?>"
                                       onkeyup="cal_actif();"/>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <td>Total</td>
                    <?php foreach (array_slice($this->lCompanies_actif_passif, 0, 3) as $ap): ?>
                        <?php foreach (array_keys($arrayBilans) as $sKey): ?>
                            <?php $totalAnnee += $ap[$sKey]; ?>
                        <?php endforeach; ?>
                        <td id="totalAnneeAct_<?= $ap['ordre'] ?>"><?= $this->ficelle->formatNumber($totalAnnee, 0) ?></td>
                    <?php endforeach; ?>
                </tr>
                </tbody>
            </table>
            <br/>
            <h2>Passif</h2>
            <?php

            $totalAnnee        = 0;
            $arrayBilansPassif = array(
                'capitaux_propres'                   => 'Capitaux propres',
                'provisions_pour_risques_et_charges' => 'Provisions pour risques & charges',
                'amortissement_sur_immo'             => 'Amortissements sur immobilisations',
                'dettes_financieres'                 => 'Dettes financières',
                'dettes_fournisseurs'                => 'Dettes fournisseurs',
                'autres_dettes'                      => 'Autres dettes',
            );

            ?>
            <table class="tablesorter actif_passif" style="text-align:center;">
                <thead>
                <tr>
                    <th width="300">Ordre</th>
                    <?php foreach (array_slice($this->lCompanies_actif_passif, 0, 3) as $ap) : ?>
                        <th><?= $ap['annee'] ?></th>
                    <?php endforeach; ?>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($arrayBilansPassif as $sFieldName => $sTitle) : ?>
                    <tr>
                        <td><?= $sTitle ?></td>
                        <?php foreach (array_slice($this->lCompanies_actif_passif, 0, 3) as $ap) : ?>
                            <td>
                                <input name="<?= $sFieldName ?>_<?= $ap['ordre'] ?>"
                                       id="<?= $sFieldName ?>_<?= $ap['ordre'] ?>" type="text" class="input_moy"
                                       value="<?= ($ap[$sFieldName] != false ? $this->ficelle->formatNumber($ap[$sFieldName], 0) : ''); ?>"
                                       onkeyup="cal_passif();"/>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <td>Total</td>
                    <?php foreach (array_slice($this->lCompanies_actif_passif, 0, 3) as $ap) : ?>
                        <?php foreach (array_keys($arrayBilansPassif) as $sKey) : ?>
                            <?php $totalAnnee += $ap[$sKey]; ?>
                        <?php endforeach; ?>
                        <td id="totalAnneePass_<?= $ap['ordre'] ?>"><?= $this->ficelle->formatNumber($totalAnnee, 0) ?></td>
                    <?php endforeach; ?>
                </tr>
                </tbody>
            </table>
        <?php endif; ?>
        <br/>

        <div id="valid_etape4_4" class="valid_etape">Données sauvegardées</div>
        <div style="text-align: right">
            <input type="button" class="btn_link" value="Sauvegarder" onclick="valid_etape4_4(<?= $this->projects->id_project ?>)">
        </div>
    </form>
</div>
