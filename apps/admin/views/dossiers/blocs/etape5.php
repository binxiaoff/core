<div class="tab_title" id="title_etape5">Etape 5</div>
<div class="tab_content" id="etape5">
    <script type="text/javascript">
        function formUploadCallback(result) {
            var aStatus = jQuery.parseJSON(result);
            if(aStatus.length != 0) {
                $.each(aStatus, function(fileType, value){
                    if ('ok' == value) {
                        $(".statut_" + fileType).html('Enregistré');

                        <?php if (0 < $this->projects->period  && 1000000 > $this->projects->period && 35 == $this->current_projects_status->status) { ?>
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
        <?php if (count($this->lbilans) > 0): ?>
            <table class="tablesorter">
                <thead>
                    <tr>
                        <th></th>
                        <th width="200">Nom</th>
                        <th>Fichier</th>
                        <th>Statut</th>
                        <th></th>
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
            <?php if ($this->nb_lignes != ''): ?>
                <table>
                    <tr>
                        <td id="pager">
                            <img src="<?= $this->surl ?>/images/admin/first.png" alt="Première" class="first"/>
                            <img src="<?= $this->surl ?>/images/admin/prev.png" alt="Précédente" class="prev"/>
                            <input type="text" class="pagedisplay"/>
                            <img src="<?= $this->surl ?>/images/admin/next.png" alt="Suivante" class="next"/>
                            <img src="<?= $this->surl ?>/images/admin/last.png" alt="Dernière" class="last"/>
                            <select class="pagesize">
                                <option value="<?= $this->nb_lignes ?>"
                                        selected="selected"><?= $this->nb_lignes ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
            <?php endif; ?>
        <?php endif; ?>
        <br/>

        <div id="valid_etape5" class="valid_etape">Données sauvegardées</div>
        <br/><br/>
        <input type="hidden" name="send_etape5"/>
        <div class="btnDroite"><input type="submit" class="btn_link" value="Sauvegarder"></div>
    </form>
    <div style="display:none;">
        <iframe id="upload_target" name="upload_target" src="#"></iframe>
    </div>
</div>
