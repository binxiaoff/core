<div class="sidebar right">
    <aside class="widget widget-info">
        <div class="widget-top two-lines">
            <?= $this->lng['preteur-projets']['projet-rembourse-a-100'] ?>
        </div>
        <div class="widget-body">
            <div class="article">
                <p>
                    <?= $this->lng['preteur-projets']['ce-projet-a-ete-totalement-rembourse-le'] ?>
                    <strong class="pinky-span"> <?= date('d/m/Y', strtotime($this->lastStatushisto['added'])) ?></strong>
                </p>
                <p>
                    <?= $this->lng['preteur-projets']['vous-lui-avez-prete'] ?>
                    <strong class="pinky-span"><?= number_format($this->bidsvalid['solde'], 0, ',', ' ') ?> €</strong>
                    <?php if ($this->bidsvalid['solde'] > 0) { ?>
                        <br/><?= $this->lng['preteur-projets']['au-taux-moyen-de'] ?>
                        <strong class="pinky-span"><?= number_format($this->AvgLoansPreteur, 1, ',', ' ') ?> %</strong>
                    <?php } ?>
                </p>
            </div>
        </div>
    </aside>
</div>
