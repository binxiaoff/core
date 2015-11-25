<div class="tab_title" id="title_etape3">Etape 3</div>
<div class="tab_content" id="etape3">
    <form method="post" name="dossier_etape3" id="dossier_etape3" enctype="multipart/form-data"
          action="<?= $this->lurl ?>/dossiers/edit/<?= $this->params[0] ?>" target="_parent">
        <table class="form" style="width: 100%;">
            <tr>
                <th><label for="montant_etape3">Montant :</label></th>
                <td><input type="text" name="montant_etape3" id="montant_etape3" class="input_large" value="<?= empty($this->projects->amount) ? '' : $this->ficelle->formatNumber($this->projects->amount, 0) ?>"/> €</td>
                <th><label for="duree_etape3">Durée du prêt :</label></th>
                <td>
                    <select name="duree_etape3" id="duree_etape3" class="select">
                        <?php foreach ($this->dureePossible as $duree): ?>
                            <option <?= ($this->projects->period == $duree ? 'selected' : '') ?> value="<?= $duree ?>"><?= $duree ?> mois</option>
                        <?php endforeach ?>
                        <option<?= ($this->projects->period == 1000000 || $this->projects->period == 0 ? ' selected' : '') ?> value="0">Je ne sais pas</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="titre_etape3">Titre projet :</label></th>
                <td colspan="3">
                    <input style="width:780px;" type="text" name="titre_etape3" id="titre_etape3" class="input_large" value="<?= $this->projects->title ?>"/>
                </td>
            </tr>
            <tr>
                <th><label for="objectif_etape3">Objectif du crédit :</label></th>
                <td colspan="3">
                    <textarea style="width:780px;" name="objectif_etape3" id="objectif_etape3" class="textarea_lng"/><?= $this->projects->objectif_loan ?></textarea>
                </td>
            </tr>
            <tr>
                <th><label for="presentation_etape3">Présentation de la société :</label></th>
                <td colspan="3">
                    <textarea style="width:780px;" name="presentation_etape3" id="presentation_etape3" class="textarea_lng"/><?= $this->projects->presentation_company ?></textarea>
                </td>
            </tr>
            <tr>
                <th><label for="moyen_etape3">Moyen de remboursement prévu :</label></th>
                <td colspan="3">
                    <textarea style="width:780px;" name="moyen_etape3" id="moyen_etape3" class="textarea_lng"/><?= $this->projects->means_repayment ?></textarea>
                </td>
            </tr>
            <tr>
                <th><label for="moyen_etape3">Informations utiles :</label></th>
                <td colspan="3">
                    <textarea style="width:780px;" name="comments_etape3" id="comments_etape3" class="textarea_lng"/><?= $this->projects->comments ?></textarea>
                </td>
            </tr>
        </table>

        <div id="valid_etape3" class="valid_etape">Données sauvegardées</div>
        <div class="btnDroite">
            <input type="button" class="btn_link" value="Sauvegarder" onclick="valid_etape3(<?= $this->projects->id_project ?>)">
        </div>
    </form>
</div>
