<div id="contenu">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-6">
                <h1>Fiche projet</h1>
                <?php if (false === empty($this->projects->title)) : ?><h2><?= $this->projects->title ?></h2><?php endif; ?>
            </div>
            <div class="col-md-6">
                <h1><?= $this->projects_status->label ?></h1>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div id="project-tabs" class="row">
            <ul>
                <li><a href="#details-projet">Projet</a></li>
                <li><a href="#synthese-financiere">Synthèse financière</a></li>
                <li><a href="#documents">Documents</a></li>
                <li><a href="#comite-risque">Comité risque</a></li>
            </ul>
            <div id="details-projet" class="container-fluid">
                <?php $this->fireView('projet/details_projet'); ?>
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
        $('#project-tabs').tabs({
            beforeLoad: function (event, ui) {
                ui.panel.html('<div class="row text-center"><img src="<?= $this->surl ?>/images/admin/ajax-loader.gif" alt="Chargement en cours ..."></div>');
            }
        });
        jQuery.tablesorter.addParser({
            id: 'amount',
            type: 'numeric',
            is: function (s) {
                return /^-?[0-9\s]+,?[0-9]*/.test(s.replace(/[\n\r]/g, '').replace(/ /g, ''));
            },
            format: function (s) {
                var match = s.replace(/[\n\r]/g, '').replace(/ /g, '').match(/(^-?[0-9\s]+,?[0-9]*)/)
                if (match !== null && match.length > 0) {
                    return match[0].replace(' ', '').replace(',', '');
                }
                return '';
            }
        });

        jQuery.tablesorter.addParser({
            id: 'date',
            type: 'numeric',
            is: function (s) {
                return /[0-9]{2}\/[0-9]{2}\/[0-9]{4}/.test(s);
            },
            format: function (s) {
                var match = s.match(/([0-9]{2})\/([0-9]{2})\/([0-9]{4})/)
                if (match !== null && match.length >= 3) {
                    return match[3] + match[2] + match[1];
                }
                return '';
            }
        });
    });
</script>
