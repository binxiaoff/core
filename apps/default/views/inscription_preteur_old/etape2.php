<div class="main form-page">
    <div class="shell">

        <?=$this->fireView('../blocs/inscription-preteur')?>

        <div class="register-form leftaligned">
        	<?
			if(in_array($this->clients->type,array(1,3))){
				echo $this->fireView('particulier_etape_2');
			}
			elseif(in_array($this->clients->type,array(2,4))){
				echo $this->fireView('societe_etape_2');
			}
			?>
        </div><!-- /.register-form -->
    </div><!-- /.shell -->
</div>

<img src="http://ws.optin-machine.com/unilend/t_l.php" border="0" width="1" height="1" />
<img src="https://ext.ligatus.com/conversion/?c=68501&a=7877" width="1" height="1" />
<script type="text/javascript">
    var fb_param = { };
    fb_param.pixel_id = '6013140725883';
    fb_param.value = '0.00';
    fb_param.currency = 'EUR';
    (function(){
        var fpw = document.createElement('script');
        fpw.async = true;
        fpw.src = '//connect.facebook.net/en_US/fp.js';
        var ref = document.getElementsByTagName('script')[0];
        ref.parentNode.insertBefore(fpw, ref);
    })();
</script>

<noscript>
<img height="1" width="1" alt="" style="display:none"
     src="https://www.facebook.com/offsite_event.php?id=6013140725883&value=0.00&currency=EUR" />
</noscript>

<script type="text/javascript">
    var fb_param = { };
    fb_param.pixel_id = '6013140727083';
    fb_param.value = '0.00';
    fb_param.currency = 'EUR';
    (function(){
        var fpw = document.createElement('script');
        fpw.async = true;
        fpw.src = '//connect.facebook.net/en_US/fp.js';
        var ref = document.getElementsByTagName('script')[0];
        ref.parentNode.insertBefore(fpw, ref);
    })();
</script>

<noscript>
<img height="1" width="1" alt="" style="display:none"
     src="https://www.facebook.com/offsite_event.php?id=6013140727083&value=0.00&currency=EUR" />
</noscript>


<script type="text/javascript">

$("#bic").keyup(function() {
	check_bic($(this).val());
});
$("#bic").change(function() {
	check_bic($(this).val());
});

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


