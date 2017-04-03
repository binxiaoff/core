<?php if (count($this->projectComments) > 0) : ?>
    <table class="tablesorter">
        <thead>
        <tr>
            <th width="120" align="center">Date</th>
            <th width="150" align="center">Auteur</th>
            <th align="center">Contenu</th>
            <th width="50">&nbsp;</th>
        </tr>
        </thead>
        <tbody>
        <?php $i = 1; ?>
        <?php foreach ($this->projectComments as $comment) : ?>
            <tr<?= ($i++ % 2 == 1 ? '' : ' class="odd"') ?>>
                <td>
                    <?= $this->dates->formatDate($comment['added'], 'd/m/Y H:i') ?>
                    <?php if ($comment['added'] !== $comment['updated']) : ?>
                        <br/>
                        <em style="font-size: 11px" title="Date de dernière modification"><?= $this->dates->formatDate($comment['updated'], 'd/m/Y H:i') ?></em>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (false === empty($comment['id_user']) && $this->users->get($comment['id_user'])) : ?>
                        <?= $this->users->firstname ?> <?= $this->users->name ?>
                    <?php endif; ?>
                </td>
                <td><?= nl2br($comment['content']) ?></td>
                <td align="center">
                    <?php if ($comment['id_user'] == $_SESSION['user']['id_user']) : ?>
                        <a href="<?= $this->lurl ?>/dossiers/memo/<?= $comment['id_project'] ?>/<?= $comment['id_project_comment'] ?>" class="thickbox"><img src="<?= $this->surl ?>/images/admin/edit.png" alt="Modifier"/></a>
                        <img style="cursor:pointer;" onclick="deleteMemo(<?= $comment['id_project'] ?>, <?= $comment['id_project_comment'] ?>)" src="<?= $this->surl ?>/images/admin/delete.png" alt="Supprimer"/>
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
        $(".thickbox").colorbox();
    });
</script>
