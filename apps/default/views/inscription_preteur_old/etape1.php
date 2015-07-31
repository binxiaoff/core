<div class="main form-page">
    <div class="shell">

		<?=$this->fireView('../blocs/inscription-preteur')?>
        
        <p><?=$this->lng['etape1']['contenu']?></p>
        
        <?
		if($this->emprunteurCreatePreteur == false){
			?>
			<div id="content_type_personne">
				<div class="row">
					<div class="form-choose fixed">
						<span class="title"><?=$this->lng['etape1']['vous-etes']?></span>
		
						<div class="radio-holder" id="lab_radio1">
							<label for="typePersonne-1">
								<?=$this->lng['etape1']['particulier']?>
							   <?php /*?> <i class="icon-help tooltip-anchor" data-placement="right" title="" data-original-title="Pour vous être sûr de vous reconnaître si vous perdez votre mot de passe, nous vous invitons à choisir une question personnelle dont vous êtes sûr de connaître la réponse. Cela nous permettra de vous identifier en toute sécurité."></i><?php */?>
							</label>
							<input <?=($this->modif == false?'checked':($this->modif == true && in_array($this->clients->type,array(1,3))?'checked':''))?> type="radio" class="custom-input" name="typePersonne" id="typePersonne-1" value="1">
						</div><!-- /.radio-holder -->
		
						<div class="radio-holder" id="lab_radio2">
							<label for="typePersonne-2">
								<?=$this->lng['etape1']['societe']?>
								<?php /*?><i class="icon-help tooltip-anchor" data-placement="right" title="" data-original-title="Pour vous être sûr de vous reconnaître si vous perdez votre mot de passe, nous vous invitons à choisir une question personnelle dont vous êtes sûr de connaître la réponse. Cela nous permettra de vous identifier en toute sécurité."></i><?php */?>
							</label>
		
							<input <?=($this->modif == true && in_array($this->clients->type,array(2,4))?'checked':'')?> type="radio" class="custom-input" name="typePersonne" id="typePersonne-2" value="2">
						</div><!-- /.radio-holder -->
					</div><!-- /.form-choose -->
				</div><!-- /.row -->
			</div>
			<?
		}
		?>
		
        <span style="text-align:center; color:#C84747;"><?=$this->messageDeuxiemeCompte?></span>
        <span style="text-align:center; color:#C84747;"><?=$this->reponse_email?></span>
        <span style="text-align:center; color:#C84747;"><?=$this->reponse_age?></span>
        
		<div class="register-form">
        	<?
			if($this->emprunteurCreatePreteur == false)
			{
				?>
				<div class="particulier">
					<?=$this->fireView('particulier_etape_1')?>
				</div>
				<?
			}
			?>
            <div class="societe">
            	<?=$this->fireView('societe_etape_1')?>
            </div>
            <script>
			<?
			// si modif et que c'est une societe
			if($this->modif == true && in_array($this->clients->type,array(2,4)))
			{
				?>
				// Hide particulier
				$(".particulier" ).hide();
				$(".societe" ).show();
				<?
			}
			?>
			</script>
        </div><!-- /.register-form -->
    </div><!-- /.shell -->
</div>
<script type="text/javascript">

$(window).load(function() {
<?
//echo $this->clients->type;
if($this->emprunteurCreatePreteur == false && $this->modif == false || $this->modif == true && in_array($this->clients->type,array(1,3)))
{
	?>
	// hide societe
	$(".societe" ).hide();
	<?
}

?>

});

$(document).ready(function () {
  
  
  	// mdp controle particulier
  	$('#pass').keyup(function() { controleMdp($(this).val(),'pass'); });
  	// mdp controle particulier
	$('#pass').blur(function() { controleMdp($(this).val(),'pass'); });
	
	// mdp controle societe
  	$('#passE').keyup(function() { controleMdp($(this).val(),'passE'); });
  	// mdp controle societe
	$('#passE').blur(function() { controleMdp($(this).val(),'passE'); });
  
	// confirmation mdp particulier
	$('#pass2').bind('paste', function (e) { e.preventDefault(); });
	// confirmation email preteur particulier
	$('#conf_email').bind('paste', function (e) { e.preventDefault(); });
	$('#email').bind('paste', function (e) { e.preventDefault(); });
	
	// confirmation email preteur societe
	$('#conf_email_inscription').bind('paste', function (e) { e.preventDefault(); });
	$('#email_inscription').bind('paste', function (e) { e.preventDefault(); });
	
	// confirmation mpd societe
	$('#passE2').bind('paste', function (e) { e.preventDefault(); });
  
  
	$('select#external-consultant').on('change', function() {
		if ($('option:selected', this).val() == '3') {$('#autre_inscription').show();}
		else {$('#autre_inscription').hide();}
	});
	
	////////////////////////////////////////////
	$( "#jour_naissance, #mois_naissance, #annee_naissance" ).change(function() {
		var d = $('#jour_naissance').val();
		var m = $('#mois_naissance').val();
		var y = $('#annee_naissance').val();
		
		$.post( add_url+"/ajax/controleAge", { d: d,m:m,y:y }).done(function( data ) {
			if(data == 'ok'){ $(".check_age").html('true'); $(".error_age").slideUp(); }
			else{ $(".check_age").html('false'); $(".error_age").slideDown(); }
		});
	});
	////////////////////////////////////////////////////
	
});

