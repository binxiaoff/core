<a class="tab_title" id="section-documents" href="#section-documents">5. Documents</a>
<div class="tab_content" id="etape5">
    <script type="text/javascript">
        Dropzone.prototype.defaultOptions.dictDefaultMessage = 'Déposez vos fichiers ici'
        Dropzone.prototype.defaultOptions.dictFallbackMessage = 'Votre navigateur ne supporte pas les chargements par drag & drop'
        Dropzone.prototype.defaultOptions.dictFallbackText = 'Veuillez utiliser le formulaire ci-dessous pour charger vos fichiers.'
        Dropzone.prototype.defaultOptions.dictFileTooBig = 'Taille du fichier trop importante ({{filesize}} Mo). Taille max : {{maxFilesize}} Mo.'
        Dropzone.prototype.defaultOptions.dictInvalidFileType = 'Vous ne pouvez pas charger ce type de fichier. Formats pris en charge : PDF, JPEG, PNG, DOC, XLS'
        Dropzone.prototype.defaultOptions.dictResponseError = 'Le serveur a répondu avec un code {{statusCode}}.'
        Dropzone.prototype.defaultOptions.dictCancelUpload = 'Annuler le chargement'
        Dropzone.prototype.defaultOptions.dictCancelUploadConfirmation = 'Voulez-vous vraiment annuler le chargement ?'
        Dropzone.prototype.defaultOptions.dictRemoveFile = 'Supprimer le fichier'
        Dropzone.prototype.defaultOptions.dictRemoveFileConfirmation = null
        Dropzone.prototype.defaultOptions.dictMaxFilesExceeded = 'Vous ne pouvez pas charger de fichier supplémentaire.'
        Dropzone.autoDiscover = false

        $(function () {
            $('.dropzone').each(function () {
                var $form = $(this)
                var myDropzone = new Dropzone('#' + $form.attr('id'), {
                    uploadMultiple: true,
                    maxFiles: $form.data('dz-maxfiles'),
                    acceptedFiles: '.pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx'
                })

                myDropzone.on('success', function (file, response) {
                    myDropzone.removeFile(file)

                    if (false === $.isEmptyObject(response) && response.hasOwnProperty('success') && response.success && response.hasOwnProperty('data') && $.isArray(response.data)) {
                        $.each(response.data, function (index, file) {
                            var $removeLink = $('<a>').addClass('attachment-remove').attr('href', '<?= $this->lurl ?>/attachment/remove_project/' + file.projectAttachmentId).append('<img src="<?= $this->surl ?>/images/admin/delete.png" alt="Supprimer">')
                            var $viewerLink = $('<a>').attr('href', '<?= $this->lurl ?>/viewer/project/<?= $this->projectEntity->getIdProject() ?>/' + file.attachmentId).attr('target', '_blank').append(file.name)
                            var $file = $('<div>').addClass('attachment-file').append($removeLink).append($viewerLink)

                            $form.closest('tr').find('td:nth(1)').append($file)
                        })
                    } else {
                        var errorMessage = 'Erreur de chargement. Réponse serveur incorrecte.'
                        if (false === $.isEmptyObject(response) && response.hasOwnProperty('error') && $.isArray(response.error)) {
                            errorMessage = response.error[0]
                        }

                        alert(errorMessage)
                        console.log(response)
                    }
                })

                myDropzone.on('error', function (file, errorMessage) {
                    myDropzone.removeFile(file)

                    alert(errorMessage)
                    console.log(errorMessage)
                })
            })

            $('body')
                .on('click', '.attachment-category', function () {
                    $(this).toggleClass('expand').nextUntil('.attachment-category').slideToggle()
                })
                .on('click', '.attachment-remove', function (event) {
                    event.preventDefault()

                    var $link = $(event.currentTarget)

                    $.ajax({
                        url: $link.attr('href'),
                        dataType: 'json',
                        beforeSend: function () {
                            $link.find('img').attr('src', '<?= $this->lurl ?>/oneui/js/plugins/bootstrap3-editable/img/loading.gif')
                        },
                        success: function (data) {
                            if (false === $.isEmptyObject(data) && data.hasOwnProperty('success') && data.success) {
                                $link.find('img').attr('src', '<?= $this->surl ?>/images/admin/check.png')
                                $link.parent('.attachment-file').delay(2000).slideUp('normal', function() {
                                    $(this).remove()
                                })
                            } else {
                                $link.find('img').attr('src', '<?= $this->surl ?>/images/admin/delete.png')
                                alert('Une erreur est survenue lors de la suppression du document')
                                console.log(data)
                            }
                        },
                        error: function (xhr, status, error) {
                            $link.find('img').attr('src', '<?= $this->surl ?>/images/admin/delete.png')
                            alert('Une erreur est survenue lors de la suppression du document')
                            console.log(error)
                        }
                    })
                })
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

    <table id="attachments-table" class="tablesorter">
        <colgroup>
            <col style="width: 35%">
            <col style="width: 35%">
            <col style="width: 30%">
        </colgroup>
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
                    <td>
                        <?= $attachmentType->getName() ?>
                        <?php if ($attachmentType->getMaxItems() > 1) : ?>
                            <div class="pull-right" style="font-size: 11px">jusqu'à <?= $attachmentType->getMaxItems() ?> documents</div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (isset($this->projectAttachmentsByType[$attachmentType->getIdType()->getId()])) : ?>
                            <?php /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\ProjectAttachment $projectAttachment */ ?>
                            <?php foreach ($this->projectAttachmentsByType[$attachmentType->getIdType()->getId()] as $projectAttachment) : ?>
                                <div class="attachment-file">
                                    <a class="attachment-remove" href="<?= $this->lurl ?>/attachment/remove_project/<?= $projectAttachment->getId() ?>">
                                        <img src="<?= $this->surl ?>/images/admin/delete.png" alt="Supprimer">
                                    </a>
                                    <a href="<?= $this->lurl ?>/viewer/project/<?= $this->projectEntity->getIdProject() ?>/<?= $projectAttachment->getAttachment()->getId() ?>" target="_blank">
                                        <?= $projectAttachment->getAttachment()->getOriginalName() ?>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                            <?php unset($this->projectAttachmentsByType[$attachmentType->getIdType()->getId()]); ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <!-- Ajouter la ligne dans la colonne fichier si chargement OK -->
                        <form id="upload-form-<?= $attachmentType->getIdType()->getId() ?>" class="dropzone" action="<?= $this->lurl ?>/attachment/upload_project" method="post"
                              data-dz-maxfiles="<?= $attachmentType->getMaxItems() ?>">
                            <input type="hidden" name="id_project" value="<?= $this->projectEntity->getIdProject() ?>">
                            <input type="hidden" name="id_attachment" value="<?= $attachmentType->getIdType()->getId() ?>">
                        </form>
                    </td>
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
                                <div class="attachment-file">
                                    <a class="attachment-remove" href="<?= $this->lurl ?>/attachment/remove_project/<?= $projectAttachment->getId() ?>">
                                        <img src="<?= $this->surl ?>/images/admin/delete.png" alt="Supprimer">
                                    </a>
                                    <a href="<?= $this->lurl ?>/viewer/project/<?= $this->projectEntity->getIdProject() ?>/<?= $projectAttachment->getAttachment()->getId() ?>" target="_blank">
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
</div>
