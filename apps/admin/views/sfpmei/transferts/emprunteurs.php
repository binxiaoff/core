<div id="contenu">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-6">
                <h1>Transferts de fonds emprunteurs</h1>
            </div>
            <div class="col-md-6">
                <a href="<?= $this->lurl ?>/sfpmei/transferts/emprunteurs/csv" class="btn-primary pull-right">Export CSV</a>
            </div>
        </div>
        <?php $this->fireView('transferts/table_attributions'); ?>
    </div>
</div>
