<div class="pageBreakBefore">
    <h3><?= $this->sectionTitle ?></h3>
    <div class="dates-table">
        <table width="100%" cellspacing="0" cellpadding="0" class="table-3">
            <tr>
                <th>NOM ou<br/>Raison sociale</th>
                <th>PRENOM<br/>ou SIREN</th>
                <th>ADRESSE</th>
                <th>CODE<br/> POSTAL</th>
                <th>VILLE</th>
                <th>MONTANT</th>
                <th>TAUX<br/> D'INTERET</th>
            </tr>
            <?php $printedLines = 0; ?>
            <?php foreach ($this->lLenders as $l) : ?>
            <?php $wallet = $this->walletRepository->find($l['id_lender']); ?>
            <?php $this->clients_adresses->get($wallet->getIdClient()->getIdClient(), 'id_client'); ?>
            <?php $nom    = $wallet->getIdClient()->getNom(); ?>
            <?php $prenom = $wallet->getIdClient()->getPrenom(); ?>

            <?php if ($wallet->getIdClient()->getType() == \Unilend\Bundle\CoreBusinessBundle\Entity\Clients::TYPE_LEGAL_ENTITY) : ?>
                <?php $this->companies->get($wallet->getIdClient()->getIdClient(), 'id_client_owner'); ?>
                <?php $nom    = $this->companies->name; ?>
                <?php $prenom = $this->companies->siren; ?>
            <?php endif; ?>
            <tr>
                <td height="35" style="width: 15%; border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;"><?= $nom ?></td>
                <td height="35" style="width: 10%; border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;"><?= $prenom ?></td>
                <td height="35" style="width: 27%; border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;"><?= $this->clients_adresses->adresse1 ?></td>
                <td height="35" style="width: 7%; border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;"><?= $this->clients_adresses->cp ?></td>
                <td height="35" style="width: 23%; border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;"><?= $this->clients_adresses->ville ?></td>
                <td height="35" style="width: 10%;border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;" class="nowrap"><?= $this->ficelle->formatNumber($l['amount'] / 100, 0) ?>&nbsp;€</td>
                <td height="35" style="width: 8%; border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;" class="nowrap"><?= $this->ficelle->formatNumber($l['rate'], 1) ?>&nbsp;%</td>
            </tr>
            <?php $printedLines++; ?>
            <?php if (0 === $printedLines % $this->numberOfRowsPerPage || $printedLines === count($this->lLenders)) : ?>
        </table>
    </div> <!--dates-table-->
</div> <!--pageBreakBefore-->
<?php if ($printedLines < count($this->lLenders)): ?>
<div class="pageBreakBefore">
    <div class="dates-table">
        <table width="100%" cellspacing="0" cellpadding="0" class="table-3">
            <tr>
                <th>NOM ou<br/>Raison sociale</th>
                <th>PRENOM<br/>ou SIREN</th>
                <th>ADRESSE</th>
                <th>CODE<br/> POSTAL</th>
                <th>VILLE</th>
                <th>MONTANT</th>
                <th>TAUX<br/> D'INTERET</th>
            </tr>
            <?php endif; ?>
            <?php endif; ?>
<?php endforeach; ?>