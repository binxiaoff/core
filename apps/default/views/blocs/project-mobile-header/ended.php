<?php

$aReplacements = array(
    '[NB_PRETEURS]' => $this->NbPreteurs . ' prêteurs',
    '[TAUX]'        => $this->ficelle->formatNumber($this->AvgLoans, 1) . '&nbsp;%',
    '[JOURS]'       => $this->interDebutFin['day'] . ' jours',
    '[HEURES]'      => $this->interDebutFin['hour'] . ' heures',
    '[MINUTES]'     => $this->interDebutFin['minute'] . ' minutes'
);

foreach ($aReplacements as &$sReplacement) {
    $sReplacement = '<strong class="pinky-span">' . $sReplacement . '</strong>';
}

?>
<div class="single-project-stats">
    <h3><?= $this->lng['preteur-projets']['projet-finance-a-100'] ?></h3>
    <?php $this->fireView('../blocs/project-mobile-header/stats'); ?>
</div>
<div class="single-project-info">
    <p>
        <?= str_replace(array_keys($aReplacements), $aReplacements, $this->lng['preteur-projets']['projet-finance-mobile']) ?>
    </p>
    <p>
        <?= $this->lng['preteur-projets']['vous-lui-avez-prete'] ?>
        <strong class="pinky-span"><?= $this->ficelle->formatNumber($this->bidsvalid['solde'], 0) ?> €</strong>
        <?php if ($this->bidsvalid['solde'] > 0) { ?>
            <br/><?= $this->lng['preteur-projets']['au-taux-moyen-de'] ?>
            <strong class="pinky-span"><?= $this->ficelle->formatNumber($this->AvgLoansPreteur, 1) ?> %</strong>
        <?php } ?>
    </p>
</div>
