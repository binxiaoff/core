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
                <?php
                /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\AttachmentType $attachmentType */
                foreach ($this->aAttachmentTypes as $attachmentType) :
                ?>
                    <tr<?php if (in_array($attachmentType, $this->aMandatoryAttachmentTypes)) : ?> class="highlighted"<?php endif; ?>>
                    <?php
                    $currentAttachment = null;
                        /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\ProjectAttachment $projectAttachment */
                    foreach ($this->aAttachments as $projectAttachment) :
                        $attachment = $projectAttachment->getAttachment();
                        if ($attachment->getType() === $attachmentType) {
                            $currentAttachment = $attachment;
                            break;
                        }
                        ?>
                    <?php endforeach; ?>
                    <?php if ($currentAttachment) : ?>
                        <td class="remove_col">
                            <a href="#" data-id="<?= $projectAttachment->getId() ?>" data-label="<?= $attachmentType->getLabel() ?>" class="icon_remove_attachment"><img src="<?= $this->surl ?>/images/admin/delete.png" alt="Supprimer" title="Supprimer"></a>
                        </td>
                        <td class="type_col"><?= $attachmentType->getLabel() ?></td>
                        <td class="label_col">
                            <a href="<?= $this->url ?>/attachment/download/id/<?= $currentAttachment->getId() ?>/file/<?= urlencode($currentAttachment->getPath()) ?>"><?= $currentAttachment->getPath() ?></a>
                        </td>
                        <td class="statut_fichier_<?= $attachmentType->getId() ?>" id="statut_fichier_id_<?= $projectAttachment->getId() ?>"><?= $currentAttachment ? 'Enregistré' : '' ?></td>
                    <?php else : ?>
                        <td class="remove_col"></td>
                        <td class="type_col"><?= $attachmentType->getLabel() ?></td>
                        <td class="label_col"></td>
                        <td class="statut_fichier_<?= $attachmentType->getId() ?>"></td>
                    <?php endif; ?>
                        <td><input type="file" name="<?= $attachmentType->getId() ?>" id="fichier_project_<?= $attachmentType->getId() ?>"/></td>
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
