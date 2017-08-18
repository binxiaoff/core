<script>
    $(function() {
        jQuery.tablesorter.addParser({
            id: "fancyNumber", is: function (s) {
                return /[\-\+]?\s*[0-9]{1,3}(\.[0-9]{3})*,[0-9]+/.test(s);
            }, format: function (s) {
                return jQuery.tablesorter.formatFloat(s.replace(/,/g, '').replace(' €', '').replace(' ', ''));
            }, type: "numeric"
        });

        $('.tablesorter').tablesorter({headers: {4: {sorter: false}}});

        <?php if (count($this->nonAttributedReceptions) > $this->nb_lignes) : ?>
            $('.tablesorter').tablesorterPager({container: $('#pager'), positionFixed: false, size: <?= $this->nb_lignes ?>});
        <?php endif; ?>

        $('.inline').colorbox({inline: true});

        $('.ignore-line').on('click', function () {
            var reception = $(this).parents('tr').data('reception-id')
            $('#ignore-line-form').find('[name=reception]').val(reception)
        })

        $('#ignore-line-form').on('submit', function (event) {
            event.preventDefault()

            var $form = $(this)
            var $reception = $form.find('[name=reception]')

            if (! $reception || ! $reception.val()) {
                alert('Opération inconnue')
                return
            }

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
    });
</script>
<div id="contenu">
    <h1>Opérations non affectées (<?= count($this->nonAttributedReceptions) ?>)</h1>
    <table class="tablesorter table table-bordered table-striped">
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
                <tr data-reception-id="<?= $reception->getIdReception() ?>">
                    <td><?= $reception->getIdReception() ?></td>
                    <td><?= $reception->getMotif() ?></td>
                    <td class="text-right"><?= $this->ficelle->formatNumber($reception->getMontant() / 100) ?> €</td>
                    <td class="text-center"><?= $reception->getAdded()->format('d/m/Y') ?></td>
                    <td class="text-center">
                        <a class="thickbox ajouter_<?= $reception->getIdReception() ?>" href="<?= $this->lurl ?>/transferts/attribution/<?= $reception->getIdReception() ?>">
                            <img src="<?= $this->surl ?>/images/admin/edit.png" alt="Attribuer l'opération">
                        </a>
                        <a class="inline" href="#line-content-<?= $reception->getIdReception() ?>" title="Afficher l'opération">
                            <img src="<?= $this->surl ?>/images/admin/modif.png" alt="Afficher l'opération">
                        </a>
                        <a class="inline ignore-line" href="#ignore-line">
                            <img src="<?= $this->surl ?>/images/admin/delete.png" alt="Ignorer l'opération">
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if (count($this->nonAttributedReceptions) > $this->nb_lignes) : ?>
        <table>
            <tr>
                <td id="pager">
                    <img src="<?= $this->surl ?>/images/admin/first.png" alt="Première" class="first">
                    <img src="<?= $this->surl ?>/images/admin/prev.png" alt="Précédente" class="prev">
                    <input type="text" class="pagedisplay">
                    <img src="<?= $this->surl ?>/images/admin/next.png" alt="Suivante" class="next">
                    <img src="<?= $this->surl ?>/images/admin/last.png" alt="Dernière" class="last">
                    <select class="pagesize">
                        <option value="<?= $this->nb_lignes ?>" selected="selected"><?= $this->nb_lignes ?></option>
                    </select>
                </td>
            </tr>
        </table>
    <?php endif; ?>
    <div class="hidden">
        <?php foreach ($this->nonAttributedReceptions as $reception) : ?>
            <div id="line-content-<?= $reception->getIdReception() ?>" style="white-space: nowrap; padding: 10px; background: #fff;"><?= $reception->getLigne() ?></div>
        <?php endforeach; ?>
        <div id="ignore-line" style="padding: 10px; min-width: 500px;">
            <form id="ignore-line-form" method="POST" action="<?= $this->lurl ?>/transferts/ignore">
                <input type="hidden" name="reception">
                <h1>Ignorer une opération</h1>
                <button type="submit" class="btn-primary pull-right">Valider</button>
            </form>
        </div>
    </div>
</div>
