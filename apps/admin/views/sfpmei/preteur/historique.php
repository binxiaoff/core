<div class="row">
    <div class="col-md-12">
        <h3>Statut</h3>
        <?php if (false === empty($this->statusHistory)) : ?>
            <table class="table table-hover table-striped">
                <thead>
                <tr>
                    <th>Action</th>
                    <th>Date</th>
                    <th>Utilisateur</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($this->statusHistory as $historyEntry) {
                    $this->clients_status->get($historyEntry['id_client_status'], 'id_client_status');
                    $this->users->get($historyEntry['id_user'], 'id_user');

                    switch ($this->clients_status->status) {
                        case \clients_status::TO_BE_CHECKED: ?>
                            <tr>
                                <td>
                                    <?php if (empty($historyEntry['content'])) : ?>
                                        Création de compte
                                    <?php else: ?>
                                        Compte modifié
                                        <?= $historyEntry['content'] ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('d/m/Y H:i:s', strtotime($historyEntry['added'])) ?></td>
                                <td></td>
                            </tr>
                            <?php break;
                        case \clients_status::COMPLETENESS: ?>
                            <tr>
                                <td>
                                    Complétude<br>
                                    <?= $historyEntry['content'] ?>
                                </td>
                                <td><?= date('d/m/Y H:i:s', strtotime($historyEntry['added'])) ?></td>
                                <td class="text-nowrap"><?= $this->users->firstname ?> <?= $this->users->name ?></td>
                            </tr>
                            <?php break;
                        case \clients_status::COMPLETENESS_REMINDER: ?>
                            <tr>
                                <td>
                                    Complétude relance<br>
                                    <?= $historyEntry['content'] ?>
                                </td>
                                <td><?= date('d/m/Y H:i:s', strtotime($historyEntry['added'])) ?></td>
                                <td></td>
                            </tr>
                            <?php break;
                        case \clients_status::COMPLETENESS_REPLY: ?>
                            <tr>
                                <td>
                                    Complétude réponse<br>
                                    <?= $historyEntry['content'] ?>
                                </td>
                                <td><?= date('d/m/Y H:i:s', strtotime($historyEntry['added'])) ?></td>
                                <td></td>
                            </tr>
                            <?php break;
                        case \clients_status::MODIFICATION: ?>
                            <tr>
                                <td>
                                    Compte modifié<br>
                                    <?= $historyEntry['content'] ?>
                                </td>
                                <td><?= date('d/m/Y H:i:s', strtotime($historyEntry['added'])) ?></td>
                                <td></td>
                            </tr>
                            <?php break;
                        case \clients_status::VALIDATED: ?>
                            <tr>
                                <td>
                                    <?php if (empty($historyEntry['content'])) : ?>
                                        Compte validé
                                    <?php else : ?>
                                        <?= $historyEntry['content'] ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('d/m/Y H:i:s', strtotime($historyEntry['added'])) ?></td>
                                <td class="text-nowrap"><?= (-1 == $historyEntry['id_user']) ? 'Validation automatique Greenpoint' : $this->users->firstname . ' ' . $this->users->name ?></td>
                            </tr>
                            <?php break;
                        case \clients_status::CLOSED_LENDER_REQUEST : ?>
                            <tr>
                                <td>Compte clôturé à la demande du prêteur</td>
                                <td><?= date('d/m/Y H:i:s', strtotime($historyEntry['added'])) ?></td>
                                <td class="text-nowrap"><?= $this->users->firstname ?> <?= $this->users->name ?></td>
                            </tr>
                            <?php break;
                        case \clients_status::CLOSED_BY_UNILEND : ?>
                            <tr>
                                <td>
                                    Compte clôturé par Unilend<br>
                                    <?= $historyEntry['content'] ?>
                                </td>
                                <td><?= date('d/m/Y H:i:s', strtotime($historyEntry['added'])) ?></td>
                                <td class="text-nowrap"><?= $this->users->firstname ?> <?= $this->users->name ?></td>
                            </tr>
                            <?php break;
                        case \clients_status::CLOSED_DEFINITELY: ?>
                            <tr>
                                <td>
                                    Compte définitvement fermé<br>
                                    <?= $historyEntry['content'] ?>
                                </td>
                                <td><?= date('d/m/Y H:i:s', strtotime($historyEntry['added'])) ?></td>
                                <td class="text-nowrap"><?= $this->users->firstname ?> <?= $this->users->name ?></td>
                            </tr>
                            <?php break;
                    }
                }
                ?>
                </tbody>
            </table>
        <?php else :?>
            <strong>Aucun historique disponible</strong>
        <?php endif; ?>
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <h3>Adresse fiscal</h3>
        <?php if (false === empty($this->taxationCountryHistory)) : ?>
            <table class="table table-hover table-striped">
                <?php if (array_key_exists('error', $this->taxationCountryHistory)) : ?>
                    <tbody>
                    <tr>
                        <td><?= $this->taxationCountryHistory['error'] ?></td>
                    </tr>
                    </tbody>
                <?php else : ?>
                    <thead>
                    <tr>
                        <th>Action</th>
                        <th>Date</th>
                        <th>Utilisateur</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($this->taxationCountryHistory as $history) : ?>
                        <tr>
                            <td>Nouveau pays fiscal : <strong><?= $history['country_name'] ?></strong></td>
                            <td><?= date('d/m/Y H:i:s', strtotime($history['added'])) ?></td>
                            <td><?= $history['user_firstname'] ?> <?= $history['user_name'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                <?php endif; ?>
            </table>
        <?php else :?>
            <strong>Aucun historique disponible</strong>
        <?php endif; ?>
    </div>
    <div class="col-md-6">
        <h3>Exonération fiscale</h3>
        <?php if (false === empty($this->taxExemptionHistory)) : ?>
            <table class="table table-hover table-striped">
                <thead>
                <tr>
                    <th>Action</th>
                    <th>Date</th>
                    <th>Utilisateur</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($this->taxExemptionHistory as $actions) : ?>
                    <?php foreach ($actions['modifications'] as $action) : ?>
                        <tr>
                            <td>
                                Dispense de prélèvement fiscal <strong><?= $action['year'] ?></strong>
                                <?php if ('adding' === $action['action']) : ?>
                                    ajoutée
                                <?php elseif ('deletion' === $action['action']) : ?>
                                    supprimée
                                <?php endif; ?>
                            </td>
                            <td><?= \DateTime::createFromFormat('Y-m-d H:i:s', $actions['date'])->format('d/m/Y H:i:s') ?></td>
                            <td><?= $actions['user'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else :?>
            <strong>Aucun historique disponible</strong>
        <?php endif; ?>
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <h3>CGV</h3>
        <?php if (false === empty($this->termsOfSalesAcceptation)) : ?>
            <table class="table table-hover table-striped">
                <thead>
                <tr>
                    <th>Version</th>
                    <th>Date document</th>
                    <th>Date acceptation</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($this->termsOfSalesAcceptation as $termsOfSales) : ?>
                    <?php /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\AcceptationsLegalDocs $termsOfSales */ ?>
                    <?php $tree = $this->treeRepository->findOneBy(['idTree' => $termsOfSales->getIdLegalDoc(), 'idLangue' => $this->language]); ?>
                    <tr>
                        <td><?= $termsOfSales->getIdLegalDoc() ?></td>
                        <td><?= $tree->getAdded()->format('d/m/Y') ?></td>
                        <td><?= $termsOfSales->getAdded()->format('d/m/Y H:i') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else :?>
            <strong>Aucun historique disponible</strong>
        <?php endif; ?>
    </div>
</div>
