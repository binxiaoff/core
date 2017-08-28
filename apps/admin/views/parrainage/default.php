<!-- Liste des campagnes en cours avec les champs suivants (le même tableau sera utilsié pour les campagens expirés en dessous. Evtl avec un bouton pour afficher/cacher les campagnes expirées
Les campagnes en cours doivent etre modifiables par soumission de formulaire
-->

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
    #parrainage-summary .label {
        line-height: 20px;
        margin-bottom: 5px;
        color: #333;
        font-weight: 600;
    }
    #parrainage-create .form-control {
        width: 120px;
    }
</style>

<div id="contenu">

<div class="row">
    <div class="col-md-12">
        <h1>Gestion des parrainages</h1>
    </div>
</div>

<div id="parrainage-summary" class="block block-bordered">
    <div class="block-content" style="padding-top: 0">
        <div class="row">
            <div class="col-md-2">
                <div class="label">Parrains</div>
                <h3>15</h3>
            </div>
            <div class="col-md-2">
                <div class="label">Filleuls</div>
                <h3>34</h3>
            </div>
            <div class="col-md-2">
                <div class="label">Distribué aux parrains</div>
                <h3>1200 €</h3>
            </div>
            <div class="col-md-2">
                <div class="label">Distribué aux Filleuls</div>
                <h3>3000 €</h3>
            </div>
            <div class="col-md-3">
                <div class="label">Encore disponible * </div>
                <h3>3000 €</h3>
                * <small>(Attention, argent partagé avec les offres de bienvenue)</small>
            </div>
        </div>

    </div>
</div>

<div id="parrainage-create" class="block block-bordered">
    <div class="block-header">
        <h3 class="block-title">Créer une campagne de parrainage</h3>
    </div>
    <div class="block-content block-content-full">
        <?php if (isset($this->newCampaignFormErrors)) : ?>
            <div class="alert alert-error">
                <p>
                <?php foreach ($this->newCampaignFormErrors as $error) : ?>
                    <span><?= $error ?> </span> <br>
                <?php endforeach; ?>
                </p>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= $this->lurl ?>/parrainage/create_new_campaign" class="form-inline" style="margin-bottom: 20px;">
            <div class="form-group">
                <label>Début de l'offre</label> <br>
                <input type="text" name="start" value="" class="form-control required">
            </div>
            <div class="form-group" style="margin-left: 5px;">
                <label>Fin de l'offre</label> <br>
                <input type="text" name="end" value="" class="form-control required">
            </div>
            <div class="form-group" style="margin-left: 5px;">
                <label>Montant (Filleul)</label> <br>
                <input type="number" name="amount_sponsee" class="form-control required">
            </div>
            <div class="form-group" style="margin-left: 5px;">
                <label>Montant (Parrain)</label> <br>
                <input type="number" name="amount_sponsor" class="form-control required">
            </div>
            <div class="form-group" style="margin-left: 5px;">
                <label>Max. filleuls</label> <br>
                <input type="number" name="max_number_sponsee" class="form-control required">
            </div>
            <div class="form-group" style="margin-left: 5px;">
                <label>Durée de validité</label> <br>
                <input type="number" name="validity_days" class="form-control required">
            </div>
            <input type="hidden" name="id_campaign" value="">
            <input type="hidden" name="create_new_campaign" value="">
            <button type="submit" class="btn-primary" style="margin-top: 19px; margin-left: 5px; width: 90px;">Créer</button>
        </form>

        <p><b>Liste des campagnes</b></p>
        <!-- @TODO Dynamisation de la table -->
        <table class="table tablesorter table-striped table-hover">
            <thead>
            <tr>
                <th>Début de l'offre</th>
                <th>Montant (Filleul)</th>
                <th>Montant (Parrain)</th>
                <th>Max. filleuls</th>
                <th>Durée de validité</th>
                <th>Statut</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>Début de l'offre</td>
                <td>Montant (Filleul)</td>
                <td>Montant (Parrain)</td>
                <td>Max. filleuls</td>
                <td>Durée de validité</td>
                <td>Statut</td>
            </tr>
            </tbody>
        </table>
    </label>
</div>
</div>

<div id="parrainage-blacklist" class="block block-bordered">
    <div class="block-header">
        <h3 class="block-title">
            Liste des clients exclus (Blacklist)
        </h3>
    </div>
    <div class="block-content">
        <?php if (false === empty($this->blacklistFormErrors)) : ?>
            <div class="alert alert-error">
                <p>
                <?php foreach ($this->blacklistFormErrors as $error) : ?>
                    <span><?= $error ?> </span> <br>
                <?php endforeach; ?>
                </p>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= $this->lurl ?>/parrainage/blacklist" class="form-inline" style="margin-bottom: 20px;">
            <div class="form-group">
                <label>ID Client</label> <br>
                <input type="number" name="id_client" class="form-control required">
            </div>
            <div class="form-group">
                <label>Campagne</label> <br>
                <select name="campaign" class="form-control required" style="margin-left: 5px;">
                    <option value="">Selectionner</option>
                    <option value="0">Toutes les campagnes</option>
                    <option value="<?php // $this->ongoingCampaign->getId(); ?>">Campagne en cours</option>
                </select>
            </div>
            <input type="hidden" name="blacklist_client" value="">
            <button type="submit" class="btn-primary" style="margin-top: 19px; margin-left: 5px;">Valider</button>
        </form>

        <table class="table tablesorter table-striped table-hover">
            <thead>
            <tr>
                <th>ID Client</th>
                <th>Nom</th>
                <th>Prénom</th>
                <th>Date d'exclusion</th>
                <th>campagne</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>ID Client</td>
                <td>Nom</td>
                <td>Prénom</td>
                <td>Date d'exclusion</td>
                <td>Campagne</td>
            </tr>
            </tbody>
        </table>
    </div>
