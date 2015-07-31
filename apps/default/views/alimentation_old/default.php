<!--#include virtual="ssi-header-login.shtml"  -->
		<div class="main">
			<div class="shell">
				
				<div class="section-c tabs-c">
					<nav class="tabs-nav">
						<ul>
							<li class="active"><a href="#"><?=$this->lng['preteur-alimentation']['ajouter-des-fonds']?></a></li>
                            <?
							if($this->retrait_ok){
								?><li><a href="#"><?=$this->lng['preteur-alimentation']['transferer-des-fonds']?></a></li><?
							}
							else{
								?><li><a class="popup-link" href="<?=$this->lurl?>/thickbox/pop_up_alerte_retrait"><?=$this->lng['preteur-alimentation']['transferer-des-fonds']?></a></li><?
							}
							?>
							
						</ul>
					</nav>

					<div class="tabs">

						<div class="tab">
							<h2><?=$this->lng['preteur-alimentation']['ajouter-des-fonds']?></h2>
							<p><?=$this->lng['preteur-alimentation']['contenu-ajouter-des-fonds']?></p>
							<div class="info-table">
								
									<table>
										<tr>
											<th style="width: 375px"><?=$this->lng['preteur-alimentation']['type-de-transfert-de-fonds']?></th>
											<th>
												<div class="radio-holder ali">
													<label for="virement1"><?=$this->lng['preteur-alimentation']['virement']?></label>
													<input id="virement1" type="radio" class="custom-input" name="alimentation" value="1" checked="checked">
												</div>
                                                <br />
												<div class="radio-holder ali">
													<label for="cb"><?=$this->lng['preteur-alimentation']['cb']?></label>
													<input id="cb" type="radio" class="custom-input" name="alimentation" value="2">
												</div>
                                                <br />
												<?php /*?><div class="radio-holder ali">
													<label for="prelevement"><?=$this->lng['preteur-alimentation']['prelevement']?></label>
													<input id="prelevement" type="radio" class="custom-input" name="alimentation" value="3">
												</div><?php */?>
											</th>
										</tr>
                                    </table>
                                   
									<div id="contenuVirement">
                                    	
                                        <form action="" method="post">
                                        <br />
                                        <div class="bank-transfer">
                                            <?php /*?><div class="row tc">
                                                <?=$this->lng['preteur-alimentation']['nhesitez-pas-a-consulter-notre-aide-par-banque']?> <a target="_blank" href="<?=$this->aide_par_banque?>" class="btn btn-mini"><?=$this->lng['preteur-alimentation']['decouvrir']?></a>
                                            </div><!-- /.row tc --><?php */?>
                
                                            <div class="bank-data">
                                                
                                                    
                                                    <p class="line-content">
                                                        <span class="label"><b><?=$this->lng['preteur-alimentation']['titulaire-du-compte']?></b></span>
                                                        <span><?=$this->titulaire?></span>
                                                        </p>
                                                        <p class="line-content">
                                                            <span class="label"><b><?=$this->lng['preteur-alimentation']['domiciliation']?></b></span>
                                                            <span><?=$this->domiciliation?></span>
                                                     </p>
                                                    
                                                
                								<br />
                                                <p><b><?=$this->lng['preteur-alimentation']['ref-bancaires']?></b></p>
                
                                                <div class="cols">
                                                    <div class="col">
                                                        <span class="label" style="width:175px;display:inline-block;"><b><?=$this->lng['preteur-alimentation']['code-banque']?></b></span> <span><?=$this->etablissement?></span>
                                                        <br />
                                                        <span class="label" style="width:175px;display:inline-block;"><b><?=$this->lng['preteur-alimentation']['numero-de-compte']?></b></span> <span><?=$this->compte?></span>
                                                    </div><!-- /.col -->
                                                    <div class="col">
                                                        <span class="label" style="width:175px;display:inline-block;"><b><?=$this->lng['preteur-alimentation']['code-guichet']?></b></span> <?=$this->guichet?></span>
                                                        <br />
                                                        <span class="label" style="width:175px;display:inline-block;"><b><?=$this->lng['preteur-alimentation']['cle-rib']?></b></span> <span><?=$this->cle?></span>
                                                    </div><!-- /.col -->
                                                </div><!-- /.cols -->
                								<br />
                                                <span class="label" style="width:175px;display:inline-block;"><b><?=$this->lng['preteur-alimentation']['bic']?></b></span> <span><?=strtoupper($this->bic)?></span>
                                                <p class="line-content">
                                                    <span class="label"><b><?=$this->lng['preteur-alimentation']['iban']?></b></span>
                                                    <?
													for($i=1;$i<=7;$i++)
													{
                                                    ?><span><?=strtoupper($this->iban[$i])?></span><?
													}
													?>
                                                </p>
                
                                                <p class="line-content">
                                                    <span class="label"><b><?=$this->lng['preteur-alimentation']['motif']?></b> <i class="icon-help tooltip-anchor" data-placement="right" title="<?=$this->lng['preteur-alimentation']['motif-description']?>"></i></span>
                                                    <span><b style="color: #B10366;"><?=$this->motif?></b></span>
                                                </p>
                                                <p><i style="color: #B10366;font-size:12px;"><?=$this->lng['preteur-alimentation']['contenu-motif']?></i></p>
                                            </div><!-- /.bank-data -->
                                        </div>
                                        <br />
                                        <div class="row">
                                            <div class="cb-holder">
                                                    <label class="check" for="accept-cgu"><a style="color:#A1A5A7;" class="check" target="_blank" href="<?=$this->lurl.'/'.$this->tree->getSlug($this->lienConditionsGenerales,$this->language)?>"><?=$this->lng['etape3']['jaccepte-les-cgu-dunilend']?></a></label>
                                                    <input type="checkbox" class="custom-input required" name="accept-cgu" id="accept-cgu">
                                            </div><!-- /.cb-holder -->
                                        </div><!-- /.row -->
                                        
                                        
                                        <input type="hidden" name="sendVirement" />
                                        <button class="btn btnAlimentation" type="submit" ><?=$this->lng['preteur-alimentation']['valider']?></button>
                                        </form>     
                                	</div>
                                    
                                    <div id="contenuCb" style="display:none;">
                                    	<br />
                                        
                                    	<form action="" method="post" id="form_sendPaymentCb" name="form_sendPaymentCb">
                                            <div class="row">
                                                <div class="form-choose">
                                                    <span class="title"><b><?=$this->lng['preteur-alimentation']['fonds']?></b></span>
                                                    <input type="text" class="field field-small required" value="" name="amount" id="amount" onkeyup="lisibilite_nombre(this.value,this.id);"/>
                                                </div><!-- /.form-choose -->
                                            </div><!-- /.row -->
                
                                            <div class="row">
                                                <div class="cards">
                                                    <span class="inline-text"><b><?=$this->lng['preteur-alimentation']['carte-de-credit']?></b></span>
                                                    <img src="<?=$this->surl?>/styles/default/images/mastercard.png" alt="mc" width="96" height="60">
                                                    <img src="<?=$this->surl?>/styles/default/images/ob.png" alt="cb" width="92" height="60">
                                                    <img src="<?=$this->surl?>/styles/default/images/visa.png" alt="visa" width="97" height="60">
                                                </div><!-- /.cards -->
                                            </div><!-- /.row -->
                                            
                                            <div class="row">
                                                <div class="cb-holder">
                                                        <label class="check" for="accept-cgu"><a style="color:#A1A5A7;" class="check" target="_blank" href="<?=$this->lurl.'/'.$this->tree->getSlug($this->lienConditionsGenerales,$this->language)?>"><?=$this->lng['etape3']['jaccepte-les-cgu-dunilend']?></a></label>
                                                        <input type="checkbox" class="custom-input required" name="accept-cgu" id="accept-cgu">
                                                </div><!-- /.cb-holder -->
                                            </div><!-- /.row -->
                                            
                                            <br />
                                            <input type="hidden" name="sendPaymentCb" />
                                            <button class="btn btnAlimentation" type="submit" ><?=$this->lng['preteur-alimentation']['valider']?></button>
                                        </form>
                                    </div><!-- /.card-payment -->
                                    
                                    <div id="contenuPrelevement" style="display:none;">
                                    	<div class="tab-form" style="width:660px;">
                                            <form action="" method="post">
                                                <div class="row clearfix">
                                                    <p class="left"><?=$this->lng['preteur-alimentation']['informations-prelevement']?></p>
                                                </div>
 
                                                <div class="row">
                                                	<p class="left"><?=$this->lng['preteur-alimentation']['montant-du-prelevement']?></p>
                                                    <p class="right"><input type="text" name="montant_prelevement" title="500 €" value="500 €" class="field field-large"></p>
                                                </div>
                                                <div class="row">
                                                	<p class="left"><?=$this->lng['preteur-alimentation']['societe-creanciere']?></p>
                                                    <p class="right"><?=$this->lng['preteur-alimentation']['unilend']?></p>
                                                </div>
                                                <div class="row">
                                                	<p class="left"><?=$this->lng['preteur-alimentation']['coordonnees-bancaires']?></p>
                                                    <p class="right"><input type="text" name="rib_prelevement" title="RIB" value="RIB" class="field field-large"></p>
                                                </div>
                                                <div class="row">
                                                	<p class="left"></p>
                                                    <p class="right"><input type="text" name="iban_prelevement" title="IBAN" value="IBAN" class="field field-large"></p>
                                                </div>
                                                <div class="row">
                                                	<p class="left"><?=$this->lng['preteur-alimentation']['type-de-prelevement']?></p>
                                                    <div class="right">
                                                        <div class="radio-holder ali">
                                                            <label for="permanent"><?=$this->lng['preteur-alimentation']['permanent']?></label>
                                                            <input id="permanent" type="radio" class="custom-input" name="type_prelevement" value="1" checked="checked">
                                                        </div>
                                                        <div class="radio-holder ali">
                                                            <label for="ponctuel"><?=$this->lng['preteur-alimentation']['ponctuel']?></label>
                                                            <input id="ponctuel" type="radio" class="custom-input" name="type_prelevement" value="2">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                	<p class="left"><?=$this->lng['preteur-alimentation']['jour-du-prelevement']?></p>
                                                    <p class="right">
                                                    <select name="jour_prelevement" class="custom-select field-large">
                                                        <option value="1">1</option>
														<?
                                                        for($i=1;$i<=28;$i++){
                                                        	?><option value="<?=$i?>"><?=$i?></option><?
														}
														?>
                                                </select>
                                                    </p>
                                                </div>
                                                <div class="row">
                                                	<p class="left"><?=$this->lng['preteur-alimentation']['date-de-la-demande']?></p>
                                                    <p class="right"><?=date('d/m/Y')?></p>
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="cb-holder">
                                                            <label class="check" for="accept-cgu"><a style="color:#A1A5A7;" class="check" target="_blank" href="<?=$this->lurl.'/'.$this->tree->getSlug($this->lienConditionsGenerales,$this->language)?>"><?=$this->lng['etape3']['jaccepte-les-cgu-dunilend']?></a></label>
                                                            <input type="checkbox" class="custom-input required" name="accept-cgu" id="accept-cgu">
                                                    </div><!-- /.cb-holder -->
                                                </div><!-- /.row -->
                                                
                                                <input type="hidden" name="sendPrelevement" />
                                            	<button class="btn btnAlimentation" type="submit" ><?=$this->lng['preteur-alimentation']['valider']?></button>
                                            </form>
                                        </div>
                                    </div>
                                    
							</div>
                            
						</div><!-- /.tab -->
						
						<div class="tab">
							<h2><?=$this->lng['preteur-alimentation']['transferer-des-fonds']?></h2>
							<p><?=$this->lng['preteur-alimentation']['contenu-transferer-des-fonds']?></p>
                            
							<p><?=$this->lng['preteur-alimentation']['vous-avez-actuellement']?> <span><?=number_format($this->solde, 2, ',', ' ')?> €</span> <?=$this->lng['preteur-alimentation']['de-disponible-sr-votre-compte-unilend.']?></p>
							<p class="reponse" style="text-align:center;color:green;display:none;"><?=$this->lng['preteur-alimentation']['demande-de-transfert-de-fonds-en-cours']?></p>
                            <p class="noBicIban" style="text-align:center;color:#C84747;display:none;"><?=$this->lng['preteur-alimentation']['erreur-bic-iban']?></p>
                            <div class="tab-form">
								<form action="#" method="post">
									<div class="row clearfix">
										<p class="left"><?=$this->lng['preteur-alimentation']['nom-du-titulaire-du-compte']?></p>
										<p class="right"><?=$this->clients->prenom?> <?=$this->clients->nom?></p>
									</div>
									<div class="row clearfix">
										<p class="left"><?=$this->lng['preteur-alimentation']['numero-de-compte-2']?></p>
										<p class="right"><?=$this->clients->id_client?></p>
									</div>
                                    <?
									if($this->retrait_ok){
										?>
										<div class="row">
											 <span class="pass-field-holder">
												<input type="password" id="mot-de-passe" title="<?=$this->lng['preteur-alimentation']['mot-de-passe']?>" value="" class="field field-large required" data-validators="Presence" autocomplete="off">
											</span>
										</div>
										<div class="row">
											<input type="text" id="montant" title="<?=$this->lng['preteur-alimentation']['montant']?>" value="<?=$this->lng['preteur-alimentation']['montant']?>" class="field field-large required" data-validators="Presence" autocomplete="off">
										</div>
										
										<em><?=$this->lng['preteur-alimentation']['champs-obligatoires']?></em>
										<button class="btn" type="button" onclick="transfert('<?=$this->clients->id_client?>');"><?=$this->lng['preteur-alimentation']['valider']?></button>
										<?
									}
									else{
										?>
                                        <div class="row">
                                             <span class="pass-field-holder">
                                                <input disabled="disabled" type="text" title="<?=$this->lng['preteur-alimentation']['mot-de-passe']?>" value="<?=$this->lng['preteur-alimentation']['mot-de-passe']?>" class="field field-large">
                                            </span>
                                        </div>
                                        <div class="row">
                                            <input disabled="disabled" type="text" title="<?=$this->lng['preteur-alimentation']['montant']?>" value="<?=$this->lng['preteur-alimentation']['montant']?>" class="field field-large">
                                        </div>
                                        
                                       
                                        <?	
									}
									?>
								</form>
							</div>
						</div><!-- /.tab -->
						
					</div>

				</div><!-- /.tabs-c -->
				
			</div>
		</div>
		
