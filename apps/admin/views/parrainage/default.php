<!-- Liste des campagnes en cours avec les champs suivants (le même tableau sera utilsié pour les campagens expirés en dessous. Evtl avec un bouton pour afficher/cacher les campagnes expirées
Les campagnes en cours doivent etre modifiables par soumission de formulaire
-->
<div id="sponsorship_campaigns">
    <h2>Campagnes en cours</h2>
    <?php if (isset($this->newCampaignFormErrors)) : ?>
        <div id="create_offer_errors">
            <?php foreach ($this->newCampaignFormErrors as $error) : ?>
                <span><?= $error ?> </span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <form method="post" name="form_create_campaign" id="form_create_campaign" enctype="multipart/form-data" action="<?= $this->lurl ?>/parrainage/create_new_campaign">
        <fieldset>
            <label for="start">Début de l'offre :</label>
            <input type="text" name="start" id="start" value=""/>
            <label for="end">Fin de l'offre :</label>
            <input type="text" name="end" id="end"/>
            <label for="amount_sponsee">Montant pour le filleul :</label>
            <input type="number" name="amount_sponsee" id="amount_sponsee"/>
            <label for="amount_sponsor">Montant pour le parrain :</label>
            <input type="number" name="amount_sponsor" id="amount_sponsor"/>
            <label for="max_number_sponsee">Nombre maximum de filleuls :</label>
            <input type="number" name="max_number_sponsee" id="max_number_sponsee"/>
            <label for="validity_days">Jours de validité</label>
            <input type="number" name="validity_days" id="validity_days"/>
            <input type="hidden" name="id_campaign" id="id_campaign" value=""/>
            <input type="hidden" name="create_new_campaign" value=""/>
            <button type="submit" class="btn-primary">Créer</button>
        </fieldset>
    </form>
    Nombre de parrains<br>
    Nombre de filleuls<br>
    Montant déja distribué aux filleuls<br>
    Montant déjà distribué aux parrains<br>
    Argent encore disponible (Attention, argent partagé avec les offres de bienvenue)<br>

    <h2><Campagnes expirées</h2>
</div>

<!-- Blacklister un client, avec pop-in de confirmation êtes vous sur de voulouir blacklister le client ... / en dessous affichage de tous les clienst blacklistés-->

<div id="blacklist">
    <h2>Exclure un client du programme de parrainage</h2>
    <?php if (false === empty($this->blacklistFormErrors)) : ?>
        <div id="create_offer_errors">
            <?php foreach ($this->blacklistFormErrors as $error) : ?>
                <span><?= $error ?> </span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <form method="post" name="form_blacklist" id="form_blacklist" enctype="multipart/form-data" action="<?= $this->lurl ?>/parrainage/blacklist">
        <fieldset>
            <label for="id_client">Client</label>
            <input type="number" name="id_client" id="id_client"/>

            <select id="campaign" name="campaign">
                <option value=""></option>
                <option value="0">Toutes les campagnes</option>
                <option value="<?= $this->ongoingCampaign->getId() ?>">Campagne en cours</option>
            </select>
            <input type="hidden" name="blacklist_client" value=""/>
            <button type="submit" class="btn-primary">Créer</button>
        </fieldset>
    </form>

    <h3>Liste des clients exclu du programme</h3>
    Id client, Nom, Prénom, date d'exclusion, campagne
</div>

<!-- Chercher Client ensuite afficher la liste des sponsorships  -->
<div id="payout_reward">
    <h2>Attribution manuelle de la prime de parrainage</h2>
    <?php if (isset($this->searchSponsorshipErrors)) : ?>
        <div id="create_offer_errors">
            <?php foreach ($this->searchSponsorshipErrors as $error) : ?>
                <span><?= $error ?> </span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <form method="post" name="search_sponsorship">
        <fieldset>
            <label for="id_client">Client</label>
            <input type="number" name="id_client" id="id_client"/>
            <input type="radio" name="type" value="sponsor"> Parrain<br>
            <input type="radio" name="type" value="sponsee"> Filleul<br>
            <input type="hidden" name="search_sponsorship" value="">
            <button type="submit" class="btn-primary" id="search_sponsorship">Rechercher</button>
        </fieldset>
    </form>
    <div id="sponsorship_detail"> <!-- info call by ajax, confirmation will eventually submit the form -->
        <?php if (isset($this->payOutRewardErrors)) : ?>
            <div id="create_offer_errors">
                <?php foreach ($this->payOutRewardErrors as $error) : ?>
                    <span><?= $error ?> </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if (0 < count($this->sponsorships)) : ?>
            <?php foreach ($this->sponsorships as $sponsorship) : ?>
                ID client Filleul
                Nom Filleul
                Prénom filleul
                ID client Parrain
                Nom Parrain
                Prénom Parrain
                Statut (en attente, prime filleul versée, prime parrain versé)
                <form method="post" name="pay_out_reward" enctype="multipart/form-data" action="<?= $this->lurl ?>/parrainage/pay_out_reward">
                    <fieldset>
                    <input type="hidden" name="id_sponsorship" value="<?= $sponsorship->getId() ?>"/>
                    <input type="hidden" name="pay_out_reward" value=""/>
                        <?php if (\Unilend\Bundle\CoreBusinessBundle\Entity\Sponsorship::STATUS_ONGOING == $sponsorship->getStatus()) : ?>
                            <input type="hidden" name="type_reward" value="<?= \Unilend\Bundle\CoreBusinessBundle\Entity\OperationSubType::UNILEND_PROMOTIONAL_OPERATION_SPONSORSHIP_REWARD_SPONSEE ?>">
                            <button type="submit" class="btn-primary">Verser prime filleul</button>
                        <?php elseif (\Unilend\Bundle\CoreBusinessBundle\Entity\Sponsorship::STATUS_SPONSEE_PAID == $sponsorship->getStatus()) : ?>
                            <input type="hidden" name="type_reward" value="<?= \Unilend\Bundle\CoreBusinessBundle\Entity\OperationSubType::UNILEND_PROMOTIONAL_OPERATION_SPONSORSHIP_REWARD_SPONSOR ?>">
                            <button type="submit" class="btn-primary">Verser prime parrain</button>
                        <?php endif; ?>
                    </fieldset>
                </form>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- juste une simple table avec ces champs-->
<div>
    <h2>Primes de parrainage déjà distribués</h2>
    ID client du filleul
    Nom Prenom du filleul
    email du filleul
    Montant de la prime du filleul
    Date de versement de la prime filleul
    Code parrain utilisé
    ID client du parrain
    Nom Prenom du parrain
    email du parrain
    Montant de la prime du parrain
    Date de versement de la prime parrain
</div>

<div id="create_sponsorship_link">
    <h2>Créer une relation entre parrain et filleul</h2>
    <?php if (isset($this->createSponsorshipErrors)) : ?>
        <div id="create_offer_errors">
            <?php foreach ($this->createSponsorshipErrors as $error) : ?>
                <span><?= $error ?> </span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" name="create_sponsorship" enctype="multipart/form-data" action="<?= $this->lurl ?>/parrainage/create_sponsorship">
        <fieldset>
            <label for="id_client_sponsor">ID Client parrain</label>
            <input type="number" name="id_client_sponsor" id="id_client_sponsor"/>
            <label for="id_client_sponsee">ID Client filleul</label>
            <input type="number" name="id_client_sponsee" id="id_client_sponsee"/>
            <input type="hidden" name="create_sponsorship" value="">
            <button type="submit" class="btn-primary" id="create_sponsorship">Créer</button>
        </fieldset>
    </form>
    <?php if (isset($this->createSponsorshipData)) : ?>
        ID client Filleul
        Nom Filleul
        Prénom filleul
        Date d'inscription du Filleul
        Date de validation du Filleul
        Filleul a reçu l'offre de bienvenue et ne recevra donc pas de prime de parrainage
        ID client Parrain
        Nom Parrain
        Prénom Parrain
        <form method="post" name="create_sponsorship" enctype="multipart/form-data" action="<?= $this->lurl ?>/parrainage/create_sponsorship">
            <fieldset>
                <input type="hidden" name="create_sponsorship_confirm" value="">
                <input type="hidden" name="id_client_sponsor" id="id_client_sponsor" value="<?= $this->createSponsorshipData['idClientSponsor'] ?>"/>
                <input type="hidden" name="id_client_sponsee" id="id_client_sponsee" value="<?= $this->createSponsorshipData['idClientSponsee'] ?>"/>
                <button type="submit" class="btn-primary" id="create_sponsorship_confirm">Valider la création</button>
            </fieldset>
        </form>
    <?php endif; ?>
</div>