<div class="sidebar right">
    <aside class="widget widget-info">
        <div class="widget-top two-lines">
            <?= $this->lng['preteur-projets']['projet-na-pas-pu-etre-finance-a-100'] ?>
        </div>
        <div class="widget-body">
            <div class="article">
                <p><?= $this->lng['preteur-projets']['ce-projet-a-ete-finance-a'] ?><?= $this->ficelle->formatNumber($this->pourcentage, $this->decimalesPourcentage) ?>%</p>
                <p><?= $this->lng['preteur-projets']['merci-a-tous'] ?></p>
            </div>
        </div>
    </aside>
</div>
