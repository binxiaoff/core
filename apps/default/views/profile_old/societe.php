<style type="text/css">
	.tabs .tab{display:block;}
	.field-large {width: 422px;}
	.tab .form-choose{margin-bottom:0;}
	.form-page form .row .pass-field-holder {width: 460px;}
	.euro-field.field-large{padding-left:22px;}
</style>

<!--#include virtual="ssi-header-login.shtml"  -->
<div class="main form-page account-page account-page-personal">
    <div class="shell">
        
        <div class="section-c tabs-c">
            <nav class="tabs-nav">
                <ul class="navProfile">
                    <li <?=(!isset($this->params[0])?'class="active"':'')?>>
                        <a id="perso" href="#"><?=$this->lng['profile']['titre-1']?></a>
                    </li>
                    <li <?=(isset($this->params[0]) && $this->params[0] == 2?'class="active"':'')?> >
                        <a id="bank" href="#"><?=$this->lng['profile']['titre-2']?></a>
                    </li>
                    <li <?=(isset($this->params[0]) && $this->params[0] == 3?'class="active"':'')?> >
                        <a id="secu" href="#"><?=$this->lng['profile']['titre-3']?></a>
                    </li>
                </ul>
            </nav>

            <div class="tabs">
                <div class="tab page1">
                    <?=$this->fireView('societe_perso')?>	
                </div><!-- /.tab -->

                <div class="tab page2">
                    <?=$this->fireView('societe_bank')?>
                </div><!-- /.tab -->

                <div class="tab page3">
                    <?=$this->fireView('secu')?>
                </div>
            </div>

        </div><!-- /.tabs-c -->

    </div>
</div>		
<!--#include virtual="ssi-footer.shtml"  -->
<script type="text/javascript">

$(window).load(function() {
	<?
	if(isset($this->params[0]) && $this->params[0] > 1 && $this->params[0] <= 3){
		for($i=1;$i<=3;$i++){ if($this->params[0] != $i){ ?>$(".page<?=$i?>" ).hide();<? }}
	}
	else{ ?> $(".page2" ).hide(); $(".page3" ).hide();<? }
	?>
});
	
$(document).ready(function () {
	$('#conf_email').bind('paste', function (e) { e.preventDefault(); });
	// confirmation email preteur societe
	$('#conf_email_inscription').bind('paste', function (e) { e.preventDefault(); });
	$('#email_inscription').bind('paste', function (e) { e.preventDefault(); });
	
	// mdp controle particulier
	$('#passNew').keyup(function() { controleMdp($(this).val(),'passNew'); });
	// mdp controle particulier
	$('#passNew').blur(function() { controleMdp($(this).val(),'passNew'); });
	
	$('select#external-consultant').on('change', function() {
		if ($('option:selected', this).val() == '3') {$('#autre_inscription').show();}
		else {$('#autre_inscription').hide();}
	});
});
	
// Submit formulaire inscription preteur societe
$( "#form_societe_perso" ).submit(function( event ) {	
	var radio = true;
	
	// Civilite vos cordonnées
	if($('input[type=radio][name=genre1]:checked').length){$('#radio_genre1').css('color','#727272');}
	else{$('#radio_genre1').css('color','#C84747');radio = false;}
	
	// type d'utilisateur
	var radio_enterprise = $('input[type=radio][name=enterprise]:checked').attr('value');
	
	if(radio_enterprise == 2 || radio_enterprise == 3 ){
		if($('input[type=radio][name=genre2]:checked').length){$('#radio_genre2').css('color','#727272');}
		else{$('#radio_genre2').css('color','#C84747');radio = false;}	
	}
	else $('#radio_genre2').css('color','#727272');
	
	if(radio == false){ event.preventDefault(); }

});

// secu
	
$("#form_mdp").submit(function( event ) {
	
	var form_ok = true;
	var question = $("#secret-question");
	var reponse = $("#secret-response");
	var newpass = $('#passNew');
	
	// question secrete /reponse secrete
	if(question.val() != '' && question.val() != question.attr('title') || reponse.val() != '' && reponse.val() != reponse.attr('title')){
		// reponse vide
		if(reponse.val() == '' || reponse.val() == reponse.attr('title')){
			reponse.addClass('LV_invalid_field');reponse.removeClass('LV_valid_field');form_ok = false; }
		else { reponse.addClass('LV_valid_field');reponse.removeClass('LV_invalid_field'); }
		// question vide
		if(question.val() == '' || question.val() == question.attr('title')){ 
			question.addClass('LV_invalid_field');question.removeClass('LV_valid_field');form_ok = false;}
		else { question.addClass('LV_valid_field');question.removeClass('LV_invalid_field'); }
	}
	// controle mdp
	if(controleMdp(newpass.val(),'passNew') == false){form_ok = false}
	
	if(form_ok == false){event.preventDefault(); }
});

// info bank

