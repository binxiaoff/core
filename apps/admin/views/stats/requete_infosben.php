<script type="text/javascript">
    $(function() {
        $(".tablesorter").tablesorter();

        <?php if ($this->nb_lignes != '') : ?>
            $(".tablesorter").tablesorterPager({container: $("#pager"), positionFixed: false, size: <?= $this->nb_lignes ?>});
        <?php endif; ?>
    });
</script>
<div id="contenu">
    <div class="row">
        <div class="col-sm-6">
            <h1>Requete infosben</h1>
        </div>
        <div class="col-sm-6">
            <a href="<?= $this->lurl ?>/stats/requete_infosben_csv" class="btn-primary pull-right">Recuperation du CSV</a>
        </div>
    </div>
    <?php if (count($this->walletsWithMovements) > 0) : ?>
        <table class="tablesorter">
            <thead>
                <tr>
                    <th>Cdos</th>
                    <th>Cbéné</th>
                    <th>CEtabl</th>
                    <th>CGuichet</th>
                    <th>RéfCompte</th>
                    <th>NatCompte</th>
                    <th>TypCompte</th>
                    <th>CDRC</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 0; ?>
                <?php foreach ($this->walletsWithMovements as $wallet) : ?>
                    <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>
                        <td>1</td>
                        <td><?= $wallet->getWireTransferPattern() ?></td>
                        <td>14378</td>
                        <td></td>
                        <td><?= $wallet->getIdClient()->getIdClient() ?></td>
                        <td>4</td>
                        <td>6</td>
                        <td>P</td>
                    </tr>
                    <?php $i++; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else : ?>
        <p>Il n'y a aucun client concerné pour le moment.</p>
    <?php endif; ?>
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
