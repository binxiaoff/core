<div id="contenu">
    <div class="container-fluid">
        <div class="row">
            <span class="pull-right"><h1><?= $this->projects_status->label ?></h1></span>
            <h1>Fiche projet</h1>
            <?php if (false === empty($this->projects->title)) : ?><h2><?= $this->projects->title ?></h2><?php endif; ?>
        </div>
    </div>
    <div class="container-fluid">
        <div id="project-tabs" class="row">
            <ul>
                <li><a href="#projet">Projet</a></li>
                <li><a href="#synthese-financiere">Synthèse financière</a></li>
                <li><a href="#documents">Documents</a></li>
                <li><a href="#comite-risque">Comité risque</a></li>
            </ul>
            <div id="projet" class="container-fluid">
                <?php $this->fireView('projet/projet'); ?>
            </div>
            <div id="synthese-financiere" class="container-fluid">
                <?php $this->fireView('projet/synthese_financiere'); ?>
            </div>
            <div id="documents" class="container-fluid">
                <?php $this->fireView('projet/documents'); ?>
            </div>
            <div id="comite-risque" class="container-fluid">
                <?php $this->fireView('projet/comite_risque'); ?>
            </div>
        </div>
    </div>
</div>

<style>
    ul.ui-tabs-nav {
        background-color: transparent;
    }
</style>

<script>
    $(function () {
        $('#project-tabs').tabs()
    })
</script>
