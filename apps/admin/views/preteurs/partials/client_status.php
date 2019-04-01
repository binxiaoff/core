<h2>Historique des statuts</h2>
<table class="table">
    <thead>
    <tr>
        <th>Statut</th>
        <th>Date</th>
        <th>Utilisateur</th>
    </tr>
    </thead>
    <tbody>
        <?php /** @var \Unilend\Entity\ClientsStatusHistory $historyEntry */ ?>
        <?php foreach ($this->statusHistory as $historyEntry) : ?>
            <tr>
                <td><?= $historyEntry->getIdStatus()->getLabel() ?></td>
                <td><?= $historyEntry->getAdded()->format('d/m/Y H\hi') ?></td>
                <td>
                    <?= $historyEntry->getIdUser()->getFirstname() ?> <?= $historyEntry->getIdUser()->getName() ?>
                    <?php if (false === empty($historyEntry->getContent())) : ?>
                        <img src="<?= $this->surl ?>/images/admin/info.png" alt="Information" title="<?= htmlentities($historyEntry->getContent()) ?>" class="tooltip pull-right">
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
