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
            <?php foreach ($this->lLenders as $lender) : ?>
            <tr>
                <td height="35" style="width: 15%; border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;"><?= $lender['name'] ?></td>
                <td height="35" style="width: 10%; border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;"><?= $lender['firstName'] ?></td>
                <td height="35" style="width: 27%; border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;"><?= $lender['address'] ?></td>
                <td height="35" style="width: 7%; border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;"><?= $lender['zip'] ?></td>
                <td height="35" style="width: 23%; border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;"><?= $lender['city'] ?></td>
                <td height="35" style="width: 10%;border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;" class="nowrap"><?= $lender['amount'] ?>&nbsp;€</td>
                <td height="35" style="width: 8%; border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;" class="nowrap"><?= $lender['rate'] ?>&nbsp;%</td>
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