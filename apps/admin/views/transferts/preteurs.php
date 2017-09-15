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
    function annulerAttribution(id_client, id_reception) {
        if (confirm('Voulez vous vraiment annuler le virement ?')) {
            var val = {
                id_client: id_client,
                id_reception: id_reception
            };

            $.post(add_url + '/transferts/annuler_attribution_preteur', val).done(function(data) {
                if (data != 'nok') {
                    $(".statut_operation_" + id_reception).parent('tr').fadeOut();
                }
            });
        }
    }

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
        $('.inline').colorbox({inline: true})
    });
</script>
<div id="contenu">
    <div class="row">
        <div class="col-md-6">
            <h1>Virements prêteurs</h1>
        </div>
        <div class="col-md-6">
            <a href="<?= $this->lurl ?>/transferts/preteurs/csv" class="btn-primary pull-right">Export CSV</a>
        </div>
    </div>
    <table id="receptions-table" class="table table-bordered table-striped">
        <thead>
            <tr>
                <th style="width:50px">ID</th>
                <th>Motif</th>
                <th style="width:150px">Montant</th>
                <th style="width:150px">Attribution</th>
                <th style="width:100px">ID client</th>
                <th style="width:100px">Date</th>
                <th style="width:100px">&nbsp;</th>
            </tr>
        </thead>
        <tbody>
            <?php use Unilend\Bundle\CoreBusinessBundle\Entity\Receptions; ?>
            <?php /** @var Receptions $reception */ ?>
            <?php foreach ($this->receptions as $reception) : ?>
                <tr class="reception-line" title="<?= htmlspecialchars(nl2br($reception->getComment()), ENT_QUOTES) ?>">
                    <td><?= $reception->getIdReception() ?></td>
                    <td><?= $reception->getMotif() ?></td>
                    <td style="text-align:right" data-order="<?= $reception->getMontant() ?>" data-search="<?= $reception->getMontant() ?>"><?= $this->ficelle->formatNumber($reception->getMontant() / 100) ?> €</td>
                    <td class="statut_operation_<?= $reception->getIdReception() ?>">
                        <?php if (Receptions::STATUS_ASSIGNED_MANUAL == $reception->getstatusBo() && $reception->getIdUser()): ?>
                            <?= $reception->getIdUser()->getFirstname() . ' ' . $reception->getIdUser()->getName() ?><br>
                            <?= $reception->getAssignmentDate()->format('d/m/Y H:i:s') ?>
                        <?php else: ?>
                            <?= $this->statusOperations[$reception->getstatusBo()] ?>
                        <?php endif; ?>
                    </td>
                    <td class="num_client_<?= $reception->getIdReception() ?>"><a href="<?= $this->lurl ?>/preteurs/edit/<?= $reception->getIdClient()->getIdClient() ?>"><?= $reception->getIdClient()->getIdClient() ?></a></td>
                    <td data-order="<?= $reception->getAdded()->getTimestamp() ?>"><?= $reception->getAdded()->format('d/m/Y') ?></td>
                    <td align="center">
                        <img class="annuler_<?= $reception->getIdReception() ?>" style="cursor:pointer;" onclick="annulerAttribution(<?= $reception->getIdClient()->getIdClient() ?>, <?= $reception->getIdReception() ?>)" src="<?= $this->surl ?>/images/admin/delete.png" alt="Annuler">
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
            <div id="inline-content-<?= $reception->getIdReception() ?>" style="white-space: nowrap; padding: 10px; background: #fff;"><?= $reception->getLigne() ?></div>
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
