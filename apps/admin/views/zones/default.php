<script type="text/javascript">
    $(function() {
        <?php if ($this->nb_lignes != '') : ?>
            $(".tablesorter").tablesorterPager({container: $("#pager"), positionFixed: false, size: <?= $this->nb_lignes ?>});
        <?php endif; ?>

        <?php if (isset($_SESSION['freeow'])) : ?>
            var title = "<?= $_SESSION['freeow']['title'] ?>",
                message = "<?= $_SESSION['freeow']['message'] ?>",
                opts = {},
                container;

            opts.classes = ['smokey'];
            $('#freeow-tr').freeow(title, message, opts);
            <?php unset($_SESSION['freeow']); ?>
        <?php endif; ?>
    });
</script>
<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
    <h1>Gestion des droits des utilisateurs</h1>
    <div class="btnDroite"><a href="<?= $this->lurl ?>/zones/add" class="btn_link thickbox">Ajouter une zone</a></div>
    <?php if (count($this->lUsers) > 0) : ?>
        <table class="tablesorter">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <?php $i = 1; ?>
                    <?php foreach ($this->lZones as $z) : ?>
                        <?php if (false === in_array($z['id_zone'], [5, 6, 7, 8])) : ?>
                            <th>
                                <?= $z['name'] ?>
                                <?php if ($z['status'] != 2) : ?>
                                    <a href="<?= $this->lurl ?>/zones/edit/<?= $z['id_zone'] ?>" class="thickbox"><img src="<?= $this->surl ?>/images/admin/edit.png" alt="Modifier <?= $z['name'] ?>"/></a>
                                    <a href="<?= $this->lurl ?>/zones/delete/<?= $z['id_zone'] ?>" title="Supprimer <?= $z['name'] ?>" onclick="return confirm('Etes vous sur de vouloir supprimer <?= $z['name'] ?> ?')"><img src="<?= $this->surl ?>/images/admin/delete.png" alt="Supprimer <?= $z['name'] ?>"/></a>
                                <?php endif; ?>
                            </th>
                        <?php endif; ?>
                        <?php $i++; ?>
                    <?php endforeach; ?>
                </tr>
           </thead>
            <tbody>
                <?php $i = 1; ?>
                <?php foreach ($this->lUsers as $u) : ?>
                    <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>
                        <td><?= $u['name'] ?></td>
                        <td><?= $u['firstname'] ?></td>
                        <?php $y = 1; ?>
                        <?php foreach ($this->lZones as $z) : ?>
                            <?php if (false === in_array($z['id_zone'], [5, 6, 7, 8])) : ?>
                                <?php $this->users_zones->get($u['id_user'], 'id_zone = "' . $z['id_zone'] . '" AND id_user'); ?>
                                <td align="center">
                                    <img onclick="activeUserZone(<?= $u['id_user'] ?>,<?= $z['id_zone'] ?>,'zone_<?= $u['id_user'] ?>_<?= $z['id_zone'] ?>');" src="<?= $this->surl ?>/images/admin/check_<?= ($this->users_zones->id != '' ? 'on' : 'off') ?>.png" id="zone_<?= $u['id_user'] ?>_<?= $z['id_zone'] ?>" style="cursor: pointer;"/>
                                </td>
                            <?php endif; ?>
                            <?php $y++; ?>
                        <?php endforeach; ?>
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
                        <input type="text" class="pagedisplay" />
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
        <p>Il n'y a aucun utilisateur pour le moment.</p>
    <?php endif; ?>
</div>
