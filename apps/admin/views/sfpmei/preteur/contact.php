<div class="row">
    <div class="col-md-6">
        <table class="table table-bordered table-striped">
            <thead>
            <tr>
                <th colspan="2">Identité</th>
            </tr>
            </thead>
            <tbody>
                <?php if ($this->isPhysicalPerson) : ?>
                    <?php $this->fireView('preteur/contact_identite_physique'); ?>
                <?php else : ?>
                    <?php $this->fireView('preteur/contact_identite_morale'); ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="col-md-6">
        <table class="table table-bordered table-striped">
            <tbody>
            <tr>
                <th>Date d'inscription</th>
                <td><?= $this->dates->formatDate($this->clients->added, 'd/m/Y') ?></td>
            </tr>
            <tr>
                <th>Source</th>
                <td><?= empty($this->clients->source) ? '-' : $this->clients->source ?></td>
            </tr>
            <tr>
                <th>Évaluation CIP</th>
                <td>
                    <?php if ($this->cipEnabled) : ?>
                        Oui (<a href="<?= $this->furl ?>/pdf/conseil-cip/<?= $this->clients->hash ?>" target="_blank"> Télécharger le PDF des conseils</a>)
                    <?php else : ?>
                        Non
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>Exonération fiscale</th>
                <td><?= empty($this->exemptionYears) ? '-' : implode('<br>', $this->exemptionYears) ?>
                </td>
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
                <th colspan="2">Coordonnées</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <th>Email</th>
                <td><?= $this->clients->email ?></td>
            </tr>
            <tr>
                <th>Téléphone</th>
                <td><?= empty($this->clients->telephone) ? '-' : $this->clients->telephone ?></td>
            </tr>
            <tr>
                <th>Mobile</th>
                <td><?= empty($this->clients->mobile) ? '-' : $this->clients->mobile ?></td>
            </tr>
            <tr>
                <th>Adresse fiscale</th>
                <td>
                    <?= $this->fiscalAddress['address'] ?><br>
                    <?= $this->fiscalAddress['postCode'] ?> <?= $this->fiscalAddress['city'] ?><br>
                    <?= $this->fiscalAddress['country'] ?>
                </td>
            </tr>
            <tr>
                <th>Adresse de correspondance</th>
                <td>
                    <?= $this->postalAddress['address'] ?><br>
                    <?= $this->postalAddress['postCode'] ?> <?= $this->postalAddress['city'] ?><br>
                    <?= $this->postalAddress['country'] ?>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
    <div class="col-md-6">
        <table class="table table-bordered table-striped">
            <thead>
            <tr>
                <th colspan="2">Informations bancaires</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <th>IBAN</th>
                <td><?= $this->currentBankAccount->getIban() ?></td>
            </tr>
            <tr>
                <th>BIC</th>
                <td><?= $this->currentBankAccount->getBic() ?></td>
            </tr>
            <?php if (isset($this->fundsOriginList)) : ?>
                <tr>
                    <th>Origine des fonds</th>
                    <td>
                        <?= isset($this->fundsOriginList[$this->clients->funds_origin - 1]) ? $this->fundsOriginList[$this->clients->funds_origin - 1] : '' ?>
                        <?= empty($this->clients->funds_origin_detail) ? '' : '<br>' . $this->clients->funds_origin_detail ?>
                    </td>
                </tr>
            <?php endif; ?>
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
