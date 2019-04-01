<div class="row">
    <div class="col-md-12">
        <?php if (count($this->attachments) > 0) : ?>
            <table class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th>Nom</th>
                    <th>Fichier</th>
                </tr>
                </thead>
                <tbody>
                <?php
                /** @var \Unilend\Entity\ProjectAttachment $projectAttachment */
                foreach ($this->attachments as $projectAttachment) : ?>
                    <?php $attachment = $projectAttachment->getAttachment(); ?>
                    <tr>
                        <td class="type_col"><?= $attachment->getType()->getLabel() ?></td>
                        <td class="label_col">
                            <a href="<?= $this->lurl ?>/viewer/project/<?= $this->projects->id_project ?>/<?= $attachment->getId() ?>" target="_blank">
                                <?= $attachment->getOriginalName() ?? basename($attachment->getPath()) ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            Aucun document.
        <?php endif; ?>
    </div>
</div>
