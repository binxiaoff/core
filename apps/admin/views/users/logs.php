<script>
    $(function() {
        $(".tablesorter").tablesorter({headers: {6: {sorter: false}}});

        <?php if ($this->nb_lignes != '') : ?>
            $(".tablesorter").tablesorterPager({container: $("#pager"), positionFixed: false, size: <?= $this->nb_lignes ?>});
        <?php endif; ?>
    });
</script>
<div id="contenu">
    <div class="row">
        <div class="col-sm-6">
            <h1>Historiques des connexions à la partie d'administration du site</h1>
        </div>
        <div class="col-sm-6">
            <a href="<?= $this->lurl ?>/users/export_logs" class="btn-primary pull-right thickbox">Export</a>
        </div>
    </div>
    <?php if (count($this->L_Recuperation_logs) > 0) : ?>
        <table class="tablesorter">
            <thead>
                <tr>
                    <th>ID utilisateur</th>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Date</th>
                    <th>IP</th>
                    <th>Pays</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; ?>
                <?php foreach ($this->L_Recuperation_logs as $u) : ?>
                    <?php
                        if ($u['id_user'] == 0 && $this->users->get($u['email'], 'email')) {
                            $u['id_user']  = "<i>" . $this->users->id_user . "</i>";
                            $u['nom_user'] = "<i>" . $this->users->firstname . ' ' . $this->users->name . "</i>";
                        }
                    ?>
                    <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>
                        <td><?= $u['id_user'] ?></td>
                        <td><?= $u['nom_user'] ?></td>
                        <td><?= $u['email'] ?></td>
                        <td><?= $this->dates->formatDate($u['date_connexion'], 'd/m/Y H:i:s') ?></td>
                        <td><?= $u['ip'] ?></td>
                        <td><?= $u['pays'] ?></td>
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
        <p>Il n'y a aucun utilisateur pour le moment.</p>
    <?php endif; ?>
</div>