</div>

<div id="parrainage-adjust-prime" class="block block-bordered">
    <div class="block-header">
        <h3 class="block-title">Attribution manuelle de la prime de parrainage</h3>
    </div>
    <div class="block-content">
        <?php if (isset($this->searchSponsorshipErrors)) : ?>
            <div class="alert alert-error">
                <p>
                    <?php foreach ($this->searchSponsorshipErrors as $error) : ?>
                        <span><?= $error ?> </span> <br>
                    <?php endforeach; ?>
                </p>
            </div>
        <?php endif; ?>

        <form method="post" action="" class="form-inline" style="margin-bottom: 20px;">
            <div class="form-group">
                <label>ID Client</label> <br>
                <input type="text" name="id_client" class="form-control required">
            </div>
            <div class="form-group">
                <label style="margin-top: 19px; margin-left: 10px;">
                    <input type="radio" name="type" value="sponsor" class="form-control required">
                    Parrain
                </label>
                <label style="margin-left: 10px">
                    <input type="radio" name="type" value="sponsee" class="form-control required">
                    Filleul
                </label>
            </div>
            <input type="hidden" name="search_sponsorship" value="">
            <button type="submit" class="btn-primary" style="margin-top: 19px; margin-left: 10px;">Rechercher</button>
        </form>

        <?php if (isset($this->payOutRewardErrors)) : ?>
            <div id="create_offer_errors">
                <?php foreach ($this->payOutRewardErrors as $error) : ?>
                    <span><?= $error ?> </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (0 < count($this->sponsorships)) : ?>

            <?php $clientType = 'filleul' ?>

            <div class="row">
                <div class="col-md-4">
                    <?php if ($clientType === 'parrain') : ?>
                        <p><b>Parrain trouvé :</b></p>
                    <?php elseif ($clientType === 'filleul') : ?>
                        <p><b>Filleul trouvé :</b></p>
                    <?php endif; ?>
                    <table class="table table-condensed table-bordered">
                        <thead>
                        <tr>
                            <th colspan="2">
                                Client ID (878874)
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>Nom</td>
                            <td>Baptiste</td>
                        </tr>
                        <tr>
                            <td>Prénom</td>
                            <td>Jean</td>
                        </tr>
                        <?php if ($clientType === 'parrain') : ?>
                            <tr>
                                <td>Lien parrainage</td>
                                <td>http://unilend.fr/parrainage/ueyrksi109</td>
                            </tr>
                            <tr>
                                <td>N. de Filleuls</td>
                                <td>2</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="col-md-8">
                    <?php if ($clientType === 'parrain') : ?>
                        <p><b>Liste de ces filleuls :</b></p>
                    <?php elseif ($clientType === 'filleul') : ?>
                        <p><b>Parrainé par :</b></p>
                    <?php endif; ?>

                    <table class="table table-striped table-hover table-condensed">
                        <thead>
                        <tr>
                            <th>Client ID</th>
                            <th>Nom</th>
                            <th>Prénom</th>
                            <th>Prime Filleul</th>
                            <th>Prime Parrain</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($this->sponsorships as $sponsorship) : ?>
                        <tr>
                            <td>12322</td>
                            <td>Hollande</td>
                            <td>Greg</td>
                            <td>
                                <?php if (\Unilend\Bundle\CoreBusinessBundle\Entity\Sponsorship::STATUS_SPONSEE_PAID == $sponsorship->getStatus()) { ?>
                                    Versée
                                <?php } else if (\Unilend\Bundle\CoreBusinessBundle\Entity\Sponsorship::STATUS_ONGOING == $sponsorship->getStatus()) : ?>
                                    <form method="post" action="<?= $this->lurl ?>/parrainage/pay_out_reward">
                                        <input type="hidden" name="id_sponsorship" value="<?= $sponsorship->getId() ?>">
                                        <input type="hidden" name="pay_out_reward" value="">
                                        <input type="hidden" name="type_reward" value="<?php \Unilend\Bundle\CoreBusinessBundle\Entity\OperationSubType::UNILEND_PROMOTIONAL_OPERATION_SPONSORSHIP_REWARD_SPONSEE ?>">
                                        <button type="submit" class="btn-primary btn-sm">Verser</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (\Unilend\Bundle\CoreBusinessBundle\Entity\Sponsorship::STATUS_ONGOING == $sponsorship->getStatus()) { ?>
                                    Versée
                                <?php } else { ?>
                                    <form method="post" action="<?= $this->lurl ?>/parrainage/pay_out_reward">
                                        <input type="hidden" name="id_sponsorship" value="<?= $sponsorship->getId() ?>">
                                        <input type="hidden" name="pay_out_reward" value="">
                                        <input type="hidden" name="type_reward" value="<?= \Unilend\Bundle\CoreBusinessBundle\Entity\OperationSubType::UNILEND_PROMOTIONAL_OPERATION_SPONSORSHIP_REWARD_SPONSOR ?>">
                                        <button type="submit" class="btn-primary btn-sm">Verser</button>
                                    </form>
                                <?php } ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="parrainage-hostory" class="block block-bordered">
    <div class="block-header">
        <h3 class="block-title">Primes déjà distribués</h3>
    </div>
    <div class="block-content">
        <table class="table tablesorter table-striped table-hover">
            <thead>
            <tr>
                <th>Code</th>
                <th>Filleul ID</th>
                <th>Nom/Prénom</th>
                <th>Email</th>
                <th>Prime</th>
                <th style="border-right: 1px solid #fff;">Date Versement</th>
                <th>Parrain ID</th>
                <th>Nom/Prénom</th>
                <th>Email</th>
                <th>Prime</th>
                <th>Date Versement</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    726JDY
                </td>
                <td>
                    8322687
                </td>
                <td>
                    <span class="text-uppercase">BAPTISTE</span> Jean
                </td>
                <td>
                    jean@baptiste.com
                </td>
                <td>
                    20 E
                </td>
                <td style="border-right: 1px solid #ddd;">
                    20/08/2017
                </td>
                <td>
                    239223
                </td>
                <td>
                    <span class="text-uppercase">TRIGODET</span> Sylvain
                </td>
                <td>
                    sylvain@gmail.com
                </td>
                <td>
                    20 E
                </td>
                <td>
                    24/08/2017
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</div>

