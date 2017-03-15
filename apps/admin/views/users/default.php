<script type="text/javascript">
    $(function() {
        $(".tablesorter").tablesorter({headers: {6: {sorter: false}}});

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
    <h1>Gestion des utilisateurs</h1>
    <div class="btnDroite">
        <a href="<?= $this->lurl ?>/users/add" class="btn_link thickbox">Ajouter un utilisateur</a>
    </div>
    <?php foreach ($this->users as $userStatus => $users) : ?>
        <?php if ($userStatus == \Unilend\Bundle\CoreBusinessBundle\Entity\Users::STATUS_ONLINE) : ?>
            <h2>Utilisateurs en ligne</h2>
        <?php else : ?>
            <h2>Utilisateurs hors ligne</h2>
        <?php endif; ?>
        <?php if (count($users) > 0) : ?>
            <table class="tablesorter">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Prénom</th>
                        <th>E-mail</th>
                        <th>Droits</th>
                        <th>Ajouter</th>
                        <th>Mise à jour</th>
                        <th>Dernière connexion</th>
                        <th>&nbsp;</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; ?>
                    <?php foreach ($users as $user) : ?>
                        <?php
                            $users_types = $this->loadData('users_types');
                            $users_types->get($user['id_user_type'], 'id_user_type');
                        ?>
                        <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>
                            <td><?= $user['name'] ?></td>
                            <td><?= $user['firstname'] ?></td>
                            <td><?= $user['email'] ?></td>
                            <td><?= $users_types->label ?></td>
                            <td><?= $this->dates->formatDate($user['added'], 'd/m/Y') ?></td>
                            <td><?= $this->dates->formatDate($user['updated'], 'd/m/Y') ?></td>
                            <td><?= $this->dates->formatDate($user['lastlogin'], 'd/m/Y') ?></td>
                            <td align="center">
                                <?php if ($user['status'] != 2) : ?>
                                    <a href="<?= $this->lurl ?>/users/status/<?= $user['id_user'] ?>/<?= $user['status'] ?>" title="<?= ($user['status'] == 1 ? 'Passer hors ligne' : 'Passer en ligne') ?>">
                                        <img src="<?= $this->surl ?>/images/admin/<?= ($user['status'] == 1 ? 'offline' : 'online') ?>.png" alt="<?= ($user['status'] == 1 ? 'Passer hors ligne' : 'Passer en ligne') ?>"/>
                                    </a>
                                    <a href="<?= $this->lurl ?>/users/edit/<?= $user['id_user'] ?>" class="thickbox">
                                        <img src="<?= $this->surl ?>/images/admin/edit.png" alt="Modifier <?= $user['firstname'] ?> <?= $user['name'] ?>"/>
                                    </a>
                                    <a href="<?= $this->lurl ?>/users/delete/<?= $user['id_user'] ?>" title="Supprimer <?= $user['firstname'] ?> <?= $user['name'] ?>" onclick="return confirm('Etes vous sur de vouloir supprimer <?= $user['firstname'] ?> <?= $user['name'] ?> ?')">
                                        <img src="<?= $this->surl ?>/images/admin/delete.png" alt="Supprimer <?= $user['firstname'] ?> <?= $user['name'] ?>"/>
                                    </a>
                                <?php else : ?>
                                    <a href="<?= $this->lurl ?>/users/edit/<?= $user['id_user'] ?>" class="thickbox">
                                        <img src="<?= $this->surl ?>/images/admin/edit.png" alt="Modifier <?= $user['firstname'] ?> <?= $user['name'] ?>"/>
                                    </a>
                                <?php endif; ?>
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
            <?php else : ?>
                <p>Il n'y a aucun utilisateur pour le moment.</p>
            <?php endif; ?>
        <?php endif; ?>
        <br><br>
    <?php endforeach; ?>
</div>