<!--#include virtual="ssi-footer.shtml"  -->
 <script type="text/javascript">
 
 	$('#amount').change(function() {
		var amount = $("#amount").val().replace(',','.');
		amount = amount.replace(' ','');
		
		var val_amount = true;
		if(isNaN(amount) == true){ val_amount = false }
		else if(amount > 10000 || amount < 20){ val_amount = false }
		
		if(val_amount == false){$(this).addClass('LV_invalid_field');$(this).removeClass('LV_valid_field');}
		else{$(this).addClass('LV_valid_field');$(this).removeClass('LV_invalid_field');}
	});
 
	$(".ali" ).click(function() {
		var val = $('input[type=radio][name=alimentation]:checked').attr('value');
		if(val == 1){
			$('#contenuVirement').show();
			$('#contenuCb').hide();
			$('#contenuPrelevement').hide();
		}
		else if(val == 2){
			$('#contenuVirement').hide();
			$('#contenuCb').show();
			$('#contenuPrelevement').hide();
		}
		else{
			$('#contenuVirement').hide();
			$('#contenuCb').hide();
			//$('#contenuPrelevement').show();
			$('#contenuPrelevement').hide();
		}
	});
	
	$("#mot-de-passe").change(function() {
		if($("#mot-de-passe").val() == ''){$(this).addClass('LV_invalid_field');$(this).removeClass('LV_valid_field');}
		else{$(this).addClass('LV_valid_field');$(this).removeClass('LV_invalid_field');}
		
	});
	$("#montant").change(function() {
		if($("#montant").val() == ''){$(this).addClass('LV_invalid_field');$(this).removeClass('LV_valid_field');}
		else{$(this).addClass('LV_valid_field');$(this).removeClass('LV_invalid_field');}
		
	});
	
	
	
	
	$("#form_sendPaymentCb").submit(function( event ) {
		
		var amount = $("#amount").val().replace(',','.');
		amount = amount.replace(' ','');
		
		var form_ok = true;
		
		var val_amount = true;
		if(isNaN(amount) == true){ val_amount = false }
		else if(amount > 10000 || amount < 20){ val_amount = false }
		
		if(val_amount == false){form_ok = false;$("#amount").addClass('LV_invalid_field');$("#amount").removeClass('LV_valid_field');}
		else{$("#amount").addClass('LV_valid_field');$("#amount").removeClass('LV_invalid_field');}
		
		
		
		if(form_ok == false){ event.preventDefault(); }
	});
</script>
