<?php

use Doctrine\ORM\EntityManager;
use Unilend\Entity\UsersTypes;

?>
<script type="text/javascript">
    $(function() {
        $(".tablesorter").tablesorter({headers: {7: {sorter: false}}});
    });
</script>
<div id="contenu">
    <div class="row">
        <div class="col-md-6">
            <h1>Utilisateurs</h1>
        </div>
        <div class="col-md-6">
            <a href="<?= $this->url ?>/users/add" class="btn-primary pull-right thickbox">Ajouter un utilisateur</a>
        </div>
    </div>
    <?php
    /** @var EntityManager $entityManager */
    $entityManager = $this->get('doctrine.orm.entity_manager');
    ?>
    <?php foreach ($this->users as $userStatus => $users) : ?>
        <?php if ($userStatus == \Unilend\Entity\Users::STATUS_ONLINE) : ?>
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
                        <th>Ajout</th>
                        <th>Mise à jour</th>
                        <th>Dernière connexion</th>
                        <th>&nbsp;</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; ?>
                    <?php foreach ($users as $user) : ?>
                        <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>
                            <td><?= $user['name'] ?></td>
                            <td><?= $user['firstname'] ?></td>
                            <td><?= $user['email'] ?></td>
                            <td><?= $entityManager->getRepository(UsersTypes::class)->find($user['id_user_type'])->getLabel() ?></td>
                            <td><?= $this->formatDate($user['added'], 'd/m/Y') ?></td>
                            <td><?= $this->formatDate($user['updated'], 'd/m/Y') ?></td>
                            <td><?= $this->formatDate($user['lastlogin'], 'd/m/Y') ?></td>
                            <td align="center">
                                <a href="<?= $this->url ?>/users/status/<?= $user['id_user'] ?>/<?= $user['status'] ?>" title="<?= ($user['status'] == \Unilend\Entity\Users::STATUS_ONLINE ? 'Passer hors ligne' : 'Passer en ligne') ?>">
                                    <img src="<?= $this->surl ?>/images/admin/<?= ($user['status'] == \Unilend\Entity\Users::STATUS_ONLINE ? 'offline' : 'online') ?>.png" alt="<?= ($user['status'] == \Unilend\Entity\Users::STATUS_ONLINE ? 'Passer hors ligne' : 'Passer en ligne') ?>">
                                </a>
                                <a href="<?= $this->url ?>/users/edit/<?= $user['id_user'] ?>" class="thickbox">
                                    <img src="<?= $this->surl ?>/images/admin/edit.png" alt="Modifier <?= $user['firstname'] ?> <?= $user['name'] ?>">
                                </a>
                            </td>
                        </tr>
                        <?php $i++; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p>Aucun utilisateur</p>
        <?php endif; ?>
    <?php endforeach; ?>
</div>
