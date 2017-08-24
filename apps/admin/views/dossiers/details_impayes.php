<div class="row">
    <div class="col-md-12">
        <div class="block block-rounded">
            <div class="block-header">
                <div class="pull-left">
                    <a href="/emprunteurs/projets_avec_retard" class="text-muted"><span class="fa fa-arrow-left"></span> Retards et recouvrements</a>
                </div>
                <div class="pull-right">
                    <small>Siren: 429892899</small> <small>|</small> <small>ID Projet: 429892</small>
                    <span class="label label-info push-10-l">Remboursement</span>
                </div>
            </div>
            <div class="block-content block-content-full">
                <h2 class="h3 push-30">
                    Europe Metal Concept <br>
                    <small class="font-w400">Ingenerie, Etudes, Techniques</small>
                </h2>
                <div class="row push-30">
                    <div class="col-md-3">
                        <div class="font-s12 font-w600 text-uppercase">Restant à recouvrer</div>
                        <a class="h2 font-w300 text-primary">17 000 €</a>
                    </div>
                    <div class="col-md-3">
                        <div class="font-s12 font-w600 text-uppercase">Confié au recouvreur</div>
                        <a class="h2 font-w300 text-primary">0%</a>
                    </div>
                    <div class="col-md-3">
                        <div class="font-s12 font-w600 text-uppercase">Réceptions à traiter</div>
                        <a class="h2 font-w300 text-primary">5</a>
                    </div>
                </div>
            </div>
        </div>
        <div id="retards" class="block block-rounded">
            <?php $this->fireView('blocs/details_impayes/retards'); ?>
        </div>
        <div id="debt-colletion" class="block block-rounded">
            <?php $this->fireView('blocs/details_impayes/recouvrements'); ?>
        </div>
        <div id="fees-honoraries" class="block block-rounded">
            <?php $this->fireView('blocs/details_impayes/frais_honoraires') ?>
        </div>
        <div id="receptions" class="block block-rounded">
            <?php $this->fireView('blocs/details_impayes/receptions') ?>
        </div>
    </div>
</div>
