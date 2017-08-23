<?php if (count($this->projectComments) > 0) : ?>
    <table class="tablesorter">
        <thead>
        <tr>
            <th width="16">&nbsp;</th>
            <th width="120" align="center">Date</th>
            <th width="150" align="center">Auteur</th>
            <th align="center">Contenu</th>
            <th width="55">&nbsp;</th>
        </tr>
        </thead>
        <tbody>
        <?php $i = 1; ?>
        <?php /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsComments $comment */ ?>
        <?php foreach ($this->projectComments as $comment) : ?>
            <tr<?= ($i++ % 2 == 1 ? '' : ' class="odd"') ?> data-project-id="<?= $comment->getIdProject()->getIdProject() ?>" data-comment-id="<?= $comment->getIdProjectComment() ?>" data-public="<?php if (false == $comment->getPublic()) : ?>false<?php else: ?>true<?php endif; ?>">
                <td><?php if (false == $comment->getPublic()) : ?><img src="<?= $this->surl ?>/images/admin/lock.png" alt="Privé" style="margin: 0"><?php endif; ?></td>
                <td>
                    <?= $comment->getAdded()->format('d/m/Y H:i') ?>
                    <?php if ($comment->getUpdated()) : ?>
                        <br/>
                        <em style="font-size: 11px" title="Date de dernière modification"><?= $comment->getUpdated()->format('d/m/Y H:i') ?></em>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($comment->getIdUser() && $this->users->get($comment->getIdUser()->getIdUser())) : ?>
                        <?= $this->users->firstname ?> <?= $this->users->name ?>
                    <?php endif; ?>
                </td>
                <td class="content-memo"><?= preg_replace('/([^"])(https?:\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])([^"]?)/i', '$1<a href="$2" target="_blank">$2</a>$3', $comment->getContent()) ?></td>
                <td align="center">
                    <?php if ($this->userEntity == $comment->getIdUser()) : ?>
                        <a role="button" class="btn-edit-memo"><img src="<?= $this->surl ?>/images/admin/edit.png" alt="Modifier"/></a>
                        <a role="button" class="btn-delete-memo"><img src="<?= $this->surl ?>/images/admin/delete.png" alt="Supprimer"/></a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php $i++; ?>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<script>
    $(function() {
        $('.thickbox').colorbox();
    });
</script>