$("#form_inscription_preteur_particulier_etape_2").submit(function( event ) {
	var form_ok = true
	
	// origine
	if($('#origine_des_fonds').val() == 0){
		$('#origine_des_fonds').addClass('LV_invalid_field');$('#origine_des_fonds').removeClass('LV_valid_field');
		form_ok = false;
	}
	else{$('#origine_des_fonds').addClass('LV_valid_field');$('#origine_des_fonds').removeClass('LV_invalid_field');}
	
	
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
				size_iban = false
			}
			else new_iban = new_iban+$('#iban-'+i).val();
		}
		// 3 caracteres
		else{
			if($('#iban-'+i).val().length < 3 || $('#iban-'+i).val().length > 3){
				check_ibanNB('iban-'+i,$('#iban-'+i).val(),3);
				size_iban = false	
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
	if(size_iban == true)
	{
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
	else{
		iban_ok = false;
	}
	
	// Resultat check IBAN
	if(iban_ok == false){
		$(".error_iban").show();
		form_ok = false
	}
	else $(".error_iban").hide();
	
	
	
	// fichiers
	//ci
	if($('#txt_ci').val() == '<?=$this->lng['etape2']['aucun-fichier-selectionne']?>'){
		form_ok = false
		$("#txt_ci").removeClass("LV_valid_field");$("#txt_ci").addClass("LV_invalid_field");
	}
	else {$("#txt_ci").removeClass("LV_invalid_field");$("#txt_ci").addClass("LV_valid_field");}
	// justificatif_de_domicile
	if($('#txt_justificatif_de_domicile').val() == '<?=$this->lng['etape2']['aucun-fichier-selectionne']?>'){
		form_ok = false
		$("#txt_justificatif_de_domicile").removeClass("LV_valid_field");$("#txt_justificatif_de_domicile").addClass("LV_invalid_field");
	}
	else {$("#txt_justificatif_de_domicile").removeClass("LV_invalid_field");$("#txt_justificatif_de_domicile").addClass("LV_valid_field");}
	// rib
	if($('#txt_rib').val() == '<?=$this->lng['etape2']['aucun-fichier-selectionne']?>'){
		form_ok = false
		$("#txt_rib").removeClass("LV_valid_field");$("#txt_rib").addClass("LV_invalid_field");
	}
	else {$("#txt_rib").removeClass("LV_invalid_field");$("#txt_rib").addClass("LV_valid_field");}
	
	
	if($('#origine_des_fonds').val() == '1000000'){
		if($('#preciser').val() == '' || $('#preciser').val() == $('#preciser').attr('title')){
			form_ok = false;
			$("#preciser").removeClass("LV_valid_field");$("#preciser").addClass("LV_invalid_field");
		}
		else{ $("#preciser").removeClass("LV_invalid_field");$("#preciser").addClass("LV_valid_field"); }
	}
	
	
	if(form_ok == false){
		event.preventDefault();
	}
});


$("#form_inscription_preteur_societe_etape_2").submit(function( event ) {
	var form_ok = true
	
	// origine
	if($('#origine_des_fonds').val() == 0){
		$('#origine_des_fonds').addClass('LV_invalid_field');$('#origine_des_fonds').removeClass('LV_valid_field');
		form_ok = false;
	}
	else{$('#origine_des_fonds').addClass('LV_valid_field');$('#origine_des_fonds').removeClass('LV_invalid_field');}
	
	// check BIC
	if(check_bic($("#bic").val()) == false){form_ok = false;}

	//check IBAN
	var iban_ok = true;
	var size_iban = true;
	var new_iban = '';
	for(var i=1;i<=7;i++){
		// 4 caracteres
		if(i < 7){
	 		if($('#iban-'+i).val().length < 4  || $('#iban-'+i).val().length > 4){
				check_ibanNB('iban-'+i,$('#iban-'+i).val(),4);
				size_iban = false
			}
			else new_iban = new_iban+$('#iban-'+i).val();
		}
		// 3 caracteres
		else{
			if($('#iban-'+i).val().length < 3  || $('#iban-'+i).val().length > 3){
				check_ibanNB('iban-'+i,$('#iban-'+i).val(),3);
				size_iban = false	
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
	if(size_iban == true)
	{
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
	else{
		iban_ok = false;
	}
	
	// Resultat check IBAN
	if(iban_ok == false){
		$(".error_iban").show();
		form_ok = false
	}
	else $(".error_iban").hide();
	
	
	// fichiers
	// ci_dirigeant
	if($('#txt_ci_dirigeant').val() == '<?=$this->lng['etape2']['aucun-fichier-selectionne']?>'){
		form_ok = false
		$("#txt_ci_dirigeant").removeClass("LV_valid_field");$("#txt_ci_dirigeant").addClass("LV_invalid_field");
	}
	else {$("#txt_ci_dirigeant").removeClass("LV_invalid_field");$("#txt_ci_dirigeant").addClass("LV_valid_field");}
	<?php /*?>// delegation_pouvoir
	if($('#txt_delegation_pouvoir').val() == '<?=$this->lng['etape2']['aucun-fichier-selectionne']?>'){
		form_ok = false
		$("#txt_delegation_pouvoir").removeClass("LV_valid_field");$("#txt_delegation_pouvoir").addClass("LV_invalid_field");
	}
	else {$("#txt_delegation_pouvoir").removeClass("LV_invalid_field");$("#txt_delegation_pouvoir").addClass("LV_valid_field");}<?php */?>
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
	
	if($('#origine_des_fonds').val() == '1000000'){
		if($('#preciser').val() == '' || $('#preciser').val() == $('#preciser').attr('title')){
			form_ok = false;
			$("#preciser").removeClass("LV_valid_field");$("#preciser").addClass("LV_invalid_field");
		}
		else{ $("#preciser").removeClass("LV_invalid_field");$("#preciser").addClass("LV_valid_field"); }
	}
	
	if(form_ok == false){
		event.preventDefault();
	}
});




</script>