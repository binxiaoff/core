<div class="sidebar right">
    <aside class="widget widget-price">
        <div class="widget-top">
            <i class="icon-pig"></i>
            <?= $this->ficelle->formatNumber($this->projects->amount, 0) ?> €
        </div>
        <div class="widget-body">
            <div class="widget-cat progress-cat clearfix">
                <div class="prices clearfix">
                    <span class="price less">
                        <strong><?= $this->ficelle->formatNumber($this->payer, $this->decimales) ?> €</strong>
                        <?= $this->lng['preteur-projets']['de-pretes'] ?>
                    </span>
                    <i class="icon-arrow-gt"></i>
                    <?php if ($this->soldeBid >= $this->projects->amount) { ?>
                        <p style="font-size:14px;"><?= $this->lng['preteur-projets']['vous-pouvez-encore-preter-en-proposant-une-offre-de-pret-inferieure-a'] ?> <?= $this->ficelle->formatNumber($this->txLenderMax, 1) ?>%</p>
                    <?php } else { ?>
                        <span class="price">
                            <strong><?= $this->ficelle->formatNumber($this->resteApayer, $this->decimales) ?> €</strong>
                            <?= $this->lng['preteur-projets']['restent-a-preter'] ?>
                        </span>
                    <?php } ?>
                </div>
                <div class="progressBar" data-percent="<?= number_format($this->pourcentage, $this->decimalesPourcentage, '.', '') ?>">
                    <div><span></span></div>
                </div>
            </div>
            <div class="widget-cat">
                <p style="padding:20px;"><?= $this->lng['preteur-projets']['completude-message'] ?></p>
            </div>
        </div>
    </aside>
</div>
