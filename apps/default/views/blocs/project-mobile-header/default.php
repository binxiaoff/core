<div class="single-project-stats">
    <h2>
        <i class="ico-pig"></i>
        <?= $this->ficelle->formatNumber($this->projects->amount, 0) ?> €
    </h2>
    <?php $this->fireView('../blocs/project-mobile-header/stats'); ?>
</div>
<div class="single-project-price">
    <ul>
        <li>
            <strong><?= $this->ficelle->formatNumber($this->payer, $this->decimales) ?> €</strong>
            <?= $this->lng['preteur-projets']['de-pretes'] ?>
        </li>
        <li>
            <?php if ($this->soldeBid >= $this->projects->amount) { ?>
                <p style="font-size:14px;"><?= $this->lng['preteur-projets']['faites-une-offre-de-pret-mobile'] ?> <?= $this->ficelle->formatNumber($this->txLenderMax, 1) ?>%</p>
            <?php } else { ?>
                <span class="price">
                    <strong><?= $this->ficelle->formatNumber($this->resteApayer, $this->decimales) ?> €</strong>
                    <?= $this->lng['preteur-projets']['restent-a-preter'] ?>
                </span>
            <?php } ?>
        </li>
    </ul>
    <div class="single-project-progress-bar">
        <span style="width: <?= number_format($this->pourcentage, $this->decimalesPourcentage, '.', '') ?>%"><small><?= number_format($this->pourcentage, $this->decimalesPourcentage, '.', '') ?>%</small></span>
    </div>
</div>
