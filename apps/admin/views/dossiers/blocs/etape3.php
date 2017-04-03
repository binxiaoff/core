<a class="tab_title" id="section-presentation" href="#section-presentation">3. Présentation</a>
<div class="tab_content<?php if ($this->projects->status == \projects_status::PREP_FUNDING && \users_types::TYPE_COMMERCIAL == $_SESSION['user']['id_user_type']) : ?> expand<?php endif; ?>" id="etape3">
    <form method="post" name="dossier_etape3" id="dossier_etape3" enctype="multipart/form-data" action="<?= $this->lurl ?>/ajax/valid_etapes">
        <input type="hidden" name="etape" value="3">
        <input type="hidden" name="id_project" value="<?= $this->projects->id_project ?>">
        <table class="form" style="width: 100%;">
            <tr>
                <th><label for="photo_projet">Photo projet<br>(798 x 528)</label></th>
                <td>
                    <input type="file" name="photo_projet" id="photo_projet">
                    <?php if (false === empty($this->projects->photo_projet)) : ?>
                        <br>
                        <a target="_blank" href="<?= $this->surl ?>/images/dyn/projets/source/<?= $this->projects->photo_projet ?>">
                            <img src="<?= $this->surl ?>/images/dyn/projets/source/<?= $this->projects->photo_projet ?>" alt="<?= $this->projects->photo_projet ?>" width="300">
                        </a>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th><label for="comments_etape3">Informations utiles</label></th>
                <td colspan="3">
                    <?php if ($this->projects->create_bo) : ?>
                        <textarea style="width:780px;" name="comments_etape3" id="comments_etape3" class="textarea_lng"><?= $this->projects->comments ?></textarea>
                    <?php else : ?>
                        <span style="color: #000"><?= empty($this->projects->comments) ? '-' : nl2br($this->projects->comments) ?></span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th><label for="nature_project">Nature du projet</label></th>
                <td><input type="text" style="width: 780px;" name="nature_project" id="nature_project" class="input_moy" value="<?= $this->projects->nature_project ?>"></td>
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
        <div id="error_etape3" class="error_etape"></div>
        <div id="valid_etape3" class="valid_etape">Données sauvegardées</div>
        <div class="btnDroite">
            <input type="submit" class="btn_link" value="Sauvegarder">
        </div>
    </form>
</div>
<script>
    $(function() {
        $('#dossier_etape3').submit(function(event) {
            event.preventDefault()

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                dataType: 'json',
                data: new FormData(document.getElementById('dossier_etape3')),
                async: false,
                success: function(response) {
                    if (response.success) {
                        $('#valid_etape3').slideDown()

                        setTimeout(function () {
                            $('#valid_etape3').slideUp()
                        }, 3000)
                    } else {
                        var message = 'Erreur inconnue'

                        if (response.error && response.message) {
                            message = response.message
                        }

                        $('#error_etape3').html(message).slideDown()

                        setTimeout(function () {
                            $('#error_etape3').slideUp()
                        }, 3000)
                    }
                },
                cache: false,
                contentType: false,
                processData: false
            })
        })
    })
</script>