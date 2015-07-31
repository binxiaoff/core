<!--#include virtual="ssi-header.shtml"  -->
		<div class="main">
			<div class="shell">

				<?=$this->fireView('../blocs/depot-de-dossier')?>

				<p><?=$this->lng['etape5']['contenu']?></p>

				<div class="register-form">
					<form action="#" method="post">

						<div class="form-content">
							<p><?=$this->lng['etape5']['documents-a-fournir']?> : </p>
							<div class="rules-list">
								<ul class="unstyled-list">
									<li data-rule="1" <?=($this->companies_details->fichier_extrait_kbis != ''?'class="valid"':'')?>><span id="fichier1">- <?=$this->lng['etape5']['extrait-kbis']?><i class="check-ico"></i></span></li>
                                    <li data-rule="6" <?=($this->companies_details->fichier_cni_passeport != ''?'class="valid"':'')?>><span id="fichier6">- <?=$this->lng['etape5']['cni-passeport']?><i class="check-ico"></i></span></li>
                                    
									<li data-rule="2" <?=($this->companies_details->fichier_rib != ''?'class="valid"':'')?>><span id="fichier2">- <?=$this->lng['etape5']['rib']?><i class="check-ico"></i></span></li>
                                    
                                    <li data-rule="7" <?=($this->companies_details->fichier_derniere_liasse_fiscale != ''?'class="valid"':'')?>><span id="fichier7">- <?=$this->lng['etape5']['derniere-liasse-fiscale']?><i class="check-ico"></i></span></li>
                                    <li data-rule="8" <?=($this->companies_details->fichier_derniers_comptes_approuves != ''?'class="valid"':'')?>><span id="fichier8">- <?=$this->lng['etape5']['derniers-comptes-approuves']?><i class="check-ico"></i></span></li>
                                    <li data-rule="9" <?=($this->companies_details->fichier_derniers_comptes_consolides_groupe != ''?'class="valid"':'')?>><span id="fichier9">- <?=$this->lng['etape5']['derniers-comptes-consolides-groupe']?><i class="check-ico"></i></span></li>
                                    <li data-rule="10" <?=($this->companies_details->fichier_annexes_rapport_special_commissaire_compte != ''?'class="valid"':'')?>><span id="fichier10">- <?=$this->lng['etape5']['annexes-rapport-special-commissaire-compte']?><i class="check-ico"></i></span></li>
                                    
									<li data-rule="3" <?=($this->companies_details->fichier_delegation_pouvoir != ''?'class="valid"':'')?>><span id="fichier3">- <?=($this->companies->status_client == 2?$this->lng['etape5']['delegation-de-pouvoir-obligatoire']:$this->lng['etape5']['delegation-de-pouvoir'])?><i class="check-ico"></i></span></li>
                                    
                                    <li data-rule="11" <?=($this->companies_details->fichier_arret_comptable_recent != ''?'class="valid"':'')?>><span id="fichier11">- <?=$this->lng['etape5']['arret-comptable-recent']?><i class="check-ico"></i></span></li>
                                    <li data-rule="12" <?=($this->companies_details->fichier_budget_exercice_en_cours_a_venir != ''?'class="valid"':'')?>><span id="fichier12">- <?=$this->lng['etape5']['budget-exercice-en-cours-a-venir']?><i class="check-ico"></i></span></li>
                                    
                                    <li data-rule="13" <?=($this->companies_details->fichier_notation_banque_france != ''?'class="valid"':'')?>><span id="fichier13">- <?=$this->lng['etape5']['notation-banque-france']?><i class="check-ico"></i></span></li>
                                    
									<li data-rule="4" <?=($this->companies_details->fichier_logo_societe != ''?'class="valid"':'')?>><span id="fichier4">- <?=$this->lng['etape5']['logo-de-la-societe']?><i class="check-ico"></i></span></li>
									<li data-rule="5" <?=($this->companies_details->fichier_photo_dirigeant != ''?'class="valid"':'')?>><span id="fichier5">- <?=$this->lng['etape5']['photo-du-dirigeant']?><i class="check-ico"></i></span></li>
								</ul>
							</div><!-- /.rules-list -->
						</div><!-- /.form-content -->

						<div class="row tr">
							<a href="<?=$this->lurl?>/thickbox/pop_up_upload/<?=$this->clients->hash?>" class="btn popup-link"><?=$this->lng['etape5']['upload']?></a>
						</div><!-- /.row -->
	
							
						<div class="form-content">
							<p><?=$this->lng['etape5']['contenu-2']?></p>
						</div><!-- /.form-content -->

						<div class="row">
							<div class="cb-holder">
								<label for="accept" ><a style="color:#A1A5A7;" class="check" target="_blank" href="<?=$this->lurl.'/'.$this->tree->getSlug($this->lienConditionsGenerales,$this->language)?>"><?=$this->lng['etape5']['jaccepte-les-conditions-generales-dutilisation']?></a></label>
								<input type="checkbox" name="accept" id="accept" class="custom-input required"> 
							</div><!-- /.cb-holder -->
						</div><!-- /.row -->

						<span class="form-caption"><?=$this->lng['etape5']['champs-obligatoires']?></span>
						<div class="form-foot row row-cols centered">
                        	<input type="hidden" name="send_form_etape_5" />
                        <?
						// Delegation de pouvoir obligatoire
                        if($this->companies->status_client == 2)
						{
							?><button class="btn valid_etape_5" type="<?=($this->companies_details->fichier_extrait_kbis != '' && $this->companies_details->fichier_rib != '' && $this->companies_details->fichier_delegation_pouvoir != '' ?'submit':'button')?>"><?=$this->lng['etape5']['suivant']?><i class="icon-arrow-next"></i></button><?
						}
						else
						{
							?><button class="btn valid_etape_5" type="<?=($this->companies_details->fichier_extrait_kbis != '' && $this->companies_details->fichier_rib != '' ?'submit':'button')?>"><?=$this->lng['etape5']['suivant']?><i class="icon-arrow-next"></i></button><?
						}
						?>
						</div><!-- /.form-foot foot-cols -->

					</form>
				</div><!-- /.register-form -->
			</div><!-- /.shell -->
		</div><!-- /.main -->
<!--#include virtual="ssi-footer.shtml"  -->

<script type="text/javascript">
	$(".valid_etape_5").click(function() {
		if('<?=$this->companies_details->fichier_extrait_kbis?>' == '')
		{
			$("#fichier1").css("color","#C84747");
		}
		if('<?=$this->companies_details->fichier_rib?>' == '')
		{
			$("#fichier2").css("color","#C84747");
		}
		<?
		if($this->companies->status_client == 2)
		{
			?>
			if('<?=$this->companies_details->fichier_delegation_pouvoir?>' == '')
			{
				$("#fichier3").css("color","#C84747");
			}
			<?
		}
		?>
		
		if($('#accept').is(':checked')== true){$(".check").css("color","#A1A5A7");}
		else{$(".check").css("color","#C84747");}
		
	});
</script>