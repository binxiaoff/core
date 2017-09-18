<?php use Unilend\Bundle\CoreBusinessBundle\Entity\OffresBienvenues; ?>
<script type="text/javascript">
    $(function() {
        $.tablesorter.addParser({
            id: "fancyNumber", is: function (s) {
                return /[\-\+]?\s*[0-9]{1,3}(\.[0-9]{3})*,[0-9]+/.test(s);
            }, format: function (s) {
                return jQuery.tablesorter.formatFloat(s.replace(/,/g, '').replace(' €', '').replace(' ', ''));
            }, type: "numeric"
        });
        $.datepicker.setDefaults($.extend({showMonthAfterYear: false}, $.datepicker.regional['fr']));
        $("#datepik_1").datepicker({
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

        $(document).on('click', '.create-btn', function(e){
            e.preventDefault()
            $($(this).attr('href')).slideToggle()

            if ($(this).is('.lp'))
                $('#type_offer').val('landing_page')
            else if ($(this).is('.hp'))
                $('#type_offer').val('home_page')
        })
        // Rattrapage tablesorter (Search Lenders by ID)
        var $header = $('#offer-search-table-header')
        var $results = $('#offer-search-table')
        $results.tablesorter()
        $header.find('th').click(function () {
            if ($(this).is('.header')) {
                var $th = $(this),
                    thIndex = $th.index(),
                    sortDirection = 1, // headerSortUp
                    sorting

                if (!$th.is('.sort-active')) {
                    $th.siblings().removeClass('sort-active')
                    $th.addClass('sort-active headerSortUp')
                }

                if ($th.is('.sort-active')) {
                    if ($th.is('.headerSortDown')) {
                        $th.removeClass('headerSortDown').addClass('headerSortUp')
                        sortDirection = 1
                    } else {
                        $th.removeClass('headerSortUp').addClass('headerSortDown')
                        sortDirection = 0
                    }
                }

                sorting = [[thIndex, sortDirection]]
                $results.trigger("sorton", [sorting]);
            }
        })
        // Past offers tablesorter
        $('#offer-past-table').tablesorter()
        // Toggle Rattrapage results
        $('#toggle-trigger').click(function(e){
            e.preventDefault()

            var $this = $(this)
            var $target = $($(this).attr('href'))

            if (!$target.is(':visible'))
                $this.html('Hide Table [x]')
            else
                $this.html('Expand Table [+]')

            $target.slideToggle()
        })
    });
</script>
<style type="text/css">
    .block {
        border-bottom: 1px solid #E3E4E5;
    }
    .block-title {
        padding: 30px 0 0;
        margin: 0;
    }
    .block-content {
        padding-top: 30px;
        padding-bottom: 10px;
    }
    .block-content.block-content-full {
        padding-bottom: 30px;
    }
    #offer-summary .label {
        line-height: 20px;
        margin-bottom: 5px;
        color: #333;
        font-weight: 600;
    }
    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border: 1px solid transparent;
        border-radius: 4px;
    }

    .alert-error {
        color: #444;
        background: #fdf4fa;
        box-shadow: 0 2px #ca9faf;

    }
    .alert-success {
        background: #eaf4ea;
        color: #34a263;
        -webkit-box-shadow: 0 2px #cdefdb;
        box-shadow: 0 2px #cdefdb;
    }
