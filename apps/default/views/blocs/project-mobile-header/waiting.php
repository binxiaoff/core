<div class="single-project-stats">
    <h2>
        <i class="ico-pig"></i>
        <?= $this->ficelle->formatNumber($this->projects->amount, 0) ?> €
    </h2>
    <?php $this->fireView('../blocs/project-mobile-header/stats'); ?>
</div>
<div class="single-project-info">
    <p><?= $this->lng['preteur-projets']['periode-denchere-du-projet-terminee'] ?></p>
</div>
