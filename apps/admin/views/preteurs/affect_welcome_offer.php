<div id="popup" style="width: 250px;">
    <a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?= $this->surl ?>/images/admin/delete.png" alt="Fermer"/></a>
    <div class="row">
        <p>
            Affecter <?= $this->welcomeOffer->getMontant() / 100 ?> € <br/>
            à <?= (false === $this->client->isNaturalPerson()) ? $this->company->getName() : '' . ' ' . $this->client->getPrenom() . ' ' . $this->client->getNom() . '<br/> (ID Client : ' . $this->client->getIdClient() . ')' ?>
        </p>
    </div>
    <div id="affect_welcome_offer">
        <form action="<?= $this->lurl ?>/preteurs/offres_de_bienvenue/<?= $this->client->getIdClient() ?>/<?= $this->welcomeOffer->getIdOffreBienvenue() ?>"
              method="post" name="affect_welcome_offer" id="affect_welcome_offer">
            <table style="margin:auto;">
                <tr>
                    <td colspan="2" style="text-align:center;">
                        <button type="submit" name="oui" class="btn btn-medium">Oui</button>
                        <button type="button" id="non" class="btn btn-medium" onclick="parent.$.fn.colorbox.close();">Non</button>
                        <input type="hidden" id="affect_welcome_offer" name="affect_welcome_offer">
                    </td>
                </tr>
            </table>
        </form>
    </div>
</div>
