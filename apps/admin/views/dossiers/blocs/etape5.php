<a class="tab_title" id="section-documents" href="#section-documents">5. Documents</a>
<div class="tab_content" id="etape5">
    <script type="text/javascript">
        function formUploadCallback(result) {
            var aStatus = jQuery.parseJSON(result);
            if(aStatus.length != 0) {
                $.each(aStatus, function(fileType, value){
                    if ('ok' == value) {
                        $(".statut_" + fileType).html('Enregistré');
                    }
                });

                $("#valid_etape5").slideDown();

                setTimeout(function () {
                    $("#valid_etape5").slideUp();
                }, 4000);
            }
        }

        $('body').on('click', '.attachment-category', function() {
            $(this).toggleClass('expand').nextUntil('.attachment-category').slideToggle()
        })
    </script>
    <?php if (false === empty($this->project_cgv->id)) : ?>
        <div>
            <table class="tablesorter">
                <tbody>
                <tr>
                    <td>
                        CGV envoyées le <?= \DateTime::createFromFormat('Y-m-d H:i:s', $this->project_cgv->added)->format('d/m/Y à H:i:s') ?>
                        (<a href="<?= $this->furl . $this->project_cgv->getUrlPath() ?>" target="_blank">PDF</a>)
                        <?php if (\Unilend\Bundle\CoreBusinessBundle\Entity\UniversignEntityInterface::STATUS_SIGNED == $this->project_cgv->status && false === empty($this->project_cgv->updated)) : ?>
                            <strong>signées</strong> le <?= \DateTime::createFromFormat('Y-m-d H:i:s', $this->project_cgv->updated)->format('d/m/Y à H:i:s') ?>
                        <?php endif; ?>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <br>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" action="<?= $this->lurl ?>/dossiers/file/<?= $this->params[0] ?>" target="upload_target">
        <table id="documents-table" class="tablesorter">
            <thead>
                <tr>
                    <th>Document</th>
                    <th>Nom du fichier</th>
                    <th>Télécharger</th>
                </tr>
            </thead>
            <tbody>
                <?php $currentCategory = null; ?>
                <?php /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\ProjectAttachmentType $attachmentType */ ?>
                <?php foreach ($this->attachmentTypes as $attachmentType) : ?>
                    <?php if ($attachmentType->getIdCategory()->getId() !== $currentCategory) : ?>
                        <?php $currentCategory = $attachmentType->getIdCategory()->getId(); ?>
                        <tr class="attachment-category">
                            <th colspan="3">
                                <?= $attachmentType->getIdCategory()->getName() ?>
                                <?php if (isset($this->projectAttachmentsCountByCategory[$attachmentType->getIdCategory()->getId()])) : ?>
                                    (<?= $this->projectAttachmentsCountByCategory[$attachmentType->getIdCategory()->getId()] ?>)
                                <?php endif; ?>
                            </th>
                        </tr>
                    <?php endif; ?>

                    <tr<?php if (in_array($attachmentType->getIdType()->getId(), $this->mandatoryAttachmentTypes)) : ?> class="highlighted"<?php endif; ?>>
                        <td><?= $attachmentType->getName() ?></td>
                        <td>
                            <?php if (isset($this->projectAttachmentsByType[$attachmentType->getIdType()->getId()])) : ?>
                                <?php /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\ProjectAttachment $projectAttachment */ ?>
                                <?php foreach ($this->projectAttachmentsByType[$attachmentType->getIdType()->getId()] as $projectAttachment) : ?>
                                    <div>
                                        <a href="<?= $this->lurl ?>/viewer/<?= $this->projectEntity->getIdProject() ?>/<?= $projectAttachment->getAttachment()->getId() ?>" class="colorbox-iframe">
                                            <?= $projectAttachment->getAttachment()->getOriginalName() ?>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                                <?php unset($this->projectAttachmentsByType[$attachmentType->getIdType()->getId()]); ?>
                            <?php endif; ?>
                        </td>
                        <td><input type="file" name="<?= $attachmentType->getId() ?>" id="fichier_project_<?= $attachmentType->getId() ?>"></td>
                    </tr>
                <?php endforeach; ?>

                <?php if (false === empty($this->projectAttachmentsByType)) : ?>
                    <tr class="attachment-category">
                        <th colspan="3">Legacy (<?= count($this->projectAttachmentsByType) ?>)</th>
                    </tr>
                    <?php /** \Unilend\Bundle\CoreBusinessBundle\Entity\ProjectAttachment[] @var $projectAttachments */ ?>
                    <?php foreach ($this->projectAttachmentsByType as $attachmentTypeId => $projectAttachments) : ?>
                        <tr>
                            <td><?= $projectAttachments[0]->getAttachment()->getType()->getLabel() ?></td>
                            <td colspan="2">
                                <?php foreach ($projectAttachments as $projectAttachment) : ?>
                                    <div>
                                        <a href="<?= $this->lurl ?>/viewer/<?= $this->projectEntity->getIdProject() ?>/<?= $projectAttachment->getAttachment()->getId() ?>" class="colorbox-iframe">
                                            <?= $projectAttachment->getAttachment()->getOriginalName() ?>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <div id="valid_etape5" class="valid_etape"><br/>Données sauvegardées</div>
        <div class="btnDroite">
            <input type="hidden" name="send_etape5">
            <button type="submit" class="btn-primary">Sauvegarder</button>
        </div>
    </form>
    <div style="display:none;">
        <iframe id="upload_target" name="upload_target" src="about:blank"></iframe>
    </div>
</div>
