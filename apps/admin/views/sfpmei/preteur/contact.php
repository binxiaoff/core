<div class="row">
    <div class="col-md-6">
        <table class="table table-bordered table-striped">
            <thead>
            <tr>
                <th colspan="2">Coordonnées</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <th>Prénom</th>
                <td><?= $this->clients->prenom ?></td>
            </tr>
            <tr>
                <th>Nom</th>
                <td><?= $this->clients->nom ?></td>
            </tr>
            <tr>
                <th>Email</th>
                <td><?= $this->clients->email ?></td>
            </tr>
            <tr>
                <th>Adresse fiscale</th>
                <td>
                    <?php if ($this->clients->type == 1) : ?>
                        <?= $this->clients_adresses->adresse_fiscal ?><br>
                        <?= $this->clients_adresses->cp_fiscal ?> <?= $this->clients_adresses->ville_fiscal ?>
                    <?php else : ?>
                        <?= $this->companies->adresse1 ?><br>
                        <?= $this->companies->zip ?> <?= $this->companies->city ?>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>Téléphone / Mobile</th>
                <td><?= empty($this->clients->telephone) ? '-' : $this->clients->telephone ?> / <?= empty($this->clients->mobile) ? '-' : $this->clients->mobile ?></td>
            </tr>
            </tbody>
        </table>
    </div>
    <div class="col-md-6">
        <table class="table table-bordered table-striped">
            <thead>
            <tr>
                <th colspan="2">Inscription</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <th>Date de création</th>
                <td><?= $this->dates->formatDate($this->clients->added, 'd/m/Y') ?></td>
            </tr>
            <tr>
                <th>Source</th>
                <td><?= $this->clients->source ?></td>
            </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <table class="table table-bordered table-striped">
            <thead>
            <tr>
                <th colspan="2">Mouvements</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <th>Solde disponible</th>
                <td class="text-right"><?= $this->ficelle->formatNumber($this->availableBalance) ?> €</td>
            </tr>
            <tr>
                <th>Montant 1<sup>er</sup> versement</th>
                <td class="text-right"><?= $this->ficelle->formatNumber($this->firstDepositAmount) ?> €</td>
            </tr>
            <tr>
                <th>Total dépôts</th>
                <td class="text-right"><?= $this->ficelle->formatNumber($this->totalDepositsAmount) ?> €</td>
            </tr>
            <tr>
                <th>Total retraits</th>
                <td class="text-right"><?= $this->ficelle->formatNumber($this->totalWithdrawsAmount) ?> €</td>
            </tr>
            <tr>
                <th>Total remboursements</th>
                <td class="text-right"><?= $this->ficelle->formatNumber($this->totalRepaymentsAmount) ?> €</td>
            </tr>
            <tr>
                <th>Total remboursements intérêts (brut)</th>
                <td class="text-right"><?= $this->ficelle->formatNumber($this->totalGrowthInterestsAmount) ?> €</td>
            </tr>
            <tr>
                <th>Total remboursements mois prochain</th>
                <td class="text-right"><?= $this->ficelle->formatNumber($this->totalRepaymentsNextMonthAmount) ?> €</td>
            </tr>
            </tbody>
        </table>
    </div>
    <div class="col-md-6">
        <table class="table table-bordered table-striped">
            <thead>
            <tr>
                <th colspan="2">Prêts</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <th>Montant prêté</th>
                <td class="text-right"><?= $this->ficelle->formatNumber($this->totalLoansAmount, 0) ?> €</td>
            </tr>
            <tr>
                <th>Nombre de prêts</th>
                <td class="text-right"><?= $this->ficelle->formatNumber($this->totalLoansCount, 0) ?></td>
            </tr>
            <tr>
                <th>Montant enchères en cours</th>
                <td class="text-right"><?= $this->ficelle->formatNumber($this->totalRunningBidsAmount, 0) ?> €</td>
            </tr>
            <tr>
                <th>Nombre d'enchères en cours</th>
                <td class="text-right"><?= $this->ficelle->formatNumber($this->totalRunningBidsCount, 0) ?></td>
            </tr>
            <tr>
                <th>Enchère moyenne</th>
                <td class="text-right"><?= $this->ficelle->formatNumber($this->averageBidAmount) ?> €</td>
            </tr>
            <tr>
                <th>Taux moyen pondéré</th>
                <td class="text-right"><?= $this->ficelle->formatNumber($this->averageLoanRate) ?> %</td>
            </tr>
            </tbody>
        </table>
    </div>
</div>