<div id="parrainage-estanlish-link">

    <div class="block-header">
        <h3 class="block-title">Créer une relation entre parrain et filleul</h3>
    </div>
    <div class="block-content">
        <?php if (isset($this->createSponsorshipErrors)) : ?>
            <div class="alert alert-error">
                <p>
                <?php foreach ($this->createSponsorshipErrors as $error) : ?>
                    <span><?= $error ?> </span> <br>
                <?php endforeach; ?>
                </p>
            </div>
        <?php endif; ?>
        <form method="post" action="<?= $this->lurl ?>/parrainage/create_sponsorship" class="form-inline" style="margin-bottom: 40px;">
            <div class="form-group">
                <label>ID Client Parrain</label> <br>
                <input type="number" name="id_client_sponsor" class="form-control">
            </div>
            <div class="form-group" style="margin-left: 5px;">
                <label>ID Client Filleul</label> <br>
                <input type="number" name="id_client_sponsee" class="form-control">
            </div>
            <input type="hidden" name="create_sponsorship" value="">
            <button type="submit" class="btn-primary" style="margin-left: 5px; margin-top: 19px; width: 90px;">Associer</button>
        </form>

        <?php if (isset($this->createSponsorshipData)) : ?>
            <div class="row">
                <div class="col-md-3">
                    <p><b>Filleul</b></p>
                    <table class="table table-condensed table-bordered" style="max-width: 300px">
                        <tr>
                            <td>ID Client</td>
                            <td>43424</td>
                        </tr>
                        <tr>
                            <td>Nom</td>
                            <td>Gerard</td>
                        </tr>
                        <tr>
                            <td>Prénom</td>
                            <td>Christopher</td>
                        </tr>
                        <tr>
                            <td>Date d'inscription</td>
                            <td>14/08/2017</td>
                        </tr>
                        <tr>
                            <td>Date de validation</td>
                            <td>15/08/2017</td>
                        </tr>
                    </table>

                    <div class="alert alert-info">
                        <p>Filleul a reçu l'offre de bienvenue et ne recevra donc pas de prime de parrainage</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <p><b>Parrain</b></p>
                    <table class="table table-condensed" style="max-width: 300px">
                        <tr>
                            <td>ID Client</td>
                            <td>43424</td>
                        </tr>
                        <tr>
                            <td>Nom</td>
                            <td>BAPTISTE</td>
                        </tr>
                        <tr>
                            <td>Prénom</td>
                            <td>Jean</td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 text-center">
                    <form method="post" action="<?= $this->lurl ?>/parrainage/create_sponsorship">
                        <input type="hidden" name="create_sponsorship_confirm" value="">
                        <input type="hidden" name="id_client_sponsor" value="<?= $this->createSponsorshipData['idClientSponsor'] ?>">
                        <input type="hidden" name="id_client_sponsee" value="<?= $this->createSponsorshipData['idClientSponsee'] ?>">
                        <button type="submit" class="btn-primary">Confirmer l'association</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>


</div> <!-- /#contenu -->