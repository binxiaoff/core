<?php if (count($this->projectComments) > 0) : ?>
    <table class="tablesorter">
        <thead>
        <tr>
            <th width="16">&nbsp;</th>
            <th width="120" align="center">Date</th>
            <th width="150" align="center">Auteur</th>
            <th align="center">Contenu</th>
        </tr>
        </thead>
        <tbody>
        <?php $i = 1; ?>
        <?php /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsComments $comment */ ?>
        <?php foreach ($this->projectComments as $comment) : ?>
            <tr<?= ($i++ % 2 == 1 ? '' : ' class="odd"') ?> data-comment-id="<?= $comment->getIdProjectComment() ?>">
                <td>
                    <a href="#" class="memo-privacy-switch <?php if ($comment->getPublic()) : ?>public<?php else : ?>private<?php endif; ?>"></a>
                </td>
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
            </tr>
            <?php $i++; ?>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
