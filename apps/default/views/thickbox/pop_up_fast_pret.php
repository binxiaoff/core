<style type="text/css">
#preter_btn_non:after{background-color:#BFBFBF;}
#preter_btn_non:before{background-color:#A1A5A7;}
</style>

<div class="popup" style="width: 255px;height:330px;">
	<a href="#" class="popup-close">close</a>

	<div class="popup-head">
		<h2>
			<span class="valider_pret"><?=$this->lng['preteur-projets']['faire-une-offre']?></span>
        	<span class="no_valider_pret" style="display:none;">Votre offre n'est pas valide</span>
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
            <p><?=$this->lng['preteur-projets']['erreur-vous-devez-renseigner-une-somme-superieur-ou-egale-a']?><?=$this->pretMin?> €</p>
        </div>
        <div class="no_valid_montant_solde" style="display:none;text-align:center;">
            <p><?=$this->lng['preteur-projets']['erreur-vous-ne-disposez-pas-dun-solde-suffisant']?></p>
        </div>
        <div class="no_valid_montant_bid" style="display:none;text-align:center;">
            <p><?=$this->lng['preteur-projets']['erreur-vous-ne-pouvez-pas-realiser-une-enchere-couvrant-toute-la-valeur-dun-pret']?></p>
        </div>


		<form action="<?=$this->lurl?>/projects/detail/<?=$this->projects->slug?>/fast" method="post" id="form_pret_fast" name="form_pret_fast">
        <table border="1" style="margin:auto;">
        	<tr class="champs_pret" style="height: 60px;">
            	<td style="width:110px;"><label><?=$this->lng['preteur-projets']['je-prete-a']?></label></td>
            	<td>
                <select name="taux_pret" id="taux_pret" class="custom-select field-hundred field-extra-tiny">
                    <?php foreach (range($this->rateRange['rate_max'], $this->rateRange['rate_min'], -0.1) as $fRate) { ?>
                        <?php if ($this->soldeBid < $this->projects->amount || round($fRate, 1) < round($this->txLenderMax, 1)) { ?>
                            <option value="<?= $fRate ?>"><?= $this->ficelle->formatNumber($fRate, 1) ?>&nbsp;%</option>
                        <?php } ?>
                    <?php } ?>
                </select>
                </td>
			</tr>
			<tr class="champs_pret" style="height: 60px;">
            	<td style="width:110px;"><label><?=$this->lng['preteur-projets']['la-somme-de']?></label></td>
            	<td><input name="montant_pret" id="montant_pret" type="text" title="<?=$this->lng['preteur-projets']['montant-exemple']?>" value="<?=$this->lng['preteur-projets']['montant-exemple']?>" class="field field-extra-tiny" onkeyup="lisibilite_nombre(this.value,this.id);"/></td>
			</tr>
            <tr>
            	<td colspan="2" style="text-align:center;">
                	<input type="hidden" name="send_pret" id="send_pret" value="<?=$this->tokenBid?>" />
                    <button type="button" id="preter_btn" class="btn btn-medium"><?=$this->lng['preteur-projets']['preter']?></button>

                    <div style="display:none" class="preter_btn_conf">
                    <p><?=$this->lng['preteur-projets']['pop-up-bid-voulez-vous-preter-a']?> <span class="tx"></span> % <?=$this->lng['preteur-projets']['pop-up-bid-la-somme-de']?> <span class="montant"></span> € ?</p>
                    <button type="button" id="preter_btn_oui" class="btn btn-medium"><?=$this->lng['preteur-projets']['pop-up-bid-oui']?></button>
                    <button type="button" id="preter_btn_non" class="btn btn-medium"><?=$this->lng['preteur-projets']['pop-up-bid-non']?></button>
        			</div>
                </td>
            </tr>
        </table>
        </form>
	</div>
	<!-- /popup-cnt -->

</div>

<script type="text/javascript">
$("#preter_btn").click(function() {

	var montant_pret = $("#montant_pret").val().replace(' ','');
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


	$(".montant").html($("#montant_pret").val());
	$(".tx").html($("#taux_pret").val())

	var form_ok = true;

	if($("#taux_pret").val() == '-')
	{
		$(".no_valid").slideDown();
		$(".no_valid_montant_bas").slideUp();
		$(".no_valid_montant_deci").slideUp();
		$(".no_valid_montant_solde").slideUp();
		$(".no_valid_montant_bid").slideUp();

		form_ok = false;



	}
	else if(result == false)
	{
		$(".no_valider_pret").slideDown();
		$(".valider_pret").hide();

		$(".no_valid").slideUp();
		$(".no_valid_montant_bas").slideUp();
		$(".no_valid_montant_deci").slideDown();
		$(".no_valid_montant_solde").slideUp();
		$(".no_valid_montant_bid").slideUp();

		form_ok = false;
	}
	else if(montant_pret < <?=$this->pretMin?> || isNaN(montant_pret) == true)
	{
		$(".no_valider_pret").slideDown();
		$(".valider_pret").hide();

		$(".no_valid").slideUp();
		$(".no_valid_montant_bas").slideDown();
		$(".no_valid_montant_deci").slideUp();
		$(".no_valid_montant_solde").slideUp();
		$(".no_valid_montant_bid").slideUp();
		form_ok = false;
	}
	else if(<?=$this->solde?> < montant_pret)
	{
		$(".no_valider_pret").slideDown();
		$(".valider_pret").hide();

		$(".no_valid").slideUp();
		$(".no_valid_montant_bas").slideUp();
		$(".no_valid_montant_deci").slideUp();
		$(".no_valid_montant_solde").slideDown();
		$(".no_valid_montant_bid").slideUp();
		form_ok = false;
	}
	else if(montant_pret >= <?=$this->projects->amount?>)
	{
		$(".no_valider_pret").slideDown();
		$(".valider_pret").hide();

		$(".no_valid").slideUp();
		$(".no_valid_montant_bas").slideUp();
		$(".no_valid_montant_deci").slideUp();
		$(".no_valid_montant_solde").slideUp();
		$(".no_valid_montant_bid").slideDown();
		form_ok = false;
	}
	else
	{
		$(".no_valider_pret").slideUp();
		$(".valider_pret").slideDown();

		$(".no_valid").slideUp();
		$(".no_valid_montant_bas").slideUp();
		$(".no_valid_montant_deci").slideUp();
		$(".no_valid_montant_solde").slideUp();
		$(".no_valid_montant_bid").slideUp();
		$(".champs_pret").hide();
		$("#preter_btn").hide();

		$(".preter_btn_conf").slideDown();

	}


});


$("#preter_btn_non").click(function() {
	$("#preter_btn").show();
	$(".champs_pret").show();
	$(".preter_btn_conf").hide();
});

$("#preter_btn_oui").click(function() {
	$("#form_pret_fast").submit()
});

</script>