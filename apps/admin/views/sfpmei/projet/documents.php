<div class="row">
    <div class="col-md-12">
        <?php if (count($this->aAttachments) > 0) : ?>
            <table class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th>Nom</th>
                    <th>Fichier</th>
                </tr>
                </thead>
                <tbody>
                <?php
                /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\AttachmentType $attachmentType */
                foreach ($this->aAttachmentTypes as $attachmentType) : ?>
                    <tr>
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
                            <td class="type_col"><?= $attachmentType->getLabel() ?></td>
                            <td class="label_col">
                                <a href="<?= $this->url ?>/attachment/download/id/<?= $currentAttachment->getId() ?>/file/<?= urlencode($currentAttachment->getPath()) ?>"><?= $currentAttachment->getPath() ?></a>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            Aucun document.
        <?php endif; ?>
    </div>
</div>
