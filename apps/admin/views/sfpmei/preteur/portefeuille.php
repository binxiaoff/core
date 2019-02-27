<?php use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus; ?>
<div class="row">
    <div class="col-md-12">
        <h3>Portefeuille</h3>
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <table class="table table-bordered table-striped">
            <tbody>
            <tr>
                <th>TRI</th>
                <td>
                    <?php if (null === $this->lenderIRR) : ?>
                        Ce prêteur est trop récent. Son TRI n'a pas encore été calculé.
                    <?php else : ?>
                        <?= \Unilend\Bundle\CoreBusinessBundle\Entity\LenderStatistic::STAT_VALID_OK === $this->lenderIRR->getStatus() ? $this->ficelle->formatNumber($this->lenderIRR->getValue()) . ' %'  : 'TRI non valide'?>
                        <?= (is_null($this->lenderIRR->getAdded())) ? '' : '(calculé le ' . $this->lenderIRR->getAdded()->format('d/m/Y') . ')' ?>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>Projets</th>
                <td><?= $this->ficelle->formatNumber($this->projectsCount, 0) ?></td>
            </tr>
            <tr>
                <th>Projets à problème</th>
                <td><?= $this->ficelle->formatNumber($this->problematicProjectsCount, 0) ?></td>
            </tr>
            <tr>
                <th>Projets publiés depuis l'inscription</th>
                <td><?= $this->ficelle->formatNumber($this->publishedProjectsCount, 0) ?></td>
            </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <h3>Enchères</h3>
        <h4>Enchères en cours</h4>
    </div>
    <div class="col-md-6">
        <a href="<?= $this->lurl ?>/sfpmei/preteur/<?= $this->clients->id_client ?>/bids_csv" class="btn-primary pull-right">
            <img src="<?= $this->surl ?>/images/admin/xls.png" alt="Export CSV"> Exporter toutes les enchères
        </a>
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <?php if (count($this->runningBids) > 0) :?>
            <table class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th>Projet</th>
                    <th>Montant</th>
                    <th>Taux</th>
                    <th>Durée</th>
                    <th>Date</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($this->runningBids as $bid) : ?>
                    <?php $this->projects->get($bid['id_project']); ?>
                    <tr>
                        <td><a href="<?= $this->lurl ?>/sfpmei/projet/<?= $this->projects->id_project ?>"><?= $this->projects->title ?></a></td>
                        <td class="text-nowrap text-right"><?= $this->ficelle->formatNumber($bid['amount'] / 100, 0) ?> €</td>
                        <td class="text-nowrap"><?= $this->ficelle->formatNumber($bid['rate'], 1) ?> %</td>
                        <td><?= $this->projects->period ?> mois</td>
                        <td><?= date('d/m/Y H:i', strtotime($bid['added'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p class="bg-info">Aucune enchère en cours</p>
        <?php endif; ?>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <h3>Prêts</h3>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <?php if (false === empty($this->lenderLoans)) : ?>
            <table class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th style="width: 300px">Nom</th>
                    <th>Statut</th>
                    <th>Durée</th>
                    <th>Montant prêté</th>
                    <th>Taux</th>
                    <th>Documents</th>
                    <th>Début</th>
                    <th>Fin</th>
                    <?php if ($this->hasTransferredLoans) : ?>
                        <th>Ancien proprietaire</th>
                    <?php endif; ?>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($this->lenderLoans as $loan) : ?>
                    <?php $rowspan = ($loan['nb_loan'] > 1) ? ' rowspan="' . ($loan['nb_loan'] + 1) . '"' : ''; ?>
                    <tr>
                        <td<?= $rowspan ?>><strong><a href="<?= $this->lurl ?>/sfpmei/projet/<?= $loan['id_project'] ?>"><?= $loan['title'] ?></a></strong></td>
                        <td<?= $rowspan ?>><?= $loan['project_status_label'] ?></td>
                        <td<?= $rowspan ?> class="text-nowrap"><?= $loan['period'] ?> mois</td>
                        <td class="text-nowrap text-right"><?= $this->ficelle->formatNumber($loan['amount'], 0) ?> €</td>
                        <td class="text-nowrap"><?= $this->ficelle->formatNumber($loan['rate'], 1) ?> %</td>
                        <?php if ($loan['nb_loan'] == 1) : ?>
                            <td>
                                <?php if ($loan['project_status'] >= ProjectsStatus::REMBOURSEMENT) : ?>
                                    <a href="<?= $this->lurl ?>/protected/contrat/<?= $this->clients->hash ?>/<?= $loan['id_loan_if_one_loan'] ?>">
                                        <?php $this->contract->get($loan['id_type_contract']); ?>
                                        <?= $this->translator->trans('contract-type-label_' . $this->contract->label) ?>
                                    </a>
                                <?php endif; ?>
                                <?php if (in_array($loan['id_project'], $this->projectsInDebt)) : ?>
                                    <br/>
                                    <a href="<?= $this->lurl ?>/protected/declaration_de_creances/<?= $this->clients->hash ?>/<?= $loan['id_loan_if_one_loan'] ?>">Déclaration de créances</a>
                                <?php endif; ?>
                            </td>
                        <?php else : ?>
                            <td>&nbsp;</td>
                        <?php endif; ?>
                        <td<?= $rowspan ?>><?= $this->formatDate($loan['debut'], 'd/m/Y') ?></td>
                        <?php if (in_array($loan['project_status'], [ProjectsStatus::REMBOURSEMENT_ANTICIPE, ProjectsStatus::REMBOURSE])) : ?>
                            <td<?= $rowspan ?>>Remboursé le <?= $this->formatDate($loan['final_repayment_date'], 'd/m/Y') ?></td>
                        <?php else : ?>
                            <td<?= $rowspan ?>><?= $this->formatDate($loan['fin'], 'd/m/Y') ?></td>
                        <?php endif; ?>
                        <?php if ($this->hasTransferredLoans) : ?>
                            <?php if ($loan['nb_loan'] == 1) : ?>
                                <td>
                                    <?php if ($this->loans->get($loan['id_loan_if_one_loan']) && false === empty($this->loans->id_transfer)) :
                                        /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Clients $formerOwner */
                                        $formerOwner = $this->get('unilend.service.loan_manager')->getFormerOwner($this->loans); ?>
                                        <a href="<?= $this->lurl ?>/preteur/<?= $formerOwner->getIdClient() ?>"><?= $formerOwner->getIdClient() ?></a>
                                    <?php endif; ?>
                                </td>
                            <?php else : ?>
                                <td>&nbsp;</td>
                            <?php endif; ?>
                        <?php endif; ?>
                    </tr>
                    <?php if ($loan['nb_loan'] > 1) : ?>
                        <?php foreach ($this->loans->select('id_wallet = ' . $this->wallet->getId() . ' AND id_project = ' . $loan['id_project']) as $subLoan) : ?>
                            <tr class="sub_loan">
                                <td class="text-nowrap text-right"><?= $this->ficelle->formatNumber($subLoan['amount'] / 100, 0) ?> €</td>
                                <td class="text-nowrap"><?= $this->ficelle->formatNumber($subLoan['rate'], 1) ?> %</td>
                                <td>
                                    <?php if ($loan['project_status'] >= ProjectsStatus::REMBOURSEMENT) : ?>
                                        <a href="<?= $this->lurl ?>/protected/contrat/<?= $this->clients->hash ?>/<?= $subLoan['id_loan'] ?>">
                                            <?php $this->contract->get($subLoan['id_type_contract']); ?>
                                            <?= $this->translator->trans('contract-type-label_' . $this->contract->label) ?>
                                        </a>
                                    <?php endif; ?>
                                    <?php if (in_array($loan['id_project'], $this->projectsInDebt)) : ?>
                                        <br>
                                        <a href="<?= $this->lurl ?>/protected/declaration_de_creances/<?= $this->clients->hash ?>/<?= $subLoan['id_loan'] ?>">Déclaration de créances</a>
                                    <?php endif; ?>
                                </td>
                                <?php if ($this->hasTransferredLoans) : ?>
                                    <td>
                                        <?php if (false === empty($subLoan['id_transfer'])) :
                                            $this->loans->get($subLoan['id_loan']);
                                            /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Clients $formerOwner */
                                            $formerOwner = $this->get('unilend.service.loan_manager')->getFormerOwner($this->loans); ?>
                                            <a href="<?= $this->lurl ?>/preteur/<?= $formerOwner->getIdClient() ?>"><?= $formerOwner->getIdClient() ?></a>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <strong>Aucun prêt</strong>
        <?php endif; ?>
    </div>
</div>
