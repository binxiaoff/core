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
        <div class="col-md-6">
            <h1>Liste des emails du site</h1>
        </div>
        <div class="col-md-6">
            <a href="<?= $this->lurl ?>/mails/add" class="btn-primary pull-right">Ajouter un email</a>
        </div>
    </div>

    <div id="external_emails">
        <h2>Emails externes</h2>
        <?php if (count($this->externalEmails) > 0) : ?>
            <table class="tablesorter">
                <thead>
                <tr>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th colspan="3">Nombre d'emails envoyés dans la periode 24h</th>
                    <th>&nbsp;</th>
                </tr>
                <tr>
                    <th>Type</th>
                    <th>Nom Expéditeur</th>
                    <th>Email Expéditeur</th>
                    <th>Sujet</th>
                    <th>Mise à jour</th>
                    <th>24h</th>
                    <th>7 jours</th>
                    <th>30 jours</th>
                    <th>&nbsp;</th>
                </tr>
                </thead>
                <tbody>
                <?php $i = 1; ?>
                <?php foreach ($this->externalEmails as $mailTemplate) : ?>
                    <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>
                        <td><?= $mailTemplate->getType() ?></td>
                        <td><?= $mailTemplate->getSenderName() ?></td>
                        <td><?= $mailTemplate->getSenderEmail() ?></td>
                        <td><?= $mailTemplate->getSubject() ?></td>
                        <td><?= $mailTemplate->getUpdated()->format('d/m/Y H:i') ?></td>
                        <td><?= $this->externalEmailUsage[$mailTemplate->getType()]['24h'] ?></td>
                        <td><?= $this->externalEmailUsage[$mailTemplate->getType()]['7d'] ?></td>
                        <td><?= $this->externalEmailUsage[$mailTemplate->getType()]['30d'] ?></td>
                        <td align="center">
                            <a href="<?= $this->lurl ?>/mails/edit/<?= $mailTemplate->getType() ?>" title="Modifier <?= $mailTemplate->getType() ?>">
                                <img src="<?= $this->surl ?>/images/admin/edit.png" alt="Modifier <?= $mailTemplate->getType() ?>"/>
                            </a>
                            <a href="<?= $this->lurl ?>/mails/delete/<?= $mailTemplate->getType() ?>" title="Archiver <?= $mailTemplate->getType() ?>" onclick="return confirm('Etes vous sur de vouloir archiver <?= $mailTemplate->getType() ?> ?')">
                                <img src="<?= $this->surl ?>/images/admin/delete.png" alt="Supprimer <?= $mailTemplate->getType() ?>"/>
                            </a>
                        </td>
                    </tr>
                    <?php $i++; ?>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p>Il n'y a aucun email pour le moment.</p>
        <?php endif; ?>
    </div>

    <div id="internal_emails">
        <h2>Emails internes</h2>
        <?php if (count($this->internalEmails) > 0) : ?>
            <table class="tablesorter">
                <thead>
                <tr>
                    <th>Type</th>
                    <th>Nom Expéditeur</th>
                    <th>Email Expéditeur</th>
                    <th>Sujet</th>
                    <th>Mise à jour</th>
                    <th>Nombre d'envois dans les 30 denriers jours</th>
                    <th>&nbsp;</th>
                </tr>
                </thead>
                <tbody>
                <?php $i = 1; ?>
                <?php foreach ($this->internalEmails as $mailTemplate) : ?>
                    <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>
                        <td><?= $mailTemplate->getType() ?></td>
                        <td><?= $mailTemplate->getSenderName() ?></td>
                        <td><?= $mailTemplate->getSenderEmail() ?></td>
                        <td><?= $mailTemplate->getSubject() ?></td>
                        <td><?= $mailTemplate->getUpdated()->format('d/m/Y H:i') ?></td>
                        <td><?= $this->internalEmailUsage[$mailTemplate->getType()]['30d'] ?></td>
                        <td align="center">
                            <a href="<?= $this->lurl ?>/mails/edit/<?= $mailTemplate->getType() ?>" title="Modifier <?= $mailTemplate->getType() ?>">
                                <img src="<?= $this->surl ?>/images/admin/edit.png" alt="Modifier <?= $mailTemplate->getType() ?>"/>
                            </a>
                            <a href="<?= $this->lurl ?>/mails/delete/<?= $mailTemplate->getType() ?>" title="Archiver <?= $mailTemplate->getType() ?>" onclick="return confirm('Etes vous sur de vouloir archiver <?= $mailTemplate->getType() ?> ?')">
                                <img src="<?= $this->surl ?>/images/admin/delete.png" alt="Supprimer <?= $mailTemplate->getType() ?>"/>
                            </a>
                        </td>
                    </tr>
                    <?php $i++; ?>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p>Il n'y a aucun email pour le moment.</p>
        <?php endif; ?>
    </div>
</div>
