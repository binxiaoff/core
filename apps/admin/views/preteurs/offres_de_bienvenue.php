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
    <div id="general_information">
        <h1>Gestion offre de bienvenue</h1>
        <h3>Somme des offres de bienvenue déjà donnée : <?= $this->currencyFormatter->format($this->alreadyPaidOutAllOffers) ?></h3>
        <h3>Solde Reel disponible : <?= $this->currencyFormatter->format($this->sumDispoPourOffres) ?></h3>
        <h3>Le macaron offre de bienvenue est affiché sur les home : <?= $this->offerIsDisplayedOnHome ? 'Oui' : 'Non' ?></h3>
        <h3>Le macaron offre de bienvenue est affiché sur les landing pages : <?= $this->offerIsDisplayedOnLandingPage ? 'Oui' : 'Non' ?></h3>
    </div>

    <?php $this->fireView('rattrapage_offre_bienvenue'); ?>

    <div id="manage_offer_display"> <!-- I thought it would be nice to have those two offer div side by side -->
        <h1>Gestion de la visibilité de l'offre de bienvenue</h1>
        <div class="row">
            <div id="offer_hp">Offre Home:
                <?php if (null !== $this->currentOfferHomepage) : ?>
                    Montant : <?= $this->currencyFormatter->format($this->currentOfferHomepage->getMontant() / 100) ?><br>
                    Actif depuis : <?= $this->currentOfferHomepage->getDebut()->format('d/m/Y') ?><br>
                    Montant maximum dispoible : <?= $this->currencyFormatter->format($this->currentOfferHomepage->getMontantLimit()) ?><br>
                    Montant deja distribué sur cette offre : <?= $this->currencyFormatter->format($this->alreadyPaidOutCurrentOfferHomepage) ?><br>
                    Montant encore disponible sur cette offre : <?= $this->currencyFormatter->format($this->remainingAmountCurrentOfferHomepage) ?><br>
                    <form method="post" name="form_deactivate_offer" id="form_create_offer" enctype="multipart/form-data" action="<?= $this->lurl ?>/preteurs/deactivate_welcome_offer">
                        <input type="hidden" name="welcome_offer_id" value="<?= $this->currentOfferHomepage->getIdOffreBienvenue() ?>">
                        <input type="hidden" name="deactivate_welcome_offer" value="true">
                        <button type="submit">Desactiver cette offre</button>
                    </form>
                <?php else : ?>
                    Il y a actuellement aucune offre valide en cours sur la home page.
                    <div class="button">Créer une offre</div> <!-- displays the div with the form in it -->
                <?php endif; ?>
            </div>
            <div id="offer_lp">Offre Landing Page:
                <?php if (null !== $this->currentOfferLandingPage) : ?>
                    Montant : <?= $this->currencyFormatter->format($this->currentOfferLandingPage->getMontant() / 100) ?><br>
                    Actif depuis : <?= $this->currentOfferLandingPage->getDebut()->format('d/m/Y') ?><br>
                    Montant maximum disponible : <?= $this->currencyFormatter->format($this->currentOfferLandingPage->getMontantLimit()) ?><br>
                    Montant deja distribué sur cette offre : <?= $this->currencyFormatter->format($this->alreadyPaidOutCurrentOfferLandingPage) ?><br>
                    Montant encore disponible sur cette offre : <?= $this->currencyFormatter->format($this->remainingAmountCurrentOfferLandingPage) ?><br>
                    <form method="post" name="form_deactivate_offer" id="form_create_offer" enctype="multipart/form-data" action="<?= $this->lurl ?>/preteurs/deactivate_welcome_offer">
                        <input type="hidden" name="welcome_offer_id" value="<?= $this->currentOfferLandingPage->getIdOffreBienvenue() ?>">
                        <input type="hidden" name="deactivate_welcome_offer" value="true">
                        <button type="submit">Desactiver cette offre</button>
                    </form>
                <?php else : ?>
                    Il y a actuellement aucune offre valide en cours sur les landing pages.
                    <div class="button">Créer une offre</div><!-- displays the div with the form in it -->
                <?php endif; ?>
            </div>
        </div>
    </div>

<!--    @Dimitar: TODO  Faire un joli formulaire :) -->
<!--    <div id="form_create_offer" style="display: none;">-->
    <div id="form_create_offer">
        <?php if (isset($this->newWelcomeOfferFormErrors)) : ?>
            <div id="create_offer_errors">
                <?php foreach ($this->newWelcomeOfferFormErrors as $error) : ?>
                    <span><?= $error ?> </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <form method="post" name="form_create_offer" id="form_create_offer" enctype="multipart/form-data" action="" target="_parent">
            <fieldset>
                <label for="datepik_1">Debut de l'offre :</label>
                <input type="text" name="start"
                       id="datepik_1"
                       class="input_dp"
                       value=""/>
                <label for="montant">Montant de l'offre :</label>
                <input type="text" name="amount" id="amount" class="input_moy"/>
                <label for="montant">Dépenses max :</label>
                <input type="text" name="max_amount" id="max_amount" class="input_moy"/>
                <label for="type_offer">Affiché sur</label>
                <select id="type_offer" name="type_offer">
                    <option value=""></option>
                    <option value="<?= \Unilend\Bundle\CoreBusinessBundle\Entity\OffresBienvenues::TYPE_HOME ?>">Landing Page</option>
                    <option value="<?= \Unilend\Bundle\CoreBusinessBundle\Entity\OffresBienvenues::TYPE_LANDING_PAGE ?>">HomePage</option>
                </select>
                <input type="hidden" name="form_send_new_offer" id="form_send_offer"/>
                <button type="submit" class="btn-primary">Créer</button>
            </fieldset>
        </form>
    </div>

<!--  @Dimitar: TODO  Va devenir un tableau de plusieurs lignes, bien que tu en a qu'un seul dans la base pour l'instant-->
    <div id="past_offers">
        <?php if (false === empty($this->allOffers)) : ?>
            <?php foreach ($this->allOffers as $offer) : ?><br>
                Debut: <?= $offer->getDebut()->format('d/m/Y') ?><br>
                Fin: <?= null !== $offer->getFin() ? $offer->getFin()->format('d/m/Y') : '' ?><br>
                Montant : <?= $this->currencyFormatter->format($offer->getMontant() / 100) ?><br>
                Status : <?= \Unilend\Bundle\CoreBusinessBundle\Entity\OffresBienvenues::STATUS_OFFLINE == $offer->getStatus() ? 'offre terminée' : 'offre en cours' ?>
            <?endforeach; ?>
        <?php endif; ?>
    </div>
</div> <!-- contenu !>