// display particulier
$( "#lab_radio1" ).click(function() {
	$(".societe" ).hide();
	$(".particulier" ).show();
});
// display societe
$( "#lab_radio2" ).click(function() {
	$(".particulier" ).hide();
	$(".societe" ).show();
	
});

// Submit formulaire inscription preteur particulier
$( "#form_inscription_preteur_particulier_etape_1" ).submit(function( event ) {
	var radio = true;
	
	// date de naissance
	if($(".check_age").html() == 'false'){
		radio = false;
		/*if($("#jour_naissance").val() == ''){ $("#jour_naissance").next('.c2-sb-wrap').addClass('field-error'); }
		if($("#mois_naissance").val() == ''){ $("#mois_naissance").next('.c2-sb-wrap').addClass('field-error'); }
		if($("#annee_naissance").val() == ''){ $("#annee_naissance").next('.c2-sb-wrap').addClass('field-error'); }*/
	}
	
	// listes pays / nationalite
	/*if($("#nationalite").val() == ''){ $("#nationalite").next('.c2-sb-wrap').addClass('field-error');radio = false; }
	if($("#pays1").val() == ''){ $("#pays1").next('.c2-sb-wrap').addClass('field-error');radio = false; }
	if($("#pays2").val() == ''){ $("#pays2").next('.c2-sb-wrap').addClass('field-error');radio = false; }
	if($("#pays3").val() == ''){ $("#pays3").next('.c2-sb-wrap').addClass('field-error');radio = false; }*/
	
	// Civilite
	if($('input[type=radio][name=sex]:checked').length){$('#radio_sex').css('color','#727272');$('#female').removeClass('LV_invalid_field');}
	else{
		$('#radio_sex').css('color','#C84747');radio = false;
		$('#female').addClass('LV_invalid_field');
	 }
	
	// cgu
	if($('#accept-cgu').is(':checked') == false){$('.check').css('color','#C84747');radio = false}
	else{$('.check').css('color','#727272');}
	
	// controle mdp
	if(controleMdp($('#pass').val(),'pass') == false){radio = false}
	
	
	if($('#jour_naissance').val() == '<?=$this->lng['etape1']['jour']?>'){$("#jour_naissance").removeClass("LV_valid_field");$("#jour_naissance").addClass("LV_invalid_field");}
	else {$("#jour_naissance").removeClass("LV_invalid_field");$("#jour_naissance").addClass("LV_valid_field");}
	
	if(radio == false){ 
	 
		event.preventDefault(); 
		/*if($(".LV_invalid_field").length){
			$('html,body').animate({scrollTop: $(".LV_invalid_field").offset().top}, 'slow');
		}*/	
	}
});

// Submit formulaire inscription preteur societe
$( "#form_inscription_preteur_societe_etape_1" ).submit(function( event ) {	
	var radio = true;
	
	// pays
	/*if($("#pays1E").val() == ''){ $("#pays1E").next('.c2-sb-wrap').addClass('field-error');radio = false; }
	if($("#pays2E").val() == ''){ $("#pays2E").next('.c2-sb-wrap').addClass('field-error');radio = false; }*/
	
	// Civilite vos cordonnées
	if($('input[type=radio][name=genre1]:checked').length){$('#radio_genre1').css('color','#727272');}
	else{$('#radio_genre1').css('color','#C84747');radio = false}
	
	// type d'utilisateur
	var radio_enterprise = $('input[type=radio][name=enterprise]:checked').attr('value');
	
	if(radio_enterprise == 2 || radio_enterprise == 3 ){
		if($('input[type=radio][name=genre2]:checked').length){$('#radio_genre2').css('color','#727272');}
		else{$('#radio_genre2').css('color','#C84747');radio = false}	
	}
	else $('#radio_genre2').css('color','#727272');
	
	// cgu
	if($('#accept-cgu-societe').is(':checked') == false){$('.check-societe').css('color','#C84747');radio = false}
	else{$('.check-societe').css('color','#727272');}
	
	<?
	if($this->emprunteurCreatePreteur == false)
	{
		?>
		// controle mdp
		if(controleMdp($('#passE').val(),'passE') == false){radio = false}
		<?
	}
	?>
	
		//radio = false;
	if(radio == false){ event.preventDefault(); }	
});



</script>