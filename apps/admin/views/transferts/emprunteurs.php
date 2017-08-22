<style>
    @font-face {
        font-family: 'FontAwesome';
        src: url('<?= $this->lurl ?>/oneui/fonts/fontawesome-webfont.eot');
        src: url('<?= $this->lurl ?>/oneui/fonts/fontawesome-webfont.eot?#iefix&v=4.7.0') format('embedded-opentype'),
        url('<?= $this->lurl ?>/oneui/fonts/fontawesome-webfont.woff2') format('woff2'),
        url('<?= $this->lurl ?>/oneui/fonts/fontawesome-webfont.woff') format('woff'),
        url('<?= $this->lurl ?>/oneui/fonts/fontawesome-webfont.ttf') format('truetype'),
        url('<?= $this->lurl ?>/oneui/fonts/fontawesome-webfont.svg#fontawesomeregular') format('svg');
        font-weight: normal;
        font-style: normal;
    }

    table.dataTable thead .sorting:after,
    table.dataTable thead .sorting_asc:after,
    table.dataTable thead .sorting_desc:after {
        top: 7px !important;
    }
</style>
<script>
    $(function() {
        $('#receptions-table').DataTable({
            order: [[0, 'desc']],
            pageLength: <?= $this->nb_lignes ?>,
            bLengthChange: false,
            columnDefs: [
                {orderable: false, targets: [0, 1, 6]},
                {searchable: false, targets: 6}
            ],
            language: {
                url: '<?= $this->lurl ?>/oneui/js/plugins/datatables/localisation/fr_FR.json'
            }
        })

        $('.reception-line').tooltip({
            position: {my: 'left top', at: 'right top'},
            content: function () {
                return $(this).prop('title')
            }
        })

        $('.inline').tooltip({disabled: true})
        $('.inline').colorbox({inline: true, width: '50%'})
    });
</script>
<div id="contenu">
    <div class="row">
        <div class="col-md-6">
            <h1>Opérations emprunteurs</h1>
        </div>
        <div class="col-md-6">
            <a href="<?= $this->lurl ?>/transferts/emprunteurs/csv" class="btn-primary pull-right">Export CSV</a>
        </div>
    </div>
    <table id="receptions-table" class="table table-bordered table-striped">
        <thead>
            <tr>
                <th style="width:50px">ID</th>
                <th>Motif</th>
                <th style="width:150px">Montant</th>
                <th style="width:150px">Attribution</th>
                <th style="width:100px">ID projet</th>
                <th style="width:100px">Date</th>
                <th style="width:100px">&nbsp;</th>
            </tr>
        </thead>
        <tbody>
            <?php use Unilend\Bundle\CoreBusinessBundle\Entity\Receptions; ?>
            <?php /** @var Receptions $reception */ ?>
            <?php foreach ($this->receptions as $reception) : ?>
                <?php $isNegativeAmount = Receptions::TYPE_DIRECT_DEBIT == $reception->getType() && Receptions::DIRECT_DEBIT_STATUS_REJECTED == $reception->getStatusPrelevement(); ?>
                <tr class="reception-line<?= $isNegativeAmount ? ' danger' : '' ?>" title="<?= htmlspecialchars(nl2br($reception->getComment()), ENT_QUOTES) ?>">
                    <td><?= $reception->getIdReception() ?></td>
                    <td><?= $reception->getMotif() ?></td>
                    <td class="text-right" data-order="<?= $reception->getMontant() ?>" data-search="<?= $reception->getMontant() ?>"><?= $isNegativeAmount ? '- ' : '' ?><?= $this->ficelle->formatNumber($reception->getMontant() / 100) ?> €</td>
                    <td class="statut_operation_<?= $reception->getIdReception() ?>">
                        <?php if (Receptions::STATUS_ASSIGNED_MANUAL == $reception->getStatusBo() && null !== $reception->getIdUser()) : ?>
                            <?= $reception->getIdUser()->getFirstname() . ' ' . $reception->getIdUser()->getName() ?><br>
                            <?= $reception->getAssignmentDate()->format('d/m/Y H:i:s') ?>
                        <?php else : ?>
                            <?= $this->statusOperations[$reception->getStatusBo()] ?>
                        <?php endif; ?>
                    </td>
                    <td class="num_project_<?= $reception->getIdReception() ?>"><a href="<?= $this->lurl ?>/dossiers/edit/<?= $reception->getIdProject()->getIdProject() ?>"><?= $reception->getIdProject()->getIdProject() ?></a></td>
                    <td data-order="<?= $reception->getAdded()->getTimestamp() ?>"><?= $reception->getAdded()->format('d/m/Y') ?></td>
                    <td align="center">
                        <a class="inline" href="#comment-line-<?= $reception->getIdReception() ?>"><img src="<?= $this->surl ?>/images/admin/edit.png" alt="Commenter l'opération"></a>
                        <a class="inline" href="#inline-content-<?= $reception->getIdReception() ?>" title="Ligne de réception">
                            <img src="<?= $this->surl ?>/images/admin/modif.png" alt="Afficher l'opération">
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div class="hidden">
        <?php foreach ($this->receptions as $reception) : ?>
            <div id="inline-content-<?= $reception->getIdReception() ?>" style="white-space: nowrap; padding: 10px; background:#fff;"><?= $reception->getLigne() ?></div>
            <div id="comment-line-<?= $reception->getIdReception() ?>" style="padding: 10px; min-width: 500px;">
                <form id="comment-line-form-<?= $reception->getIdReception() ?>" class="comment-line-form" method="POST" action="<?= $this->lurl ?>/transferts/comment">
                    <input type="hidden" name="reception" value="<?= $reception->getIdReception() ?>">
                    <input type="hidden" name="referer" value="<?= $_SERVER['REQUEST_URI'] ?>">
                    <h1>Commenter une opération</h1>
                    <div class="form-group">
                        <label for="comment-line-comment-<?= $reception->getIdReception() ?>" class="sr-only">Commentaire</label>
                        <textarea id="comment-line-comment-<?= $reception->getIdReception() ?>" name="comment" rows="5" class="form-control"><?= $reception->getComment() ?></textarea>
                    </div>
                    <div class="text-right">
                        <button type="button" class="btn-default" onclick="$.fn.colorbox.close()">Annuler</button>
                        <button type="submit" class="btn-primary">Valider</button>
                    </div>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<script src="<?= $this->lurl ?>/oneui/js/plugins/datatables/jquery.dataTables.min.js"></script>
<link type="text/css" rel="stylesheet" href="<?= $this->lurl ?>/oneui/js/plugins/datatables/jquery.dataTables.min.css">