</style>
<div id="contenu">
    <div class="row">
        <div class="col-md-12">
            <h1>Offre de bienvenue</h1>
        </div>
    </div>

    <div id="offer-summary" class="block block-bordered">
        <div class="block-content" style="padding-top: 0">
            <div class="row">
                <div class="col-md-3">
                    <div class="label">Somme des offres données</div>
                    <h3><?= $this->currencyFormatter->format($this->alreadyPaidOutAllOffers) ?></h3>
                </div>
                <div class="col-md-3">
                    <div class="label">Solde réel disponible</div>
                    <h3><?= $this->currencyFormatter->format($this->sumDispoPourOffres) ?></h3>
                </div>
                <div class="col-md-3">
                    <div class="label">Macaron affiché sur homepage</div>
                    <h3><?= $this->offerIsDisplayedOnHome ? 'Oui' : 'Non' ?></h3>
                </div>
                <div class="col-md-3">
                    <div class="label">Macaron affiché sur landing pages</div>
                    <h3><?= $this->offerIsDisplayedOnLandingPage ? 'Oui' : 'Non' ?></h3>
                </div>
            </div>
        </div>
    </div>

    <?php $this->fireView('rattrapage_offre_bienvenue'); ?>

    <div id="offer-visibility" class="block block-bordered">
        <div class="block-header">
            <h3 class="block-title">Gestion de la visibilité</h3>
        </div>
        <div class="block-content block-content-full">
            <div class="row">
                <div class="col-md-3">
                    <h4 style="margin-top: 0">Offre Home Page</h4>
                    <?php if (null !== $this->currentOfferHomepage) : ?>
                        <table class="table table-condensed">
                            <tr>
                                <td style="width: 120px">Montant</td>
                                <td><?= $this->currencyFormatter->format($this->currentOfferHomepage->getMontant() / 100) ?></td>
                            </tr>
                            <tr>
                                <td>Actif depuis</td>
                                <td><?= $this->currentOfferHomepage->getDebut()->format('d/m/Y') ?></td>
                            </tr>
                            <tr>
                                <td>Total alloué</td>
                                <td><?= $this->currencyFormatter->format($this->currentOfferHomepage->getMontantLimit()) ?></td>
                            </tr>
                            <tr>
                                <td>Déjà distribué</td>
                                <td><?= $this->currencyFormatter->format($this->alreadyPaidOutCurrentOfferHomepage) ?></td>
                            </tr>
                            <tr>
                                <td>Encore disponible</td>
                                <td><?= $this->currencyFormatter->format($this->remainingAmountCurrentOfferHomepage) ?></td>
                            </tr>
                        </table>

                        <form method="post" name="form_deactivate_offer" id="form_create_offer" enctype="multipart/form-data" action="<?= $this->lurl ?>/preteurs/deactivate_welcome_offer">
                            <input type="hidden" name="welcome_offer_id" value="<?= $this->currentOfferHomepage->getIdOffreBienvenue() ?>">
                            <input type="hidden" name="deactivate_welcome_offer" value="true">
                            <button type="submit" class="btn-primary btn-sm">Désactiver cette offre</button>
                        </form>
                    <?php else : ?>
                        <div class="alert">
                            <p>Il n'y a actuellement aucune offre valide en cours sur la home page.</p>
                        </div>
                        <a href="#offer-create" class="btn-primary btn-sm create-btn hp">Créer une offre</a>
                    <?php endif; ?>
                </div>
                <div class="col-md-3">
                    <h4 style="margin-top: 0">Offre Landing Page</h4>
                    <?php if (null !== $this->currentOfferLandingPage) : ?>
                        <table class="table table-condensed">
                            <tr>
                                <td style="width: 120px">Montant</td>
                                <td><?= $this->currencyFormatter->format($this->currentOfferLandingPage->getMontant() / 100) ?></td>
                            </tr>
                            <tr>
                                <td>Actif depuis</td>
                                <td><?= $this->currentOfferLandingPage->getDebut()->format('d/m/Y') ?></td>
                            </tr>
                            <tr>
                                <td>Total alloué</td>
                                <td><?= $this->currencyFormatter->format($this->currentOfferLandingPage->getMontantLimit()) ?></td>
                            </tr>
                            <tr>
                                <td>Déjà distribué</td>
                                <td><?= $this->currencyFormatter->format($this->alreadyPaidOutCurrentOfferLandingPage) ?></td>
                            </tr>
                            <tr>
                                <td>Encore disponible</td>
                                <td><?= $this->currencyFormatter->format($this->remainingAmountCurrentOfferLandingPage) ?></td>
                            </tr>
                        </table>
                        <form method="post" action="<?= $this->lurl ?>/preteurs/deactivate_welcome_offer">
                            <input type="hidden" name="welcome_offer_id" value="<?= $this->currentOfferLandingPage->getIdOffreBienvenue() ?>">
                            <input type="hidden" name="deactivate_welcome_offer" value="true">
                            <button type="submit" class="btn-primary btn-sm">Désactiver cette offre</button>
                        </form>
                    <?php else : ?>
                        <div class="alert">
                            <p>Il y a actuellement aucune offre valide en cours sur les landing pages.</p>
                        </div>
                        <a href="#offer-create" class="btn-primary btn-sm create-btn lp">Créer une offre</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div id="offer-create" class="block block-bordered" style="display: none">
        <div class="block-header">
            <h3 class="block-title">Création d'offre</h3>
        </div>
        <div class="block-content block-content-full">
            <?php if (isset($this->newWelcomeOfferFormErrors)) : ?>
                <div class="alert alert-error">
                    <?php foreach ($this->newWelcomeOfferFormErrors as $error) : ?>
                        <span><?= $error ?> </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <form method="post" action="" class="form-inline">
                <div class="form-group">
                    <label>Début de l'offre</label><br>
                    <input type="text" name="start" id="datepik_1" class="form-control" value="">
                </div>
                <div class="form-group" style="margin-left: 10px;">
                    <label>Montant de l'offre</label><br>
                    <input type="text" name="amount" class="form-control" value="">
                </div>
                <div class="form-group" style="margin-left: 10px;">
                    <label>Dépenses max</label><br>
                    <input type="text" name="max_amount" class="form-control" value="">
                </div>
                <div class="form-group" style="margin-left: 10px;">
                    <label>Affichée sur</label><br>
                    <select id="type_offer" name="type_offer" class="form-control">
                        <option value=""></option>
                        <option value="<?= \Unilend\Bundle\CoreBusinessBundle\Entity\OffresBienvenues::TYPE_HOME ?>">Home Page</option>
                        <option value="<?= \Unilend\Bundle\CoreBusinessBundle\Entity\OffresBienvenues::TYPE_LANDING_PAGE ?>">Landing Page</option>
                    </select>
                </div>
                <input type="hidden" name="form_send_new_offer">
                <button type="submit" class="btn-primary" style="margin-top: 19px; margin-left: 10px; width: 120px;">Créer l'offre</button>
            </form>
        </div>
    </div>

    <div id="offer-past" class="block block-bordered" style="border: 0">
        <div class="block-header">
            <h3 class="block-title">Offres passées</h3>
        </div>
        <div class="block-content">
            <?php if (false === empty($this->pastOffers)) : ?>
                <table id="offer-past-table" class="tablesorter table table-hover table-stripped">
                    <thead>
                    <tr>
                        <th>Type</th>
                        <th>Début</th>
                        <th>Fin</th>
                        <th>Montant</th>
                        <th>Statut</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($this->pastOffers as $offer) : ?>
                        <tr>
                            <td><?= (false === in_array($offer->getType(), [OffresBienvenues::TYPE_HOME, OffresBienvenues::TYPE_LANDING_PAGE])) ? $offer->getType() : ((OffresBienvenues::TYPE_LANDING_PAGE == $offer->getType()) ? 'Landing Page' : 'Home Page'); ?></td>
                            <td><?= $offer->getDebut()->format('d/m/Y') ?></td>
                            <td><?= null !== $offer->getFin() ? $offer->getFin()->format('d/m/Y') : '' ?></td>
                            <td><?= $this->currencyFormatter->format($offer->getMontant() / 100) ?></td>
                            <td><?= OffresBienvenues::STATUS_OFFLINE == $offer->getStatus() ? 'Terminée' : 'En cours' ?></td>
                        </tr>
                    <? endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
