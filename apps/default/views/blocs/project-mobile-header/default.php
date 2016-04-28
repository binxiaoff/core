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
            <strong><?= $this->ficelle->formatNumber($this->payer, 0) ?>&nbsp;€</strong>
            <?= $this->lng['preteur-projets']['de-pretes'] ?>
        </li>
        <li>
            <?php if ($this->soldeBid >= $this->projects->amount) { ?>
                <p style="font-size:14px;"><?= $this->lng['preteur-projets']['faites-une-offre-de-pret-mobile'] ?> <?= $this->ficelle->formatNumber($this->txLenderMax, 1) ?>&nbsp;%</p>
            <?php } else { ?>
                <span class="price">
                    <strong><?= $this->ficelle->formatNumber($this->resteApayer, 0) ?>&nbsp;€</strong>
                    <?= $this->lng['preteur-projets']['restent-a-preter'] ?>
                </span>
            <?php } ?>
        </li>
    </ul>
    <div class="single-project-progress-bar">
        <span style="width: <?= number_format($this->pourcentage, $this->decimalesPourcentage, '.', '') ?>%"><small><?= $this->ficelle->formatNumber($this->pourcentage, $this->decimalesPourcentage) ?>&nbsp;%</small></span>
    </div>
</div>
