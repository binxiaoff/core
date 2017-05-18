<script type="text/javascript">
    $(function() {
        $(".tablesorter").tablesorter({headers: {5: {sorter: false}}});

        <?php if ($this->nb_lignes != '') : ?>
            $(".tablesorter").tablesorterPager({container: $("#pager"), positionFixed: false, size: <?= $this->nb_lignes ?>});
        <?php endif; ?>
    });
</script>
<div id="contenu">
    <div class="row">
        <div class="col-sm-6">
            <h1>Liste des emails du site</h1>
        </div>
        <div class="col-sm-6">
            <a href="<?= $this->lurl ?>/mails/add" class="btn-primary pull-right">Ajouter un email</a>
        </div>
    </div>
    <?php if (count($this->lMails) > 0) : ?>
        <table class="tablesorter">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Nom Expéditeur</th>
                    <th>Email Expéditeur</th>
                    <th>Sujet</th>
                    <th>Mise à jour</th>
                    <th>&nbsp;</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; ?>
                <?php foreach ($this->lMails as $m) : ?>
                    <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>
                        <td><?= $m['type'] ?></td>
                        <td><?= $m['sender_name'] ?></td>
                        <td><?= $m['sender_email'] ?></td>
                        <td><?= $m['subject'] ?></td>
                        <td><?= $this->dates->formatDate($m['updated'], 'd/m/Y H:i') ?></td>
                        <td align="center">
                            <a href="<?= $this->lurl ?>/mails/edit/<?= $m['type'] ?>" title="Modifier <?= $m['type'] ?>">
                                <img src="<?= $this->surl ?>/images/admin/edit.png" alt="Modifier <?= $m['type'] ?>"/>
                            </a>
                            <a href="<?= $this->lurl ?>/mails/delete/<?= $m['type'] ?>" title="Archiver <?= $m['type'] ?>" onclick="return confirm('Etes vous sur de vouloir archiver <?= $m['type'] ?> ?')">
                                <img src="<?= $this->surl ?>/images/admin/delete.png" alt="Supprimer <?= $m['type'] ?>"/>
                            </a>
                        </td>
                    </tr>
                    <?php $i++; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if ($this->nb_lignes != '') : ?>
            <table>
                <tr>
                    <td id="pager">
                        <img src="<?= $this->surl ?>/images/admin/first.png" alt="Première" class="first"/>
                        <img src="<?= $this->surl ?>/images/admin/prev.png" alt="Précédente" class="prev"/>
                        <input type="text" class="pagedisplay"/>
                        <img src="<?= $this->surl ?>/images/admin/next.png" alt="Suivante" class="next"/>
                        <img src="<?= $this->surl ?>/images/admin/last.png" alt="Dernière" class="last"/>
                        <select class="pagesize">
                            <option value="<?= $this->nb_lignes ?>" selected="selected"><?= $this->nb_lignes ?></option>
                        </select>
                    </td>
                </tr>
            </table>
        <?php endif; ?>
    <?php else : ?>
        <p>Il n'y a aucun email pour le moment.</p>
    <?php endif; ?>
</div>
