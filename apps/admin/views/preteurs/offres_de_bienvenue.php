<script type="text/javascript">
    $(function() {
        jQuery.tablesorter.addParser({
            id: "fancyNumber", is: function (s) {
                return /[\-\+]?\s*[0-9]{1,3}(\.[0-9]{3})*,[0-9]+/.test(s);
            }, format: function (s) {
                return jQuery.tablesorter.formatFloat(s.replace(/,/g, '').replace(' €', '').replace(' ', ''));
            }, type: "numeric"
        });

        $(".tablesorter").tablesorter();

        $.datepicker.setDefaults($.extend({showMonthAfterYear: false}, $.datepicker.regional['fr']));
        $("#datepik_1").datepicker({
            showOn: 'both',
            buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            yearRange: '<?=(date('Y') - 10)?>:<?=(date('Y') + 10)?>'
        });
        $("#datepik_2").datepicker({
            showOn: 'both',
            buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            yearRange: '<?=(date('Y') - 10)?>:<?=(date('Y') + 10)?>'
        });

        <?php if ($this->nb_lignes != '') : ?>
            $(".tablesorter").tablesorterPager({container: $("#pager"), positionFixed: false, size: <?= $this->nb_lignes ?>});
        <?php endif; ?>
    });
</script>
<style type="text/css">
    table.formColor {
        width: 697px;
    }

    .select {
        width: 251px;
    }

    .fenetre_offres_de_bienvenues {
        width: 697px;
        background-color: white;
        border: 1px solid #A1A5A7;
        border-radius: 10px 10px 10px 10px;
        padding: 5px;
    }
</style>
<div id="contenu">
    <h1>Gestion offre de bienvenue</h1>
    <h3>Somme des offres de bienvenue déjà donnée : <?= $this->currencyFormatter->format($this->alreadyPaidOutAllOffers) ?></h3>
    <h3>Solde Reel disponible : <?= $this->currencyFormatter->format($this->sumDispoPourOffres) ?></h3>
    <h3>Le macaron offre de bienvenue est affiché sur les home : <?= $this->offerIsDisplayedOnHome ? 'Oui' : 'Non' ?></h3>

    <?php $this->fireView('rattrapage_offre_bienvenue'); ?>

    <h1>Gestion de la visibilité de l'offre de bienvenue</h1>
    <?php if (null !== $this->currentOffer) : ?>
    <div class="row">
        <div>Offre en cours: </div>
        Montant : <?= $this->currencyFormatter->format($this->currentOffer->getMontant() / 100) ?><br>
        Actif depuis : <?= $this->currentOffer->getDebut()->format('d/m/Y') ?><br>
        Montant maximum distribuable : <?= $this->currencyFormatter->format($this->currentOffer->getMontantLimit()) ?><br>
        Montant deja distribué sur cette offre : <?= $this->currencyFormatter->format($this->alreadyPaidOutCurentOffer) ?><br>
        Montant encore disponible sur cette offre : <?= $this->currencyFormatter->format($this->remainingAmountCurrentOffer) ?><br>
        <div class="button">
            Desactiver cette offre
        </div>
    <?php else : ?>
        Il y a actuellement aucune offre valide en cours.
        <div type="button">Créer une offre</div>
    <?php endif; ?>
    </div>

<!--    @Dimitar: TODO  Faire un joli formualire :) -->
<!--    <div id="form_create_offer" style="display: none;">-->
    <div id="form_create_offer">
        <form method="post" name="form_create_offer" id="form_create_offer" enctype="multipart/form-data" action="" target="_parent">
            <fieldset>
                <label for="datepik_1">Debut de l'offre :</label>
                <input type="text" name="start" id="start" class="input_dp"/>
                <label for="montant">Montant de l'offre :</label>
                <input type="text" name="amount" id="amount" class="input_moy"/>
                <label for="montant">Dépenses max :</label>
                <input type="text" name="max_amount" id="max_amount" class="input_moy"/>
                <label>Motif :</label><?= $this->welcomeOfferMotiveSetting->getValue() ?>>
                <input type="hidden" name="form_send_offres" id="form_send_offres"/>
                <button type="submit" class="btn-primary">Mettre à jour</button>
            </fieldset>
        </form>
    </div>

<!--  @Dimitar: TODO  Va devenir un tableau de plusieurs lignes, bien que tu en a qu'un seul dans la base pour l'instant-->
    <div id="past_offers">
        <?php if (false === empty($this->allOffers)) : ?>
            <?php foreach ($this->allOffers as $offer) : ?><br>
                Debut: <?= $offer->getDebut()->format('d/m/Y') ?><br>
                Fin: <?= $offer->getFin()->format('d/m/Y') ?><br>
                Montant : <?= $this->currencyFormatter->format($offer->getMontant() / 100) ?><br>
            <?endforeach; ?>
        <?php endif; ?>
    </div>
</div> <!-- contenu !>
