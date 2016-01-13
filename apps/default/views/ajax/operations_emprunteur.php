<?php

ob_start();

$bOdd = true;
$sPositif = 'positif';
$sNegatif = 'negatif';

foreach ($this->aBorrowerOperations as $aOperation) {
?>
    <tr class="<?= $bOdd ? 'odd' : 'even' ?>">
        <td><?= $this->lng['espace-emprunteur'][ 'operations-type-' . $aOperation['type'] ] ?></td>
        <td class="col2"><?= $aOperation['id_project'] ?></td>
        <td><?= $this->dates->formatDateMysqltoShortFR($aOperation['date']) ?></td>
        <td class="<?= $aOperation['montant'] > 0 ? $sPositif : $sNegatif ?>"><?= $this->ficelle->formatnumber($aOperation['montant']) ?> &euro;</td>
        <?php if ($aOperation['type'] === 'affectation-preteurs'): ?>
            <td>
                <a href="<?= $this->lurl ?>/espace_emprunteur/getCSVWithLenderDetails/e/<?= $aOperation['id_project'].'/'.$aOperation['ordre'] ?>">
                    <img class="xls small" src="<?= $this->surl ?>/images/default/xls_hd.png"/>
                </a>
            </td>
        <?php elseif ($aOperation['type'] === 'financement'): ?>
            <td>
                <a href="<?= $this->lurl ?>/espace_emprunteur/getCSVWithLenderDetails/l/<?= $aOperation['id_project'] ?>">
                     <img class="xls small" src="<?= $this->surl ?>/images/default/xls_hd.png"/>
                </a>
            </td>
        <?php else: ?>
            <td></td>
        <?php endif; ?>
    </tr>
    <?php if ($aOperation['type'] === 'commission-mensuelle'): ?>
        <tr class="<?= $bOdd ? 'odd' : 'even' ?>">
            <td style="text-align: right"><?= $this->lng['espace-emprunteur']['operations-type-commission-ht'] ?></td>
            <td></td>
            <td></td>
            <td class="<?= $aOperation['montant'] > 0 ? $sPositif : $sNegatif ?>"><?= $this->ficelle->formatnumber($aOperation['commission']) ?> &euro;</td>
            <td></td>
        </tr>
        <tr class="<?= $bOdd ? 'odd' : 'even' ?>">
            <td style="text-align: right"><?= $this->lng['espace-emprunteur']['operations-type-tva'] ?></td>
            <td></td>
            <td></td>
            <td class="<?= $aOperation['montant'] > 0 ? $sPositif : $sNegatif ?>"><?= $this->ficelle->formatnumber($aOperation['tva']) ?> &euro;</td>
            <td></td>
        </tr>
    <?php endif; ?>
    <?php
    $bOdd = $bOdd ? false : true;
}

$sHtmlBody = ob_get_contents();
ob_clean();
echo json_encode(array('html' => $sHtmlBody, 'debut' => $this->sDisplayDateTimeStart, 'fin' => $this->sDisplayDateTimeEnd));
