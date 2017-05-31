<script type="text/javascript">
    $(function() {
        jQuery.tablesorter.addParser({
            id: "fancyNumber", is: function (s) {
                return /[\-\+]?\s*[0-9]{1,3}(\.[0-9]{3})*,[0-9]+/.test(s);
            }, format: function (s) {
                return jQuery.tablesorter.formatFloat(s.replace(/,/g, '').replace(' €', '').replace(' ', ''));
            }, type: "numeric"
        });

        $(".tablesorter").tablesorter({headers: {5: {sorter: false}}});

        <?php if ($this->nb_lignes != '') : ?>
            $(".tablesorter").tablesorterPager({container: $("#pager"), positionFixed: false, size: <?= $this->nb_lignes ?>});
        <?php endif; ?>

        $(".inline").colorbox({inline: true, width: "50%"});
    });
</script>
<div id="contenu">
    <h1>Liste des mouvements non affectés</h1>
    <table class="tablesorter">
        <thead>
            <tr>
                <th>ID</th>
                <th>Motif</th>
                <th>Montant</th>
                <th>Statut</th>
                <th>Date</th>
                <th>&nbsp;</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1;
            /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Receptions $reception */
            foreach ($this->nonAttributedReceptions as $reception) : ?>
                <tr<?= ($i++ % 2 == 1 ? '' : ' class="odd"') ?>>
                    <td><?= $reception->getIdReception() ?></td>
                    <td><?= $reception->getMotif() ?></td>
                    <td><?= $this->ficelle->formatNumber($reception->getMontant() / 100) ?> €</td>
                    <td class="statut_operation_<?= $reception->getIdReception() ?>"><?= $this->statusOperations[$reception->getStatusBo()] ?></td>
                    <td><?= $reception->getAdded()->format('d/m/Y') ?></td>
                    <td align="center">
                        <a class="thickbox ajouter_<?= $reception->getIdReception() ?>" href="<?= $this->lurl ?>/transferts/attribution/<?= $reception->getIdReception() ?>"><img src="<?= $this->surl ?>/images/admin/edit.png" alt="Attribuer"/></a>
                        <a class="inline" href="#inline_content_<?= $reception->getIdReception() ?>"><img src="<?= $this->surl ?>/images/admin/modif.png" alt="Afficher la ligne de réception"/></a>
                        <div style="display:none;">
                            <div id="inline_content_<?= $reception->getIdReception() ?>" style="white-space: nowrap; padding:10px; background:#fff;"><?= $reception->getLigne() ?></div>
                        </div>
                    </td>
                </tr>
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
</div>
