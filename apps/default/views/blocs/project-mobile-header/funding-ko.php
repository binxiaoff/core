<div class="single-project-stats">
    <h3><?= $this->lng['preteur-projets']['projet-na-pas-pu-etre-finance-a-100'] ?></h3>
    <?php $this->fireView('../blocs/project-mobile-header/stats'); ?>
</div>
<div class="single-project-info">
    <p><?= $this->lng['preteur-projets']['ce-projet-a-ete-finance-a'] ?><?= number_format($this->pourcentage, $this->decimalesPourcentage, ',', '') ?>%</p>
    <p><?= $this->lng['preteur-projets']['merci-a-tous'] ?></p>
</div>
