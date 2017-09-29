<style>
    @font-face {
        font-family: 'FontAwesome';
        src: url('https://admin.local.unilend.fr/oneui/fonts/fontawesome-webfont.eot');
        src: url('https://admin.local.unilend.fr/oneui/fonts/fontawesome-webfont.eot?#iefix&v=4.7.0') format('embedded-opentype'),
        url('https://admin.local.unilend.fr/oneui/fonts/fontawesome-webfont.woff2') format('woff2'),
        url('https://admin.local.unilend.fr/oneui/fonts/fontawesome-webfont.woff') format('woff'),
        url('https://admin.local.unilend.fr/oneui/fonts/fontawesome-webfont.ttf') format('truetype'),
        url('https://admin.local.unilend.fr/oneui/fonts/fontawesome-webfont.svg#fontawesomeregular') format('svg');
        font-weight: normal;
        font-style: normal;
    }
</style>
<link rel="stylesheet" href="<?= $this->url ?>/oneui/js/plugins/datatables/jquery.dataTables.min.css">
<script src="<?= $this->url ?>/oneui/js/plugins/datatables/jquery.dataTables.min.js"></script>
<script>
    $(function() {
        $('.js-datatable').dataTable({
            info: false,
            paging: false,
            searching: false,
            columnDefs: [{targets: 7, orderable: false}]
        })
    })
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
    <?php foreach ($this->sections as $section) : ?>
        <div class="row">
            <div class="col-md-12">
                <h2><?= $section['title'] ?></h2>
                <?php if (count($section['emails']) > 0) : ?>
                    <table class="table table-bordered table-striped js-datatable">
                        <thead>
                        <tr>
                            <th>Type</th>
                            <th>Sujet</th>
                            <th>Expéditeur</th>
                            <th>Mise à jour</th>
                            <th>24h</th>
                            <th>7j</th>
                            <th>30j</th>
                            <th>&nbsp;</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($section['emails'] as $mailTemplate) : ?>
                            <tr>
                                <td><?= $mailTemplate->getType() ?></td>
                                <td><?= $mailTemplate->getSubject() ?></td>
                                <td class="text-nowrap">
                                    <?= $mailTemplate->getSenderName() ?><br>
                                    <em><?= $mailTemplate->getSenderEmail() ?></em>
                                </td>
                                <td><?= $mailTemplate->getAdded()->format('d/m/Y H:i') ?></td>
                                <td><?= $section['stats'][$mailTemplate->getType()]['day'] ?></td>
                                <td><?= $section['stats'][$mailTemplate->getType()]['week'] ?></td>
                                <td><?= $section['stats'][$mailTemplate->getType()]['month'] ?></td>
                                <td align="center">
                                    <a href="<?= $this->lurl ?>/mails/edit/<?= $mailTemplate->getType() ?>" title="Modifier <?= $mailTemplate->getType() ?>">
                                        <img src="<?= $this->surl ?>/images/admin/edit.png" alt="Modifier <?= $mailTemplate->getType() ?>"/>
                                    </a>
                                    <a href="<?= $this->lurl ?>/mails/delete/<?= $mailTemplate->getType() ?>" title="Archiver <?= $mailTemplate->getType() ?>" onclick="return confirm('Etes vous sur de vouloir archiver <?= $mailTemplate->getType() ?> ?')">
                                        <img src="<?= $this->surl ?>/images/admin/delete.png" alt="Supprimer <?= $mailTemplate->getType() ?>"/>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <p>Aucun email</p>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
