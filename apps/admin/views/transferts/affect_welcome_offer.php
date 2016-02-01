<div id="popup" style="background-color:#FFF;">
<a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img
            src="<?= $this->surl ?>/images/admin/delete.png" alt="Fermer"/></a>
    <div class="row">
    <p>
        Affecter <?= $this->oWelcomeOffer->montant/100 ?> € <br/>
        à <?= (false === empty($this->oCompany->name)) ? $this->oCompany->name: '' .' '.
                $this->oClient->prenom .' '. $this->oClient->nom .'<br/> (ID Prêteur : ' . $this->oLendersAccounts->id_lender_account .')' ?>
    </p>
    </div>
    <div id="affect_welcome_offer">
        <form
            action="<?= $this->lurl ?>/transferts/rattrapage_offre_bienvenue/<?=$this->oClient->id_client.'/'.$this->oWelcomeOffer->id_offre_bienvenue ?>"
            method="post" name="affect_welcome_offer"
            id="affect_welcome_offer">

            <table border="1" style="margin:auto;">
                <tr>
                    <td colspan="2" style="text-align:center;">
                        <button type="submit" name="oui"
                                class="btn btn-medium">Oui
                        </button>
                        <button type="button" id="non"
                                class="btn btn-medium" onclick="parent.$.fn.colorbox.close();">Non
                        </button>
                        <input type="hidden" id="affect_welcome_offer" name="affect_welcome_offer">
                    </td>
                </tr>
            </table>
        </form>
    </div>
</div>