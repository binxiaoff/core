<div id="contenu">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <h1>Fiche emprunteur</h1>
                <h2><?= $this->clients->prenom . ' ' . $this->clients->nom ?> (<?= $this->clients->id_client ?>)</h2>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div id="borrower-tabs" class="row">
            <ul>
                <li><a href="#contact">Contact</a></li>
                <li><a href="#liste-projets">Liste des projets</a></li>
                <li><a href="#historique-mandats">Historique des mandats</a></li>
            </ul>
            <div id="contact" class="container-fluid">
                <?php $this->fireView('emprunteur/contact'); ?>
            </div>
            <div id="liste-projets" class="container-fluid">
                <?php $this->fireView('emprunteur/liste_projets'); ?>
            </div>
            <div id="historique-mandats" class="container-fluid">
                <?php $this->fireView('emprunteur/historique_mandats'); ?>
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
        $('#borrower-tabs').tabs({
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
