<a class="tab_title" id="section-presentation" href="#section-presentation">3 - Présentation</a>
<div class="tab_content<?php if ($this->projects->status == \projects_status::PREP_FUNDING && \users_types::TYPE_COMMERCIAL == $_SESSION['user']['id_user_type']) : ?> expand<?php endif; ?>" id="etape3">
    <form method="post" name="dossier_etape3" id="dossier_etape3" enctype="multipart/form-data"
          action="<?= $this->lurl ?>/dossiers/edit/<?= $this->params[0] ?>" target="_parent">
        <table class="form" style="width: 100%;">
            <tr>
                <th><label for="comments_etape3">Informations utiles</label></th>
                <td colspan="3">
                    <textarea style="width:780px;" name="comments_etape3" id="comments_etape3" class="textarea_lng"<?= $this->projects->create_bo ? '' : ' disabled' ?>><?= $this->projects->comments ?></textarea>
                </td>
            </tr>
            <tr>
                <th><label for="presentation_etape3">Présentation de la société</label></th>
                <td colspan="3">
                    <textarea style="width:780px;" name="presentation_etape3" id="presentation_etape3" class="textarea_lng"><?= $this->projects->presentation_company ?></textarea>
                </td>
            </tr>
            <tr>
                <th><label for="objectif_etape3">Objectif du crédit</label></th>
                <td colspan="3">
                    <textarea style="width:780px;" name="objectif_etape3" id="objectif_etape3" class="textarea_lng"><?= $this->projects->objectif_loan ?></textarea>
                </td>
            </tr>
            <tr>
                <th><label for="moyen_etape3">Moyen de remboursement prévu</label></th>
                <td colspan="3">
                    <textarea style="width:780px;" name="moyen_etape3" id="moyen_etape3" class="textarea_lng"><?= $this->projects->means_repayment ?></textarea>
                </td>
            </tr>
        </table>
        <div id="valid_etape3" class="valid_etape">Données sauvegardées</div>
        <div class="btnDroite">
            <input type="button" class="btn_link" value="Sauvegarder" onclick="valid_etape3(<?= $this->projects->id_project ?>)">
        </div>
    </form>
</div>
