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
            <tr<?= ($i++ % 2 == 1 ? '' : ' class="odd"') ?>>
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
                <td><?= preg_replace('/(https?:\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/i', '<a href="$1" target="_blank">$1</a>', nl2br($comment->getContent())) ?></td>
                <td align="center">
                    <?php if ($this->userEntity == $comment->getIdUser()) : ?>
                        <a href="<?= $this->lurl ?>/dossiers/memo/<?= $comment->getIdProject()->getIdProject() ?>/<?= $comment->getIdProjectComment() ?>" class="thickbox"><img src="<?= $this->surl ?>/images/admin/edit.png" alt="Modifier"/></a>
                        <img style="cursor:pointer;" onclick="deleteMemo(<?= $comment->getIdProject()->getIdProject() ?>, <?= $comment->getIdProjectComment() ?>)" src="<?= $this->surl ?>/images/admin/delete.png" alt="Supprimer"/>
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
