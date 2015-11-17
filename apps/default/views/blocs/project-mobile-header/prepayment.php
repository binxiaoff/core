<div class="single-project-stats">
    <h3><?= $this->lng['preteur-projets']['projet-rembourse-a-100'] ?></h3>
    <?php $this->fireView('../blocs/project-mobile-header/stats'); ?>
</div>
<div class="single-project-info">
    <p>
        <?= $this->lng['preteur-projets']['ce-projet-a-ete-totalement-rembourse-le'] ?>
        <strong class="pinky-span"> <?= date('d/m/Y', strtotime($this->lastStatushisto['added'])) ?></strong>
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
