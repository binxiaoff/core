<div class="pageBreakBefore">
    <h3 class="pink">ECHEANCIER DES REMBOURSEMENTS</h3>
    <div class="dates-table">
        <table width="100%" cellspacing="0" cellpadding="0" class="table-2">
            <tr>
                <th>DATE</th>
                <th>CAPITAL</th>
                <th>INTERETS</th>
                <th>COMMISSION<br/>UNILEND H.T.</th>
                <th>TVA</th>
                <th>TOTAL</th>
                <th>CAPITAL RESTANT DÛ</th>
            </tr>
            <?php $capRestant = $this->capital; ?>
            <?php $printedLines = 0; ?>
            <?php foreach ($this->lRemb as $r) : ?>
            <?php $montantEmprunteur = round($r['montant'] + $r['commission'] + $r['tva'], 2); ?>
            <?php $capRestant -= $r['capital']; ?>
            <?php if ($capRestant < 0) : ?>
                <?php $capRestant = 0; ?>
            <?php endif; ?>
            <tr>
                <td height="35" style="width: 15%; border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;" class="nowrap"><?= $this->formatDate($r['date_echeance_emprunteur'], 'd/m/Y') ?></td>
                <td height="35" style="width: 15%; border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;" class="nowrap"><?= $this->ficelle->formatNumber($r['capital'] / 100) ?>&nbsp;€</td>
                <td height="35" style="width: 10%; border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;" class="nowrap"><?= $this->ficelle->formatNumber($r['interets'] / 100) ?>&nbsp;€</td>
                <td height="35" style="width: 15%; border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;" class="nowrap"><?= $this->ficelle->formatNumber($r['commission'] / 100) ?>&nbsp;€</td>
                <td height="35" style="width: 10%; border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;" class="nowrap"><?= $this->ficelle->formatNumber($r['tva'] / 100) ?>&nbsp;€</td>
                <td height="35" style="width: 15%; border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;" class="nowrap"><?= $this->ficelle->formatNumber($montantEmprunteur / 100) ?>&nbsp;€</td>
                <td height="35" style="width: 20%; border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;" class="nowrap"><?= $this->ficelle->formatNumber($capRestant / 100) ?>&nbsp;€</td>
            </tr>
            <?php $printedLines++; ?>
            <?php if (0 === $printedLines % $this->numberOfRowsPerPage || $printedLines === count($this->lRemb)) : ?>
        </table>
    </div> <!--dates-table-->
</div> <!--pageBreakBefore-->
<?php if ($printedLines < count($this->lRemb)) : ?>
<div class="pageBreakBefore">
    <div class="dates-table">
        <table width="100%" cellspacing="0" cellpadding="0" class="table-2">
            <tr>
                <th>DATE</th>
                <th>CAPITAL</th>
                <th>INTERETS</th>
                <th>COMMISSION<br/>UNILEND H.T.</th>
                <th>TVA</th>
                <th>TOTAL</th>
                <th>CAPITAL RESTANT DÛ</th>
            </tr>
            <?php endif; ?>
            <?php endif; ?>
            <?php endforeach; ?>
