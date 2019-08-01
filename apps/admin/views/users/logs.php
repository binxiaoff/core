<script>
    $(function() {
        $('.tablesorter').tablesorter();

        <?php if ($this->nb_lignes != '') : ?>
            $('.tablesorter').tablesorterPager({container: $('#pager'), positionFixed: false, size: <?= $this->nb_lignes ?>});
        <?php endif; ?>
    });
</script>
<div id="contenu">
    <h1>Historiques des connexions à la partie d'administration du site</h1>
    <?php if (count($this->loginLogs) > 0) : ?>
        <table class="tablesorter">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Date</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; ?>
                <?php foreach ($this->loginLogs as $log) : ?>
                    <?php
                        /** @var \Unilend\Entity\LoginConnectionAdmin $log */
                        if (empty($log->getIdUser()) && $this->users->get($log->getEmail(), 'email')) {
                            $log->setIdUser($this->users->id_user);
                            $log->setNomUser($this->users->firstname . ' ' . $this->users->name);
                        }
                    ?>
                    <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>
                        <td><?= $log->getNomUser() ?></td>
                        <td><?= $log->getEmail() ?></td>
                        <td><?= $log->getDateConnexion()->format('d/m/Y H:i:s') ?></td>
                        <td><?= $log->getIp() ?></td>
                    </tr>
                    <?php $i++; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if ($this->nb_lignes != '') : ?>
            <table>
                <tr>
                    <td id="pager">
                        <img src="<?= $this->url ?>/images/first.png" alt="Première" class="first">
                        <img src="<?= $this->url ?>/images/prev.png" alt="Précédente" class="prev">
                        <input type="text" class="pagedisplay">
                        <img src="<?= $this->url ?>/images/next.png" alt="Suivante" class="next">
                        <img src="<?= $this->url ?>/images/last.png" alt="Dernière" class="last">
                        <select class="pagesize">
                            <option value="<?= $this->nb_lignes ?>" selected><?= $this->nb_lignes ?></option>
                        </select>
                    </td>
                </tr>
            </table>
        <?php endif; ?>
    <?php else : ?>
        <p>Il n'y a aucun utilisateur pour le moment.</p>
    <?php endif; ?>
</div>
