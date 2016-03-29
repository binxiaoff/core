<div class="sidebar right">
    <aside class="widget widget-info">
        <div class="widget-top">
            <?= $this->lng['preteur-projets']['projet-finance-a-100'] ?>
        </div>
        <div class="widget-body">
            <div class="article">
                <p>
                    <?= $this->lng['preteur-projets']['ce-projet-est-integralement-finance-par'] ?>
                    <strong class="pinky-span"> <?= $this->ficelle->formatNumber($this->NbPreteurs, 0) ?> <?= $this->lng['preteur-projets']['preteur'] ?><?= ($this->NbPreteurs > 1 ? 's' : '') ?></strong>
                    <br/><?= $this->lng['preteur-projets']['au-taux-de'] ?>
                    <strong class="pinky-span"> <?= $this->ficelle->formatNumber($this->avgRate, 1) ?> %</strong>
                    <br/><?= $this->lng['preteur-projets']['en'] ?> <?= ($this->interDebutFin['day'] > 0 ? $this->interDebutFin['day'] . ' jours ' : '') ?><?= ($this->interDebutFin['hour'] > 0 ? $this->interDebutFin['hour'] . ' heures ' : '') ?> <?= $this->lng['preteur-projets']['et'] ?> <?= $this->interDebutFin['minute'] ?> <?= $this->lng['preteur-projets']['minutes'] ?>
                </p>
                <p>
                    <?= $this->lng['preteur-projets']['vous-lui-avez-prete'] ?>
                    <strong class="pinky-span"><?= $this->ficelle->formatNumber($this->bidsvalid['solde'], 0) ?> €</strong>
                    <?php if ($this->bidsvalid['solde'] > 0) { ?>
                        <br/><?= $this->lng['preteur-projets']['au-taux-moyen-de'] ?>
                        <strong class="pinky-span"><?= $this->ficelle->formatNumber($this->AvgLoansPreteur, 1) ?> %</strong>
                    <?php } ?>
                </p>
                <p><?= $this->lng['preteur-projets']['merci-a-tous'] ?></p>
            </div>
        </div>
    </aside>
</div>
