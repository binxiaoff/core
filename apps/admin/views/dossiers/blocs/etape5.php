<div class="tab_title" id="title_etape5">Etape 5</div>
<div class="tab_content" id="etape5">
    <script type="text/javascript">
        function formUploadCallback(result) {
            var aStatus = jQuery.parseJSON(result);
            if(aStatus.length != 0) {
                $.each(aStatus, function(fileType, value){
                    if ('ok' == value) {
                        $(".statut_" + fileType).html('Enregistré');

                        <?php if (in_array($this->projects->period, array(0, 1000000)) && $this->projects->status == \projects_status::PREP_FUNDING) { ?>
                        if (fileType == 'fichier_3' && $('#displayPeriodHS').css('display') == 'block') { // RIB
                            $("#status").css('display', 'block');
                            $("#msgProject").css('display', 'block');
                            $('#displayPeriodHS').css('display', 'none');
                            $("#msgProjectPeriodHS").css('display', 'none');
                        }
                        <?php } ?>
                    }
                });
                $("#valid_etape5").slideDown();

                setTimeout(function () {
                    $("#valid_etape5").slideUp();
                }, 4000);
            }
        }
    </script>
    <form method="post" name="dossier_etape5" id="dossier_etape5" enctype="multipart/form-data" action="<?= $this->lurl ?>/dossiers/file/<?= $this->params[0] ?>" target="upload_target">
        <table class="tablesorter">
            <thead>
                <tr>
                    <th width="20"></th>
                    <th width="250">Nom</th>
                    <th>Fichier</th>
                    <th width="100">Statut</th>
                    <th width="300"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($this->aAttachmentTypes as $sAttachmentType): ?>
                    <tr>
                        <td class="remove_col">
                            <?php if (isset($this->aAttachments[$sAttachmentType['id']]['path'])): ?>
                                <a href="#" data-id="<?= $this->aAttachments[$sAttachmentType['id']]['id'] ?>" data-label="<?= $sAttachmentType['label'] ?>" class="icon_remove_attachment"><img src="<?= $this->surl ?>/images/admin/delete.png" alt="Supprimer" title="Supprimer"></a>
                            <?php endif; ?>
                        </td>
                        <td class="type_col"><?= $sAttachmentType['label'] ?></td>
                        <td class="label_col">
                            <?php if (isset($this->aAttachments[$sAttachmentType['id']]['path'])): ?>
                                <a href="<?= $this->url ?>/attachment/download/id/<?= $this->aAttachments[$sAttachmentType['id']]['id'] ?>/file/<?= urlencode($this->aAttachments[$sAttachmentType['id']]['path']) ?>"><?= $this->aAttachments[$sAttachmentType['id']]['path'] ?></a>
                            <?php endif; ?>
                        </td>
                        <td class="statut_fichier_<?= $sAttachmentType['id'] ?>" id="statut_fichier_id_<?= $sAttachmentType['id'] ?>"><?= isset($this->aAttachments[$sAttachmentType['id']]) === true ? 'Enregistré' : '' ?></td>
                        <td><input type="file" name="<?= $sAttachmentType['id'] ?>" id="fichier_project_<?= $sAttachmentType['id'] ?>"/></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div id="valid_etape5" class="valid_etape"><br/>Données sauvegardées</div>
        <div class="btnDroite">
            <input type="hidden" name="send_etape5"/>
            <input type="submit" class="btn_link" value="Sauvegarder">
        </div>
    </form>
    <div style="display:none;">
        <iframe id="upload_target" name="upload_target" src="#"></iframe>
    </div>
</div>
