<div id="popup" style="width: 390px;">
    <a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?= $this->surl ?>/images/admin/delete.png" alt="Fermer"/></a>
    <form action="<?= $this->lurl ?>/preteurs/offres_de_bienvenue/<?= $this->client->getIdClient() ?>/<?= $this->welcomeOffer->getIdOffreBienvenue() ?>" method="post" class="text-center">
        <h3 style="margin: 30px 0">Affecter <?= $this->welcomeOffer->getMontant() / 100 ?> € à <?= (false === $this->client->isNaturalPerson()) ? $this->company->getName() : '' . ' ' . $this->client->getFirstName() . ' ' . $this->client->getLastName() . '<br/> (ID Client : ' . $this->client->getIdClient() . ')' ?></h3>
        <input type="hidden" id="affect_welcome_offer" name="affect_welcome_offer">
        <button type="button" class="btn-default" onclick="parent.$.fn.colorbox.close();">Annuler</button>
        <button type="submit" class="btn-primary">Valider</button>
    </form>
</div>
