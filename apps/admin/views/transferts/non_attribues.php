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
                {orderable: false, targets: [0, 1, 4]},
                {searchable: false, targets: 4}
            ],
            language: {
                url: '<?= $this->lurl ?>/oneui/js/plugins/datatables/localisation/fr_FR.json'
            }
        })

        $('.ignore-line-form').on('submit', function (event) {
            event.preventDefault()

            var $form = $(this)
            var $reception = $form.find('[name=reception]')

            $.ajax({
                url: $form.attr('action'),
                method: $form.attr('method'),
                data: $form.serialize(),
                success: function (response) {
                    $.colorbox.close()
                    if (response === 'ok') {
                        $('tr[data-reception-id=' + $reception.val() + ']').fadeOut()
                    } else {
                        alert('Une erreur est survenue')
                    }
                },
                error: function () {
                    $.colorbox.close()
                    alert('Une erreur est survenue')
                }
            })
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
    <h1>Opérations non affectées (<?= count($this->nonAttributedReceptions) ?>)</h1>
    <table id="receptions-table" class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Motif</th>
                <th>Montant</th>
                <th>Date</th>
                <th>&nbsp;</th>
            </tr>
        </thead>
        <tbody>
            <?php /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Receptions $reception */ ?>
            <?php foreach ($this->nonAttributedReceptions as $reception) : ?>
                <tr data-reception-id="<?= $reception->getIdReception() ?>" class="reception-line" title="<?= htmlspecialchars(nl2br($reception->getComment()), ENT_QUOTES) ?>">
                    <td><?= $reception->getIdReception() ?></td>
                    <td><?= $reception->getMotif() ?></td>
                    <td class="text-right" data-order="<?= $reception->getMontant() ?>" data-search="<?= $reception->getMontant() ?>"><?= $this->ficelle->formatNumber($reception->getMontant() / 100) ?> €</td>
                    <td class="text-center" data-order="<?= $reception->getAdded()->getTimestamp() ?>"><?= $reception->getAdded()->format('d/m/Y') ?></td>
                    <td class="text-center">
                        <a class="thickbox ajouter_<?= $reception->getIdReception() ?>" href="<?= $this->lurl ?>/transferts/attribution/<?= $reception->getIdReception() ?>"><img src="<?= $this->surl ?>/images/admin/check.png" alt="Attribuer l'opération"></a>
                        <a class="inline" href="#ignore-line-<?= $reception->getIdReception() ?>"><img src="<?= $this->surl ?>/images/admin/delete.png" alt="Ignorer l'opération"></a>
                        <a class="inline" href="#comment-line-<?= $reception->getIdReception() ?>"><img src="<?= $this->surl ?>/images/admin/edit.png" alt="Commenter l'opération"></a>
                        <a class="inline" href="#content-line-<?= $reception->getIdReception() ?>" title="Ligne de réception"><img src="<?= $this->surl ?>/images/admin/modif.png" alt="Afficher l'opération"></a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div class="hidden">
        <?php foreach ($this->nonAttributedReceptions as $reception) : ?>
            <div id="content-line-<?= $reception->getIdReception() ?>" style="white-space: nowrap; padding: 10px;"><?= $reception->getLigne() ?></div>
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
            <div id="ignore-line-<?= $reception->getIdReception() ?>" style="padding: 10px; min-width: 500px;">
                <form id="ignore-line-form-<?= $reception->getIdReception() ?>" class="ignore-line-form" method="POST" action="<?= $this->lurl ?>/transferts/ignore">
                    <input type="hidden" name="reception" value="<?= $reception->getIdReception() ?>">
                    <h1>Ignorer une opération</h1>
                    <div class="form-group">
                        <label for="ignore-line-comment-<?= $reception->getIdReception() ?>">Commentaire</label>
                        <textarea id="ignore-line-comment-<?= $reception->getIdReception() ?>" name="comment" rows="5" class="form-control"><?= $reception->getComment() ?></textarea>
                    </div>
                    <div class="text-right">
                        <button type="button" class="btn-default" onclick="$.fn.colorbox.close()">Annuler</button>
                        <button type="submit" class="btn-primary">Valider</button>
                    </div>
                </form>
            </div>
            <div id="comment-<?= $reception->getIdReception() ?>"><?= nl2br($reception->getComment()) ?></div>
        <?php endforeach; ?>
    </div>
</div>
<script src="<?= $this->lurl ?>/oneui/js/plugins/datatables/jquery.dataTables.min.js"></script>
<link type="text/css" rel="stylesheet" href="<?= $this->lurl ?>/oneui/js/plugins/datatables/jquery.dataTables.min.css">
