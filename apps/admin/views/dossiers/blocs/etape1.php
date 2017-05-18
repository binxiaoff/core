<a class="tab_title" id="section-project" href="#section-project">1. Projet</a>
<div class="tab_content" id="etape1">
    <form method="post" id="dossier_etape1" action="<?= $this->lurl ?>/dossiers/edit/<?= $this->params[0] ?>" onsubmit="valid_etape1(<?= $this->projects->id_project ?>); return false;">
        <table class="form" style="width: 100%;">
            <tr>
                <th><label for="siren_etape1">SIREN</label></th>
                <td><input type="text" name="siren_etape1" id="siren_etape1" class="input_moy" value="<?= $this->companies->siren ?>"></td>
                <th><label for="partner_id">Partenaire</label></th>
                <td>
                    <select id="partner_etape1" name="partner_etape1" class="select" title="Partenaire">
                        <?php foreach ($this->partnerList as $partner) : ?>
                            <option value="<?= $partner->getId() ?>"<?= $this->projects->id_partner == $partner->getId() ? ' selected' : '' ?>><?= $partner->getIdCompany()->getName() ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="montant_etape1">Montant</label></th>
                <td><input type="text" name="montant_etape1" id="montant_etape1" class="input_moy" value="<?= empty($this->projects->amount) ? '' : $this->ficelle->formatNumber($this->projects->amount, 0) ?>"> €</td>
                <th><label for="source_etape1">Source</label></th>
                <td>
                    <select id="source_etape1" name="source_etape1" class="select">
                        <option value=""></option>
                        <?php foreach ($this->sources as $source) : ?>
                            <option value="<?= stripslashes($source) ?>"<?= $this->clients->source === $source ? ' selected' : '' ?>><?= $source ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="duree_etape1">Durée du prêt</label></th>
                <td colspan="3">
                    <select name="duree_etape1" id="duree_etape1" class="select">
                        <option<?= (in_array($this->projects->period, [0, 1000000])) ? ' selected' : '' ?> value="0">Je ne sais pas</option>
                        <?php foreach ($this->dureePossible as $duree) : ?>
                            <option<?= ($this->projects->period == $duree ? ' selected' : '') ?> value="<?= $duree ?>"><?= $duree ?> mois</option>
                        <?php endforeach ?>
                    </select>
                </td>
            </tr>
        </table>
        <div id="error_etape1" class="error_etape"></div>
        <button type="submit" class="btn-primary pull-right">Sauvegarder</button>
    </form>
</div>