// BIC
$("#bic").keyup(function() { check_bic($(this).val()); });
$("#bic").change(function() { check_bic($(this).val()); if($(this).val() != "<?=$this->lenders_accounts->bic?>"){$(".change_bank").fadeIn(); $('#txt_rib').val('');}});

// IBAN
for(var i=2;i<=7;i++){ $('#iban-'+i).change(function() { $(".change_bank").fadeIn(); $('#txt_rib').val(''); });	}

$('#iban-1').change(function() {
	if($("#iban-1").val().substring(0,2) != 'FR'){	
		$("#iban-1").addClass('LV_invalid_field');$("#iban-1").removeClass('LV_valid_field');
		$(".error_iban").html('<?=$this->lng['etape2']['iban-erreur-2']?>');
		$(".error_iban").slideDown();
	}
	else{ 
		$("#iban-1").addClass('LV_valid_field');$("#iban-1").removeClass('LV_invalid_field');
		$(".error_iban").slideUp();
	}
});

// formulaire informations bancaires
$("#form_bank_societe").submit(function( event ) {
	var form_ok = true;
	
	// origine
	if($('#origine_des_fonds').val() == 0){
		$('#origine_des_fonds').addClass('LV_invalid_field');$('#origine_des_fonds').removeClass('LV_valid_field');
		form_ok = false;
	}
	else{$('#origine_des_fonds').addClass('LV_valid_field');$('#origine_des_fonds').removeClass('LV_invalid_field');}
	
	// fichiers
	// ci_dirigeant
	if($('#txt_ci_dirigeant').val() == '<?=$this->lng['etape2']['aucun-fichier-selectionne']?>'){
		form_ok = false
		$("#txt_ci_dirigeant").removeClass("LV_valid_field");$("#txt_ci_dirigeant").addClass("LV_invalid_field");
	}
	else {$("#txt_ci_dirigeant").removeClass("LV_invalid_field");$("#txt_ci_dirigeant").addClass("LV_valid_field");}
	// rib
	if($('#txt_rib').val() == '<?=$this->lng['etape2']['aucun-fichier-selectionne']?>'){
		form_ok = false
		$("#txt_rib").removeClass("LV_valid_field");$("#txt_rib").addClass("LV_invalid_field");
	}
	else {$("#txt_rib").removeClass("LV_invalid_field");$("#txt_rib").addClass("LV_valid_field");}
	// txt_kbis
	if($('#txt_kbis').val() == '<?=$this->lng['etape2']['aucun-fichier-selectionne']?>'){
		form_ok = false
		$("#txt_kbis").removeClass("LV_valid_field");$("#txt_kbis").addClass("LV_invalid_field");
	}
	else {$("#txt_kbis").removeClass("LV_invalid_field");$("#txt_kbis").addClass("LV_valid_field");}
	
	// check BIC
	if(check_bic($("#bic").val()) == false){form_ok = false;}

	//check IBAN
	var iban_ok = true;
	var size_iban = true;
	var new_iban = '';
	for(var i=1;i<=7;i++){
		// 4 caracteres
		if(i < 7){
			if($('#iban-'+i).val().length < 4 || $('#iban-'+i).val().length > 4){
				check_ibanNB('iban-'+i,$('#iban-'+i).val(),4);
				size_iban = false;
			}
			else new_iban = new_iban+$('#iban-'+i).val();
		}
		// 3 caracteres
		else{
			if($('#iban-'+i).val().length < 3 || $('#iban-'+i).val().length > 3){
				check_ibanNB('iban-'+i,$('#iban-'+i).val(),3);
				size_iban = false;	
			}
			else new_iban = new_iban+$('#iban-'+i).val();
		}
	}
	
	if($("#iban-1").val().substring(0,2) != 'FR'){	
		$("#iban-1").addClass('LV_invalid_field');$("#iban-1").removeClass('LV_valid_field');
		$(".error_iban").html('<?=$this->lng['etape2']['iban-erreur-2']?>');
		form_ok = false;
		iban_ok = false;	
	}
	else{ 
		$("#iban-1").addClass('LV_valid_field');$("#iban-1").removeClass('LV_invalid_field');
		$(".error_iban").html('<?=$this->lng['etape2']['iban-erreur']?>');
	}
	
	// Lorsque l'on a le bon nombre de caracteres
	if(size_iban == true){
		// On verifie si l'IBAN est bon
		if(validateIban(new_iban) == false){
			for(var i=1;i<=7;i++){
				$("#iban-"+i).addClass('LV_invalid_field');$("#iban-"+i).removeClass('LV_valid_field');
			}
			iban_ok = false;
		}
		else{
			for(var i=1;i<=7;i++){
				$("#iban-"+i).addClass('LV_valid_field');$("#iban-"+i).removeClass('LV_invalid_field');
			}
		}
	}
	else{ iban_ok = false; }
	
	// Resultat check IBAN
	if(iban_ok == false){ $(".error_iban").show(); form_ok = false }
	else $(".error_iban").hide();

	if(form_ok == false){ event.preventDefault(); }
});



</script>
*
