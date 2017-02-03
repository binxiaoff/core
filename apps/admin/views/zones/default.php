<div id="contenu">
    <h1>Droits d'accès</h1>
    <?php if (count($this->users) > 0) : ?>
        <table class="tablesorter">
            <thead>
                <tr>
                    <th style="width: 130px">Nom</th>
                    <th style="width: 130px">Prénom</th>
                    <?php foreach ($this->zones as $zone) : ?>
                        <th style="width: <?= ceil(900 / count($this->zones)) ?>px"><?= $zone['name'] ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; ?>
                <?php foreach ($this->users as $user) : ?>
                    <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>
                        <td><?= $user['name'] ?></td>
                        <td><?= $user['firstname'] ?></td>
                        <?php foreach ($this->zones as $zone) : ?>
                            <?php $this->userZone->get($user['id_user'], 'id_zone = "' . $zone['id_zone'] . '" AND id_user'); ?>
                            <td align="center">
                                <img onclick="activeUserZone(<?= $user['id_user'] ?>,<?= $zone['id_zone'] ?>,'zone_<?= $user['id_user'] ?>_<?= $zone['id_zone'] ?>');" src="<?= $this->surl ?>/images/admin/check_<?= ($this->userZone->id != '' ? 'on' : 'off') ?>.png" id="zone_<?= $user['id_user'] ?>_<?= $zone['id_zone'] ?>" style="cursor: pointer;"/>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php $i++; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else : ?>
        <p>Il n'y a aucun utilisateur pour le moment.</p>
    <?php endif; ?>
</div>
