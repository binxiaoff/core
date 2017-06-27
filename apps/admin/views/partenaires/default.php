<script type="text/javascript">
    $(function() {
        $(".tablesorter").tablesorter({headers:{7:{sorter: false}}});

        <?php if ($this->nb_lignes != '') : ?>
            $(".tablesorter").tablesorterPager({container: $("#pager"),positionFixed: false,size: <?= $this->nb_lignes ?>});
        <?php endif; ?>
    });
</script>
<div id="contenu">
    <div class="row">
        <div class="col-md-6">
            <h1>Liste des campagnes</h1>
        </div>
        <div class="col-md-6">
            <a href="<?= $this->lurl ?>/partenaires/add" class="btn-primary pull-right thickbox">Ajouter une campagne</a>
        </div>
    </div>
    <?php if (count($this->lPartenaires) > 0) : ?>
        <table class="tablesorter">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Lien</th>
                    <th>Type</th>
                    <th>Nb de clics</th>
                    <th>&nbsp;</th>
                </tr>
               </thead>
            <tbody>
            <?php $i = 1; ?>
            <?php foreach ($this->lPartenaires as $p) : ?>
                <?php
                    $this->partenaires_types->get($p['id_type'],'id_type');
                    $nbclic = $this->partenaires->nbClicTotal($p['id_partenaire']);
                ?>
                <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>
                    <td><?= $p['nom'] ?></td>
                    <td>/p/<?= $p['hash'] ?>/</td>
                    <td><?= $this->partenaires_types->nom ?></td>
                    <td><?= $nbclic ?></td>
                    <td align="center">
                        <a href="<?= $this->lurl ?>/partenaires/status/<?= $p['id_partenaire'] ?>/<?= $p['status'] ?>" title="<?= ($p['status'] == 1 ? 'Passer hors ligne' : 'Passer en ligne') ?>">
                            <img src="<?= $this->surl ?>/images/admin/<?= ($p['status'] == 1 ? 'offline' : 'online') ?>.png" alt="<?= ($p['status'] == 1 ? 'Passer hors ligne' : 'Passer en ligne') ?>"/>
                        </a>
                        <a href="<?= $this->lurl ?>/partenaires/edit/<?= $p['id_partenaire'] ?>" class="thickbox">
                            <img src="<?= $this->surl ?>/images/admin/edit.png" alt="Modifier <?= $p['nom'] ?>"/>
                        </a>
                        <form method="post" id="formQuery<?= $p['id_partenaire'] ?>" action="<?= $this->lurl ?>/queries/excel/1" target="_blank" style="margin:0; padding:0; border:0; display:inline;">
                            <input type="hidden" name="param_ID_Partenaire" value="<?= $p['id_partenaire'] ?>"/>
                        </form>
                        <a onclick="document.getElementById('formQuery<?= $p['id_partenaire'] ?>').submit();return false;" title="Exporter les commandes de la campagne">
                            <img src="<?= $this->surl ?>/images/admin/xls.png" alt="Exporter les commandes de la campagne"/>
                        </a>
                        <a href="<?= $this->lurl ?>/partenaires/delete/<?= $p['id_partenaire'] ?>" title="Supprimer <?= $p['nom'] ?>" onclick="return confirm('Etes vous sur de vouloir supprimer <?= $p['nom'] ?> ?')">
                            <img src="<?= $this->surl ?>/images/admin/delete.png" alt="Supprimer <?= $p['nom'] ?>"/>
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
        <p>Il n'y a aucune campagne pour le moment.</p>
    <?php endif; ?>
</div>
