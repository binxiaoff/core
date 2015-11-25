<div class="tab_title" id="title_etape1">Etape 1</div>
<div class="tab_content" id="etape1">
    <form method="post" id="dossier_etape1" action="<?= $this->lurl ?>/dossiers/edit/<?= $this->params[0] ?>" onsubmit="valid_etape1(<?= $this->projects->id_project ?>); return false;">
        <table class="form" style="width: 100%;">
            <tr>
                <th><label for="montant_etape1">Montant :</label></th>
                <td><input type="text" name="montant_etape1" id="montant_etape1" class="input_moy" value="<?= empty($this->projects->amount) ? '' : $this->ficelle->formatNumber($this->projects->amount, 0) ?>"/> €</td>
                <th><label for="duree_etape1">Durée du prêt :</label></th>
                <td>
                    <select name="duree_etape1" id="duree_etape1" class="select">
                        <?php foreach ($this->dureePossible as $duree): ?>
                            <option<?= ($this->projects->period == $duree ? ' selected' : '') ?> value="<?= $duree ?>"><?= $duree ?> mois</option>
                        <?php endforeach ?>
                        <option<?= ($this->projects->period == 1000000 || $this->projects->period == 0) ? ' selected' : '' ?> value="0">Je ne sais pas</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="siren_etape1">SIREN :</label></th>
                <td colspan="3">
                    <?php if ($this->projects->create_bo == 1): ?>
                        <input type="text" name="siren_etape1" id="siren_etape1" class="input_moy" value="<?= $this->companies->siren ?>"/>
                    <?php else: ?>
                        <input type="hidden" name="siren_etape1" id="siren_etape1" value="<?= $this->companies->siren ?>"/>
                        <?= $this->companies->siren ?>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
        <div id="valid_etape1" class="valid_etape">Données sauvegardées</div>
        <div class="btnDroite">
            <input type="submit" class="btn_link" value="Sauvegarder">
        </div>
    </form>
</div>
