<div id="contenu">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="pull-right"><?= $this->lenderStatusMessage ?></div>
                <h1>Fiche prêteur</h1>
                <h2><?= $this->clients->prenom ?> <?= $this->clients->nom ?> (<?= $this->clients->id_client ?>)</h2>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div id="lender-tabs" class="row">
            <ul>
                <li><a href="#contact">Contact</a></li>
                <li><a href="#validation">Validation</a></li>
                <li><a href="<?= $this->lurl ?>/sfpmei/preteur/<?= $this->clients->id_client ?>/mouvements">Mouvements</a></li>
                <li><a href="<?= $this->lurl ?>/sfpmei/preteur/<?= $this->clients->id_client ?>/portefeuille">Portefeuille</a></li>
            </ul>
            <div id="contact" class="container-fluid">
                <?php $this->fireView('preteur/contact'); ?>
            </div>
            <div id="validation" class="container-fluid"></div>
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
        $('#lender-tabs').tabs();

        $.datepicker.setDefaults($.extend({showMonthAfterYear: false}, $.datepicker.regional['fr']));

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
                    console.log(match[3] + match[2] + match[1])
                    return match[3] + match[2] + match[1];
                }
                return '';
            }
        });
    });
</script>
