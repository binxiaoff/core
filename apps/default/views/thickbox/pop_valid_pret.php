<style type="text/css">
#non:after{background-color:#BFBFBF;}
#non:before{background-color:#A1A5A7;}
</style>
<div class="popup" style="width: 380px;height:190px;">
	<a href="#" class="popup-close">close</a>
	
	<div class="popup-head">
		<h2>
        	<span class="valider_pret"><?=$this->lng['preteur-projets']['pop-up-bid-confirmation-de-pret']?></span>
            <span class="no_valider_pret" style="display:none;"><?=$this->lng['preteur-projets']['erreur-votre-offre-nest-pas-valide']?></span>
        </h2>
	</div>

	<div class="popup-cnt" style="padding:10px;">
    	
        <div class="no_valid" style="display:none;text-align:center;">
        	<p><?=$this->lng['preteur-projets']['erreur-vous-devez-renseigner-le-taux-du-pret']?></p>
        </div>
         <div class="no_valid_montant_deci" style="display:none;text-align:center;">
        	<p><?=$this->lng['preteur-projets']['erreur-vous-devez-renseigner-un-montant-sans-decimales']?></p>
        </div>
        <div class="no_valid_montant_bas" style="display:none;text-align:center;">
            <p><?=$this->lng['preteur-projets']['erreur-vous-devez-renseigner-une-somme-superieur-ou-egale-a']?> <?=$this->pretMin?> €</p>
        </div>
        <div class="no_valid_montant_solde" style="display:none;text-align:center;">
            <p><?=$this->lng['preteur-projets']['erreur-vous-ne-disposez-pas-dun-solde-suffisant']?></p>
        </div>
        <div class="no_valid_montant_bid" style="display:none;text-align:center;">
            <p><?=$this->lng['preteur-projets']['erreur-vous-ne-pouvez-pas-realiser-une-enchere-couvrant-toute-la-valeur-dun-pret']?></p>
        </div>

        <div class="valider_pret">
        	
            
            
            <p><?=$this->lng['preteur-projets']['pop-up-bid-voulez-vous-preter-a']?> <span id="taux"></span> % <?=$this->lng['preteur-projets']['pop-up-bid-la-somme-de']?> <span id="montant" style="white-space:nowrap;"></span> € ?</p>
            
            <form action="<?=$this->lurl?>/projects/detail/<?=$this->projects->slug?>" method="post" class="form_mdp_lost" name="form_valid_pret" id="form_valid_pret">
            <table border="1" style="margin:auto;">
                <tr>
                    <td colspan="2" style="text-align:center;">
                        <input type="hidden" name="montant_pret" value="" id="montant_pret" >
                        <input type="hidden" name="taux_pret" value="" id="taux_pret" >
                        
                        <input type="hidden" name="send_pret" id="send_pret" value="<?=$this->tokenBid?>" />
                        
                        <button type="submit" name="oui" class="btn btn-medium">OUI</button>
                        <button type="button" id="non" class="btn btn-medium">NON</button>
                    </td>
                </tr>
            </table>
            </form>
        </div>
	</div>
	<!-- /popup-cnt -->

</div>
<script type="text/javascript">
	
	
	
	
	
	var montant_pret = $("#montant_p").val().replace(' ','');
	montant_pret = montant_pret.replace(',','.');
	
	var nb = montant_pret.length;
	var tabMontant_pret = montant_pret.split('');
	var result = true;
	
	for(i=0;i<nb;i++)
	{
		if(tabMontant_pret[i] == '.')
		{
			result = false;
		}
	}

	if($("#tx_p").val() == '-')
	{
		$(".no_valid").show();
		$(".valider_pret").hide();
		
		$(".no_valider_pret").show();
	}
	else if(result == false)
	{
		$(".no_valid_montant_deci").show();
		$(".valider_pret").hide();
		
		$(".no_valider_pret").show();
	}
	else if(montant_pret < <?=$this->pretMin?> || isNaN(montant_pret))
	{
		$(".no_valid_montant_bas").show();
		$(".valider_pret").hide();
		
		$(".no_valider_pret").show();
	}
	else if(<?=$this->solde?> < montant_pret)
	{
		
		$(".no_valid_montant_solde").show();
		$(".valider_pret").hide();
		
		$(".no_valider_pret").show();
	}
	else if(montant_pret >= <?=$this->projects->amount?>)
	{
		$(".no_valid_montant_bid").show();
		$(".valider_pret").hide();
		
		$(".no_valider_pret").show();
	}
	else
	{
		$("#montant").html($("#montant_p").val());
		$("#montant_pret").val($("#montant_p").val());
		
		
		$("#taux").html($("#tx_p").val());
		$("#taux_pret").val($("#tx_p").val());
		
		$(".no_valider_pret").hide();
		
	}




$("#non").click(function() {
	$(".popup-close").click()
});
</script>